<?php

namespace Tests\Feature\Concerns;

use Illuminate\Http\UploadedFile;
use ZipArchive;

/** Builds small but REAL DOCX and PDF files (not mocks) so the extraction tests run on genuine documents. */
trait BuildsCvFiles
{
    /** @var string[] temp files to remove */
    private array $cvTempFiles = [];

    protected function cleanCvFiles(): void
    {
        foreach ($this->cvTempFiles as $f) { @unlink($f); }
        $this->cvTempFiles = [];
    }

    private function tempPath(string $ext): string
    {
        return $this->cvTempFiles[] = sys_get_temp_dir() . '/cvtest_' . bin2hex(random_bytes(6)) . '.' . $ext;
    }

    /** a genuine .docx: one Word paragraph per line of text */
    protected function docx(string $text, string $name = 'cv.docx'): UploadedFile
    {
        $path = $this->tempPath('docx');
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $paras = '';
        foreach (explode("\n", $text) as $line) {
            $paras .= '<w:p><w:r><w:t xml:space="preserve">' . htmlspecialchars($line, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</w:t></w:r></w:p>';
        }
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');
        $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>' . $paras . '</w:body></w:document>');
        $zip->close();

        return new UploadedFile($path, $name, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);
    }

    /** a zip called .docx that is not a Word document */
    protected function fakeDocx(string $name = 'broken.docx'): UploadedFile
    {
        $path = $this->tempPath('docx');
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('readme.txt', 'this is not a word document');
        $zip->close();

        return new UploadedFile($path, $name, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);
    }

    /** a genuine PDF with a text layer: one line of text per line, standard font, uncompressed content stream */
    protected function pdf(string $text, string $name = 'cv.pdf'): UploadedFile
    {
        $ops = "BT\n/F1 11 Tf\n14 TL\n50 760 Td\n";
        foreach (explode("\n", $text) as $line) {
            $esc = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], mb_convert_encoding($line, 'Windows-1252', 'UTF-8'));
            $ops .= "({$esc}) Tj\nT*\n";
        }
        $ops .= "ET\n";

