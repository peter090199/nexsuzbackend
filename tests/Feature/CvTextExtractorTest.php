<?php

namespace Tests\Feature;

use App\Services\CvImport\CvDocumentException;
use App\Services\CvImport\CvParser;
use App\Services\CvImport\CvTextExtractor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\ExecutableFinder;
use Tests\Feature\Concerns\BuildsCvFiles;

/**
 * Reading real files: DOCX and PDF (built here, not mocked), and every way a file can fail to be read.
 */
class CvTextExtractorTest extends TestCase
{
    use BuildsCvFiles;

    protected function tearDown(): void
    {
        $this->cleanCvFiles();
        parent::tearDown();
    }

    private function extract(string $path, string $type): string
    {
        return (new CvTextExtractor())->extract($path, $type);
    }

    private function expectFailure(string $reason, callable $run): void
    {
        try {
            $run();
        } catch (CvDocumentException $e) {
            $this->assertSame($reason, $e->reason, $e->getMessage());
            $this->assertNotSame('', $e->getMessage());
            $this->assertStringNotContainsString('/', $e->getMessage(), 'a message never shows a path');

            return;
        }
        $this->fail("expected a '{$reason}' failure");
    }

    public function test_it_reads_a_docx_and_keeps_the_lines(): void
    {
        $text = $this->extract($this->docx($this->sampleCv())->getPathname(), 'docx');

        $this->assertStringContainsString("PEDRO YORPO\nSoftware Engineer", $text);
        $this->assertStringContainsString('Reduced page load time by 40%', $text, 'a percent sign and ordinary punctuation survive');
    }

    public function test_special_characters_in_a_docx_are_decoded(): void
    {
        $text = $this->extract($this->docx("Renée O'Brien & Sons <Ltd>\nJosé Peña — Manager of Operations at Acme Philippines")->getPathname(), 'docx');

        $this->assertStringContainsString("Renée O'Brien & Sons <Ltd>", $text);
        $this->assertStringContainsString('José Peña', $text);
    }

    public function test_a_whole_cv_goes_from_docx_to_profile_data(): void
    {
        $parsed = (new CvParser())->parse($this->extract($this->docx($this->sampleCv())->getPathname(), 'docx'));

        $this->assertSame('Pedro', $parsed['profile']['fname']);
        $this->assertCount(2, $parsed['employment']);
        $this->assertCount(2, $parsed['education']);
        $this->assertContains('Laravel', array_column($parsed['skills'], 'skills'));
    }

    public function test_it_reads_a_pdf_with_a_text_layer(): void
    {
        $text = $this->extract($this->pdf($this->sampleCv())->getPathname(), 'pdf');

        $this->assertStringContainsString('PEDRO YORPO', $text);
        $this->assertStringContainsString('Ateneo de Manila University', $text);
    }

    public function test_a_whole_cv_goes_from_pdf_to_profile_data(): void
    {
        $parsed = (new CvParser())->parse($this->extract($this->pdf($this->sampleCv())->getPathname(), 'pdf'));

        $this->assertSame('Pedro', $parsed['profile']['fname']);
        $this->assertSame('Yorpo', $parsed['profile']['lname']);
        $this->assertSame('pedro.yorpo@gmail.com', $parsed['profile']['email']);
        $this->assertNotEmpty($parsed['employment']);
        $this->assertNotEmpty($parsed['education']);
    }

    public function test_the_built_in_pdf_reader_works_on_its_own(): void
    {
        // the reader that needs no package and no program on the server (the others are used first when present)
        $m = new \ReflectionMethod(\App\Services\CvImport\PdfTextReader::class, 'builtin');
        $m->setAccessible(true);
        $bytes = file_get_contents($this->pdf($this->sampleCv())->getPathname());

        $text = $m->invoke(new \App\Services\CvImport\PdfTextReader(), $bytes);

        $this->assertStringContainsString('PEDRO YORPO', $text);
        $this->assertStringContainsString("Software Engineer\npedro.yorpo@gmail.com", $text, 'lines stay on their own line');
        $this->assertStringContainsString('Reduced page load time by 40%', $text);
    }

