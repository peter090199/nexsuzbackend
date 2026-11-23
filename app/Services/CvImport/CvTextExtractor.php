<?php

namespace App\Services\CvImport;

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;
use ZipArchive;

/**
 * Plain text out of an uploaded CV / resume: PDF, DOCX or DOC. Anything that cannot be read becomes a
 * CvDocumentException with a message the job seeker can act on. Nothing here ever writes to disk or the database.
 */
class CvTextExtractor
{
    public const TYPES = ['pdf', 'doc', 'docx'];
    private const MAX_XML_BYTES = 12_000_000;      // an unzipped document.xml bigger than this is not a CV (zip bomb guard)
    private const MIN_CHARS = 40;                  // less than this is "no text"

    public function __construct(private PdfTextReader $pdf = new PdfTextReader())
    {
    }

    /** the extension that decides how a stored file is read ("pdf", "doc", "docx"), or null */
    public static function typeOf(string $pathOrName): ?string
    {
        $ext = strtolower(pathinfo($pathOrName, PATHINFO_EXTENSION));

        return in_array($ext, self::TYPES, true) ? $ext : null;
    }

    public function extract(string $absolutePath, string $type): string
    {
        $text = match ($type) {
            'pdf' => $this->pdf->read($absolutePath),
            'docx' => $this->docx($absolutePath),
            'doc' => $this->doc($absolutePath),
            default => throw new CvDocumentException('unsupported', 'Only PDF, DOC and DOCX files can be read.'),
        };

        $text = trim($text);
        if (mb_strlen($text) < self::MIN_CHARS) {
            if ($type === 'pdf' && $this->pdf->diagnosis === 'hidden') {
                throw new CvDocumentException('unreadable', 'This PDF has text, but it is stored in a way we cannot read. Export or "Save as" the document to PDF again, or upload it as a DOCX file, or fill up your CV by hand.');
            }
            throw $type === 'pdf'
                ? new CvDocumentException('scanned', 'We could not find any text in this PDF. It may be a scan or a picture of a page. Upload a PDF saved from a document, or a DOCX file, or fill up your CV by hand.')
                : new CvDocumentException('empty', 'There is hardly any text in this file, so there is nothing to import.');
        }

        return $text;
    }

    // ------------------------------------------------------------------ DOCX

    private function docx(string $file): string
    {
        $zip = new ZipArchive();
        if ($zip->open($file) !== true) {
            throw new CvDocumentException('corrupt', 'This file is not a readable DOCX document.');
        }
        try {
            $stat = $zip->statName('word/document.xml');
            if (!$stat) {
                throw new CvDocumentException('corrupt', 'This file is not a readable DOCX document.');
            }
            if ($stat['size'] > self::MAX_XML_BYTES) {
                throw new CvDocumentException('corrupt', 'This document is too large to read.');
            }
            $xml = $zip->getFromName('word/document.xml');
        } finally {
            $zip->close();
        }
        if (!is_string($xml) || $xml === '') {
            throw new CvDocumentException('corrupt', 'This file is not a readable DOCX document.');
        }

        // paragraph / line / tab boundaries become text boundaries, then every tag goes
        $xml = preg_replace('/<w:tab\s*\/>/', "\t", $xml);
        $xml = preg_replace('/<w:(?:br|cr)[^>]*\/>/', "\n", $xml);
        $xml = preg_replace('/<\/w:p>/', "\n", $xml);
        $text = html_entity_decode(strip_tags($xml), ENT_QUOTES | ENT_XML1, 'UTF-8');

        return $this->clean($text);
    }

    // ------------------------------------------------------------------ DOC (legacy Word)

    private function doc(string $file): string
    {
        $bin = (new ExecutableFinder())->find('antiword');
        if (!$bin) {
            throw new CvDocumentException('unsupported', 'Older .doc files cannot be read on this server. Save the file as a PDF or DOCX and upload that instead.');
        }
        $p = new Process([$bin, '-m', 'UTF-8.txt', $file]);        // array form: no shell
        $p->setTimeout(30);
        $p->run();
        if (!$p->isSuccessful()) {
            throw new CvDocumentException('corrupt', 'This .doc file could not be read. Save it as a PDF or DOCX and upload that instead.');
        }

        return $this->clean($p->getOutput());
    }

    private function clean(string $t): string
    {
        $t = str_replace(["\r\n", "\r", "\xC2\xA0"], ["\n", "\n", ' '], $t);
        $t = preg_replace('/[^\P{C}\n\t]+/u', '', $t) ?? $t;        // control characters other than newline / tab
        $t = preg_replace("/[ \t]+\n/", "\n", $t);
        $t = preg_replace("/\n{4,}/", "\n\n\n", $t);

        return trim($t);
    }
}