        return $this->pdfFromContent($ops, $name);
    }

    /**
     * A PDF built the way Canva (and many other CV makers) build them, which is what a plain reader cannot read:
     *  - a subset Type0 font with 2-byte glyph ids ("Identity-H"), so the text is a list of glyph numbers, not letters
     *  - a ToUnicode table (bfchar and bfrange) saying which letter each glyph number is
     *  - Flate-compressed streams, text drawn as hex strings, every word placed on its own with a `cm` transform
     * With $objectStreams the page, font and catalog objects sit inside a compressed object stream, as in PDF 1.5+.
     * With $withToUnicode = false there is no table at all: the text cannot be recovered by anyone.
     */
    protected function canvaPdf(string $text, bool $objectStreams = false, bool $withToUnicode = true, string $name = 'canva.pdf'): UploadedFile
    {
        $lines = explode("\n", $text);
        $chars = array_values(array_unique(preg_split('//u', implode('', $lines), -1, PREG_SPLIT_NO_EMPTY)));
        $gid = [];
        foreach ($chars as $i => $ch) { $gid[$ch] = $i + 3; }                  // real subsets start at a few

        // the ToUnicode table: single glyphs as bfchar, and the first run of consecutive glyphs as one bfrange
        $utf16 = fn (string $ch) => strtoupper(bin2hex(mb_convert_encoding($ch, 'UTF-16BE', 'UTF-8')));
        $single = [];
        foreach ($gid as $ch => $g) { $single[] = sprintf('<%04X> <%s>', $g, $utf16($ch)); }
        $blocks = '';
        foreach (array_chunk($single, 90) as $chunk) { $blocks .= count($chunk) . " beginbfchar\n" . implode("\n", $chunk) . "\nendbfchar\n"; }
        $cmap = "/CIDInit /ProcSet findresource begin\n12 dict begin\nbegincmap\n/CMapName /Adobe-Identity-UCS def\n/CMapType 2 def\n"
            . "1 begincodespacerange\n<0000> <FFFF>\nendcodespacerange\n" . $blocks . "endcmap\nCMapName currentdict /CMap defineresource pop\nend\nend\n";

        // the page content: each word is a separate chunk placed by `cm`, as Canva does
        $content = '';
        foreach ($lines as $row => $line) {
            $x = 40;
            foreach (preg_split('/\s+/u', trim($line), -1, PREG_SPLIT_NO_EMPTY) as $word) {
                $hex = '';
                foreach (preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY) as $ch) { $hex .= sprintf('%04X', $gid[$ch]); }
                $content .= sprintf("q 1 0 0 1 %d %d cm\nBT\n/F1 11 Tf\n0 0 Td\n<%s> Tj\nET\nQ\n", $x, 780 - $row * 14, $hex);
                $x += 7 * (mb_strlen($word) + 1);
            }
        }

        $z = fn (string $s) => gzcompress($s);
        $dicts = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            3 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            5 => '<< /Type /Font /Subtype /Type0 /BaseFont /ABCDEF+CanvaSans /Encoding /Identity-H /DescendantFonts [6 0 R]' . ($withToUnicode ? ' /ToUnicode 7 0 R' : '') . ' >>',
            6 => '<< /Type /Font /Subtype /CIDFontType2 /BaseFont /ABCDEF+CanvaSans /CIDSystemInfo << /Registry (Adobe) /Ordering (Identity) /Supplement 0 >> /DW 1000 >>',
        ];
        $streams = [4 => $z($content)];
        if ($withToUnicode) { $streams[7] = $z($cmap); }

        $pdf = "%PDF-1.5\n";
        $offsets = [];
        $emit = function (int $n, string $dict, ?string $stream) use (&$pdf, &$offsets) {
            $offsets[$n] = strlen($pdf);
            $pdf .= $stream === null
                ? "{$n} 0 obj\n{$dict}\nendobj\n"
                : "{$n} 0 obj\n<< /Filter /FlateDecode /Length " . strlen($stream) . " >>\nstream\n{$stream}\nendstream\nendobj\n";
        };

        if ($objectStreams) {
            $body = ''; $head = ''; $pos = 0;
            foreach ($dicts as $n => $d) { $head .= "{$n} {$pos} "; $body .= $d . "\n"; $pos = strlen($body); }
            $first = strlen($head);
            $os = $z($head . $body);
            $offsets[9] = strlen($pdf);
            $pdf .= "9 0 obj\n<< /Type /ObjStm /N " . count($dicts) . " /First {$first} /Filter /FlateDecode /Length " . strlen($os) . " >>\nstream\n{$os}\nendstream\nendobj\n";
        } else {
            foreach ($dicts as $n => $d) { $emit($n, $d, null); }
        }
        foreach ($streams as $n => $s) { $emit($n, '', $s); }
        $pdf .= "trailer\n<< /Root 1 0 R >>\n%%EOF\n";

        $path = $this->tempPath('pdf');
        file_put_contents($path, $pdf);

        return new UploadedFile($path, $name, 'application/pdf', null, true);
    }

    /** a PDF whose only content is a drawn rectangle: no text layer, like a scan */
    protected function scannedPdf(string $name = 'scan.pdf'): UploadedFile
    {
        return $this->pdfFromContent("0.8 g\n50 50 400 600 re\nf\n", $name);
    }

    private function pdfFromContent(string $content, string $name): UploadedFile
    {
        $objs = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            "<< /Length " . strlen($content) . " >>\nstream\n{$content}endstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objs as $i => $o) {
            $offsets[$i + 1] = strlen($pdf);
            $pdf .= ($i + 1) . " 0 obj\n{$o}\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objs) + 1) . "\n0000000000 65535 f \n";
        foreach ($offsets as $off) { $pdf .= sprintf("%010d 00000 n \n", $off); }
        $pdf .= "trailer\n<< /Size " . (count($objs) + 1) . " /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";

        $path = $this->tempPath('pdf');
        file_put_contents($path, $pdf);

        return new UploadedFile($path, $name, 'application/pdf', null, true);
    }

    /** a realistic CV, as plain text */
    protected function sampleCv(): string
    {
        return <<<'TXT'
PEDRO YORPO
Software Engineer
pedro.yorpo@gmail.com | +63 917 123 4567 | Quezon City, Metro Manila, Philippines

PROFESSIONAL SUMMARY
Software engineer with 6 years of experience building web applications with Laravel and Angular. Passionate about clean code and mentoring junior developers.

WORK EXPERIENCE
Senior Software Engineer - Acme Technologies Inc.
Jan 2021 - Present
- Led a team of five engineers building a recruitment platform
- Reduced page load time by 40%

Software Developer, Globex Corporation
June 2018 - December 2020
- Built REST APIs with Laravel
- Maintained Angular front ends

EDUCATION
Bachelor of Science in Information Technology
Ateneo de Manila University
2012 - 2016

Senior High School - STEM
Quezon City Science High School
2010 - 2012

SKILLS
PHP, Laravel, Angular, TypeScript, MySQL
- Git
- Docker

LANGUAGES
English (Fluent), Filipino (Native)

CERTIFICATIONS
AWS Certified Developer - Amazon Web Services (2022)
Laravel Certified Developer, Laravel Inc. 2020

TRAININGS AND SEMINARS
Advanced Angular Workshop - Angular Philippines, March 2023
Secure Coding Training | Cyber Academy | 2021

PERSONAL INFORMATION
Date of Birth: August 29, 1995
Permanent Address: Bulacan, Philippines

REFERENCES
Available upon request.
TXT;
    }
}