    public function test_the_built_in_reader_reads_a_compressed_content_stream(): void
    {
        $m = new \ReflectionMethod(\App\Services\CvImport\PdfTextReader::class, 'builtin');
        $m->setAccessible(true);
        $content = "BT\n/F1 11 Tf\n14 TL\n50 760 Td\n(Compressed Resume Line One) Tj\nT*\n(Second line of the resume) Tj\nET\n";
        $pdf = "%PDF-1.4\n1 0 obj\n<< /Filter /FlateDecode /Length 0 >>\nstream\n" . gzcompress($content) . "\nendstream\nendobj\n%%EOF";

        $text = $m->invoke(new \App\Services\CvImport\PdfTextReader(), $pdf);

        $this->assertStringContainsString("Compressed Resume Line One\nSecond line of the resume", $text);
    }

    /** an extractor that may only use the built-in PDF reader: what a server with no extra software has */
    private function builtinOnly(): CvTextExtractor
    {
        return new CvTextExtractor(new class extends \App\Services\CvImport\PdfTextReader {
            protected function readerOrder(): array { return ['builtin']; }
        });
    }

    public function test_a_canva_style_pdf_with_glyph_ids_and_a_tounicode_table_is_read(): void
    {
        // the case that used to be reported as "scanned": 2-byte glyph ids, compressed streams, words placed one by one
        $text = $this->builtinOnly()->extract($this->canvaPdf($this->sampleCv())->getPathname(), 'pdf');

        $this->assertStringContainsString('PEDRO YORPO', $text);
        $this->assertStringContainsString("PEDRO YORPO\nSoftware Engineer\npedro.yorpo@gmail.com", $text, 'every line is on its own line');
        $this->assertStringContainsString('Senior Software Engineer - Acme Technologies Inc.', $text, 'the words of a line are joined with spaces');
        $this->assertStringContainsString('Reduced page load time by 40%', $text);
    }

    public function test_a_canva_style_pdf_goes_all_the_way_to_profile_data(): void
    {
        $parsed = (new CvParser())->parse($this->builtinOnly()->extract($this->canvaPdf($this->sampleCv())->getPathname(), 'pdf'));

        $this->assertSame(['Pedro', 'Yorpo', 'pedro.yorpo@gmail.com'], [$parsed['profile']['fname'], $parsed['profile']['lname'], $parsed['profile']['email']]);
        $this->assertCount(2, $parsed['employment']);
        $this->assertCount(2, $parsed['education']);
        $this->assertContains('Laravel', array_column($parsed['skills'], 'skills'));
        $this->assertSame('1995-08-29', $parsed['profile']['date_birth']);
    }

    public function test_a_pdf_with_its_objects_in_a_compressed_object_stream_is_read(): void
    {
        // PDF 1.5+ (Word, Chrome, Google Docs): the page tree and the fonts are inside an object stream
        $text = $this->builtinOnly()->extract($this->canvaPdf($this->sampleCv(), true)->getPathname(), 'pdf');

        $this->assertStringContainsString('Ateneo de Manila University', $text);
        $this->assertStringContainsString("PEDRO YORPO\nSoftware Engineer", $text);
    }

    public function test_special_characters_survive_the_font_table(): void
    {
        $text = $this->builtinOnly()->extract($this->canvaPdf("Renée Peña Ñoño\nSenior Accountant at José's Café Inc.\nManila Philippines 2018 2020 present worked")->getPathname(), 'pdf');

        $this->assertStringContainsString('Renée Peña Ñoño', $text);
        $this->assertStringContainsString("José's Café Inc.", $text);
    }

    public function test_a_pdf_whose_text_cannot_be_decoded_is_unreadable_not_scanned(): void
    {
        // glyph ids and no ToUnicode table: the text is there but nobody can turn it into letters
        try {
            $this->builtinOnly()->extract($this->canvaPdf($this->sampleCv(), false, false)->getPathname(), 'pdf');
            $this->fail('expected a failure');
        } catch (CvDocumentException $e) {
            $this->assertSame('unreadable', $e->reason, 'it has text: calling it a scan would be wrong');
            $this->assertStringContainsString('DOCX', $e->getMessage());
        }
    }

    public function test_a_real_scan_is_still_called_a_scan(): void
    {
        try {
            $this->builtinOnly()->extract($this->scannedPdf()->getPathname(), 'pdf');
            $this->fail('expected a failure');
        } catch (CvDocumentException $e) {
            $this->assertSame('scanned', $e->reason);
        }
    }

    public function test_the_default_reader_chain_reads_a_canva_style_pdf_too(): void
    {
        // whichever readers this machine has (smalot, pdftotext, built in), the result is the same CV
        $parsed = (new CvParser())->parse($this->extract($this->canvaPdf($this->sampleCv())->getPathname(), 'pdf'));

        $this->assertSame('Pedro', $parsed['profile']['fname']);
        $this->assertCount(2, $parsed['employment']);
    }

    public function test_a_password_protected_pdf_is_explained(): void
    {
        $path = sys_get_temp_dir() . '/cvtest_locked.pdf';
        file_put_contents($path, "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R /Encrypt 9 0 R >>\n%%EOF\n");
        $this->cvTempFiles[] = $path;

        $this->expectFailure('corrupt', fn () => $this->extract($path, 'pdf'));
    }

    public function test_a_scanned_pdf_is_reported_as_scanned(): void
    {
        $this->expectFailure('scanned', fn () => $this->extract($this->scannedPdf()->getPathname(), 'pdf'));
    }

    public function test_a_pdf_that_is_not_a_pdf_is_corrupt(): void
    {
        $path = sys_get_temp_dir() . '/cvtest_notpdf.pdf';
        file_put_contents($path, "this is plain text, not a PDF at all\n");
        $this->cvTempFiles[] = $path;

        $this->expectFailure('corrupt', fn () => $this->extract($path, 'pdf'));
    }

    public function test_a_zip_called_docx_is_corrupt(): void
    {
        $this->expectFailure('corrupt', fn () => $this->extract($this->fakeDocx()->getPathname(), 'docx'));
    }

    public function test_a_docx_with_almost_no_text_is_empty(): void
    {
        $this->expectFailure('empty', fn () => $this->extract($this->docx('Hi')->getPathname(), 'docx'));
    }

    public function test_an_unknown_type_is_unsupported(): void
    {
        $this->expectFailure('unsupported', fn () => $this->extract($this->docx($this->sampleCv())->getPathname(), 'rtf'));
    }

    public function test_a_legacy_doc_is_read_only_when_a_converter_exists_and_otherwise_explained(): void
    {
        $path = sys_get_temp_dir() . '/cvtest_old.doc';
        file_put_contents($path, "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1" . str_repeat("\0", 600));   // the OLE signature of a .doc
        $this->cvTempFiles[] = $path;

        if ((new ExecutableFinder())->find('antiword')) {
            $this->expectException(CvDocumentException::class);       // converter present: it cannot read this blank stub
            $this->extract($path, 'doc');

            return;
        }
        $this->expectFailure('unsupported', fn () => $this->extract($path, 'doc'));
    }

    public function test_the_type_comes_from_the_extension(): void
    {
        $this->assertSame('pdf', CvTextExtractor::typeOf('uploads/1/resumes/abc.PDF'));
        $this->assertSame('docx', CvTextExtractor::typeOf('x.docx'));
        $this->assertSame('doc', CvTextExtractor::typeOf('x.doc'));
        $this->assertNull(CvTextExtractor::typeOf('x.exe'));
        $this->assertNull(CvTextExtractor::typeOf('noextension'));
    }
}
