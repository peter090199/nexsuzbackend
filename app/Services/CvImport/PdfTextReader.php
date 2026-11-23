<?php

namespace App\Services\CvImport;

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

/**
 * Plain text out of a PDF. Readers are tried in this order and the first one that gives real text wins:
 *   1. the built-in reader below, which needs nothing installed. It understands what most CV makers produce
 *      (Canva, Word, Google Docs, Chrome, LibreOffice): compressed streams and object streams, subset fonts whose
 *      characters are mapped by a ToUnicode table (2-byte "Identity-H" codes), and the position of every piece of text.
 *   2. smalot/pdfparser, if the package is installed
 *   3. the `pdftotext` program (poppler), if the server has it (env PDFTOTEXT_BINARY, the PATH, or a usual folder)
 * A PDF with no text layer (a scan) gives no text from any of them.
 *
 * After read(), `diagnosis` says what was found: 'ok', 'hidden' (the page has text, but its fonts give no way to
 * turn it into letters) or 'none' (no text at all: a picture of a page).
 */
class PdfTextReader
{
    public string $diagnosis = 'none';

    /** a text-showing operator was met, whether or not it could be decoded */
    private bool $sawText = false;

    /** @var array<int, array{d:string, s:?string}> */
    private array $objs = [];
    /** @var array<int, array{composite:bool, width:int, map:?array}> */
    private array $fontCache = [];

    public function read(string $file): string
    {
        $this->diagnosis = 'none';
        $this->sawText = false;
        $bytes = @file_get_contents($file);
        if ($bytes === false || !str_contains(substr($bytes, 0, 1024), '%PDF')) {
            throw new CvDocumentException('corrupt', 'This file is not a readable PDF.');
        }
        if (preg_match('~/Encrypt\b~', substr($bytes, -4096)) || preg_match('~/Encrypt\s+\d+\s+\d+\s+R~', $bytes)) {
            throw new CvDocumentException('corrupt', 'This PDF is password protected, so it cannot be read. Remove the password and upload it again.');
        }

        foreach ($this->readerOrder() as $reader) {
            try {
                $text = match ($reader) {
                    'smalot' => $this->withSmalot($file),
                    'pdftotext' => $this->withPdftotext($file),
                    default => $this->builtin($bytes),
                };
            } catch (\Throwable) {
                $text = null;
            }
            if ($text !== null && self::usable($text)) {
                $this->diagnosis = 'ok';

                return $text;
            }
        }
        $this->diagnosis = $this->sawText ? 'hidden' : 'none';

        return '';   // nothing readable: the extractor says why
    }

    /** which readers to try, in order (a test can narrow it to one) */
    protected function readerOrder(): array
    {
        // the built-in reader first: it keeps the order a CV is drawn in and joins the words of a line, which is how Canva
        // and most CV makers lay text out. The others are for PDFs it cannot decode (fonts with no ToUnicode table).
        return ['builtin', 'smalot', 'pdftotext'];
    }

    /** enough real letters to be worth parsing (and not the noise a wrong font table produces) */
    public static function usable(string $text): bool
    {
        $letters = preg_match_all('/\p{L}/u', $text);
        $len = max(1, mb_strlen($text));

        return $letters >= 40 && ($letters / $len) > 0.35;
    }

    // ------------------------------------------------------------------ readers 1 and 2

    private function withSmalot(string $file): ?string
    {
        if (!class_exists(\Smalot\PdfParser\Parser::class)) {
            return null;
        }

        return (new \Smalot\PdfParser\Parser())->parseFile($file)->getText();
    }

    private function withPdftotext(string $file): ?string
    {
        $bin = $this->findPdftotext();
        if (!$bin) {
            return null;
        }
        // default reading order: it groups the words of a line (-raw would put every word on its own line, and -layout
        // would glue the two columns of a CV into one)
        $p = new Process([$bin, '-enc', 'UTF-8', $file, '-']);   // array form: no shell, the path is one argument
        $p->setTimeout(30);
        $p->run();

        return $p->isSuccessful() ? $p->getOutput() : null;
    }

    private function findPdftotext(): ?string
    {
        $env = getenv('PDFTOTEXT_BINARY') ?: ($_ENV['PDFTOTEXT_BINARY'] ?? '');
        if ($env && is_file($env)) {
            return $env;
        }

        return (new ExecutableFinder())->find('pdftotext', null, ['/usr/bin', '/usr/local/bin', '/opt/homebrew/bin', 'C:\\Program Files\\poppler\\Library\\bin', 'C:\\Program Files\\Git\\ucrt64\\bin']);
    }

    // ------------------------------------------------------------------ reader 3: built in

    private function builtin(string $pdf): string
    {
        $this->objs = [];
        $this->fontCache = [];
        $this->parseObjects($pdf);
        if (!$this->objs) {
            return '';
        }

        $out = '';
        foreach ($this->pageNumbers() as $pageNo) {
            $out .= $this->pageText($pageNo) . "\n\n";
            if (strlen($out) > 3_000_000) { break; }
        }
        if (trim($out) === '') {
            // a damaged page tree: read every stream that looks like page content
            foreach ($this->objs as $o) {
                if ($o['s'] !== null && str_contains($o['s'], 'BT')) { $out .= $this->contentText($o['s'], [], []) . "\n"; }
            }
        }

        return $out;
    }

    // ---- objects

    private function parseObjects(string $pdf): void
    {
        if (!preg_match_all('/(?<![\d])(\d{1,7})\s+(\d{1,5})\s+obj\b/', $pdf, $m, PREG_OFFSET_CAPTURE)) {
            return;
        }
        $count = count($m[0]);
        for ($i = 0; $i < $count; $i++) {
            $start = $m[0][$i][1] + strlen($m[0][$i][0]);
            $end = $i + 1 < $count ? $m[0][$i + 1][1] : strlen($pdf);
            $body = substr($pdf, $start, $end - $start);
            $num = (int) $m[1][$i][0];

            $sp = strpos($body, 'stream');
            if ($sp !== false && ($dEnd = strrpos(substr($body, 0, $sp), '>>')) !== false) {
                $dict = substr($body, 0, $dEnd + 2);
                $data = substr($body, $sp + 6);
                $data = preg_replace('/^\r?\n/', '', $data);
                $es = strrpos($data, 'endstream');
                if ($es !== false) { $data = substr($data, 0, $es); }
                $data = rtrim($data, "\r\n");
                $this->objs[$num] = ['d' => $dict, 's' => $this->decode($dict, $data)];
            } else {
                $this->objs[$num] = ['d' => $body, 's' => null];
            }
        }

        // PDF 1.5+ keeps many objects (pages, fonts) inside compressed object streams
        foreach ($this->objs as $num => $o) {
            if ($o['s'] !== null && preg_match('~/Type\s*/ObjStm~', $o['d']) && preg_match('~/N\s+(\d+)~', $o['d'], $n) && preg_match('~/First\s+(\d+)~', $o['d'], $f)) {
                $this->expandObjectStream($o['s'], (int) $n[1], (int) $f[1]);
            }
        }
    }

    private function expandObjectStream(string $data, int $n, int $first): void
    {
        if (!preg_match_all('/(\d+)\s+(\d+)/', substr($data, 0, $first), $p, PREG_SET_ORDER)) { return; }
        $p = array_slice($p, 0, $n);
        foreach ($p as $i => $pair) {
            $off = $first + (int) $pair[2];
            $next = isset($p[$i + 1]) ? $first + (int) $p[$i + 1][2] : strlen($data);
            $this->objs[(int) $pair[1]] ??= ['d' => substr($data, $off, max(0, $next - $off)), 's' => null];
        }
    }

    /** undo the stream filters we can: Flate, ASCIIHex, ASCII85 (images and anything else are left alone) */
    private function decode(string $dict, string $data): ?string
    {
        if (!preg_match('~/Filter\s*(\[[^\]]*\]|/\w+)~', $dict, $f)) { return $data; }
        foreach (preg_split('~[\s\[\]]+/?~', trim($f[1], " []")) as $name) {
            $name = ltrim($name, '/');
            if ($name === '') { continue; }
            if (in_array($name, ['FlateDecode', 'Fl'], true)) {
                $d = @gzuncompress($data);
                if ($d === false && strlen($data) > 2) { $d = @gzinflate(substr($data, 2)); }
                if ($d === false) { return null; }
                $data = $d;
            } elseif (in_array($name, ['ASCIIHexDecode', 'AHx'], true)) {
                $data = (string) hex2bin(preg_replace('/[^0-9A-Fa-f]/', '', preg_replace('/>.*$/s', '', $data)) . (strlen(preg_replace('/[^0-9A-Fa-f]/', '', $data)) % 2 ? '0' : ''));
            } elseif (in_array($name, ['ASCII85Decode', 'A85'], true)) {
                $data = $this->ascii85($data);
            } else {
                return null;                     // DCTDecode (a picture), LZW, ...: not text
            }
        }
        // a Flate stream with a predictor (xref / object streams of some writers): undo the PNG "up" predictor
        if (preg_match('~/Predictor\s+(\d+)~', $dict, $pr) && (int) $pr[1] >= 10 && preg_match('~/Columns\s+(\d+)~', $dict, $col)) {
            $data = $this->pngPredictor($data, (int) $col[1]);
        }

        return $data;
    }

    private function ascii85(string $s): string
    {
        $s = preg_replace('/\s+/', '', preg_replace('/^<~|~>.*$/s', '', $s));
        $out = '';
        $buf = [];
        foreach (str_split($s) as $ch) {
            if ($ch === 'z' && !$buf) { $out .= "\0\0\0\0"; continue; }
            $buf[] = ord($ch) - 33;
            if (count($buf) === 5) {
                $n = 0; foreach ($buf as $b) { $n = $n * 85 + $b; }
                $out .= pack('N', $n & 0xFFFFFFFF); $buf = [];
            }
        }
        if ($buf) {
            $k = count($buf); while (count($buf) < 5) { $buf[] = 84; }
            $n = 0; foreach ($buf as $b) { $n = $n * 85 + $b; }
            $out .= substr(pack('N', $n & 0xFFFFFFFF), 0, $k - 1);
        }

        return $out;
    }

    private function pngPredictor(string $data, int $cols): string
    {
        $rowLen = $cols + 1; $out = ''; $prev = str_repeat("\0", $cols);
        foreach (str_split($data, $rowLen) as $row) {
            if (strlen($row) < $rowLen) { break; }
            $type = ord($row[0]); $cur = substr($row, 1);
            if ($type === 2) { for ($i = 0; $i < $cols; $i++) { $cur[$i] = chr((ord($cur[$i]) + ord($prev[$i])) & 255); } }
            elseif ($type === 1) { for ($i = 1; $i < $cols; $i++) { $cur[$i] = chr((ord($cur[$i]) + ord($cur[$i - 1])) & 255); } }
            $out .= $cur; $prev = $cur;
        }

        return $out;
    }

    // ---- dictionary helpers

    /** the raw text of the value of /Key in a dictionary: a reference, a name, a number, a [..] or a nested << .. >> */
    private function get(string $dict, string $key): ?string
    {
        if (!preg_match('~/' . preg_quote($key, '~') . '(?![A-Za-z0-9])\s*~', $dict, $m, PREG_OFFSET_CAPTURE)) { return null; }
        $i = $m[0][1] + strlen($m[0][0]);
        $c = $dict[$i] ?? '';
        if ($c === '<' && ($dict[$i + 1] ?? '') === '<') { return $this->balanced($dict, $i, '<<', '>>'); }
        if ($c === '[') { return $this->balanced($dict, $i, '[', ']'); }
        if (preg_match('~\G(\d+\s+\d+\s+R|/[^\s/<>\[\]()]+|[-\d.]+|\([^)]*\))~', $dict, $v, 0, $i)) { return $v[1]; }

        return null;
    }

    private function balanced(string $s, int $i, string $open, string $close): string
    {
        $depth = 0; $n = strlen($s); $ol = strlen($open); $cl = strlen($close);
        for ($j = $i; $j < $n; $j++) {
            if (substr($s, $j, $ol) === $open) { $depth++; $j += $ol - 1; }
            elseif (substr($s, $j, $cl) === $close) { $depth--; $j += $cl - 1; if ($depth === 0) { return substr($s, $i, $j - $i + 1); } }
        }

        return substr($s, $i);
    }

    private function refNo(?string $v): ?int
    {
        return $v !== null && preg_match('~^(\d+)\s+\d+\s+R~', trim($v), $m) ? (int) $m[1] : null;
    }

    /** a value that is either an inline dictionary or a reference to one */
    private function dictOf(?string $v): ?string
    {
        if ($v === null) { return null; }
        if (str_starts_with(ltrim($v), '<<')) { return $v; }
        $n = $this->refNo($v);

        return $n !== null && isset($this->objs[$n]) ? $this->objs[$n]['d'] : null;
    }

    // ---- pages

    /** @return int[] page object numbers in reading order */
    private function pageNumbers(): array
    {
        $pages = [];
        $root = null;
        foreach ($this->objs as $n => $o) {
            if (preg_match('~/Type\s*/Pages\b~', $o['d']) && !preg_match('~/Parent\s+\d+~', $o['d'])) { $root = $n; break; }
        }
        if ($root !== null) { $this->walk($root, $pages, 0); }
        if (!$pages) {
            foreach ($this->objs as $n => $o) { if (preg_match('~/Type\s*/Page\b(?!s)~', $o['d'])) { $pages[] = $n; } }
            sort($pages);
        }

        return array_values(array_unique($pages));
    }

    private function walk(int $n, array &$pages, int $depth): void
    {
        if ($depth > 12 || !isset($this->objs[$n])) { return; }
        $d = $this->objs[$n]['d'];
        if (preg_match('~/Type\s*/Page\b(?!s)~', $d)) { $pages[] = $n; return; }
        $kids = $this->get($d, 'Kids');
        if ($kids !== null && preg_match_all('~(\d+)\s+\d+\s+R~', $kids, $k)) {
            foreach ($k[1] as $kid) { $this->walk((int) $kid, $pages, $depth + 1); }
        }
    }

    /** the fonts of a page (name => font object number), looking up the page tree for inherited resources */
    private function pageFonts(int $pageNo): array
    {
        $n = $pageNo; $res = null;
        for ($hops = 0; $hops < 8 && $n !== null && isset($this->objs[$n]); $hops++) {
            $d = $this->objs[$n]['d'];
            $res = $this->dictOf($this->get($d, 'Resources'));
            if ($res !== null) { break; }
            $n = $this->refNo($this->get($d, 'Parent'));
        }
        if ($res === null) { return []; }
        $fonts = $this->dictOf($this->get($res, 'Font'));
        $map = [];
        if ($fonts !== null && preg_match_all('~/([^\s/<>\[\]()]+)\s+(\d+)\s+\d+\s+R~', $fonts, $m, PREG_SET_ORDER)) {
            foreach ($m as $f) { $map[$f[1]] = (int) $f[2]; }
        }

        return $map;
    }

    private function pageText(int $pageNo): string
    {
        $d = $this->objs[$pageNo]['d'];
        $contents = $this->get($d, 'Contents');
        if ($contents === null) { return ''; }
        $streams = [];
        if (preg_match_all('~(\d+)\s+\d+\s+R~', $contents, $m)) {
            foreach ($m[1] as $n) { if (isset($this->objs[(int) $n]) && $this->objs[(int) $n]['s'] !== null) { $streams[] = $this->objs[(int) $n]['s']; } }
        }

        return $this->contentText(implode("\n", $streams), $this->pageFonts($pageNo), []);
    }

    // ---- fonts

    /** @return array{composite:bool, width:int, map:?array} */
    private function font(int $no): array
    {
        if (isset($this->fontCache[$no])) { return $this->fontCache[$no]; }
        $d = $this->objs[$no]['d'] ?? '';
        $composite = (bool) preg_match('~/Subtype\s*/Type0~', $d) || (bool) preg_match('~/Encoding\s*/Identity-[HV]~', $d);
        $map = null; $width = $composite ? 2 : 1;
        $tu = $this->refNo($this->get($d, 'ToUnicode'));
        if ($tu !== null && isset($this->objs[$tu]) && $this->objs[$tu]['s'] !== null) {
            [$map, $cw] = $this->cmap($this->objs[$tu]['s']);
            if ($cw) { $width = $cw; }
        }

        return $this->fontCache[$no] = ['composite' => $composite, 'width' => $width, 'map' => $map];
    }

    /** @return array{0:array<int,string>,1:?int} code => text, and the byte width of a code */
    private function cmap(string $cm): array
    {
        $map = []; $width = null;
        if (preg_match('~begincodespacerange\s*<([0-9A-Fa-f]+)>~', $cm, $cs)) { $width = max(1, (int) (strlen($cs[1]) / 2)); }
        $u = fn (string $hex) => mb_convert_encoding((string) hex2bin(strlen($hex) % 2 ? $hex . '0' : $hex), 'UTF-8', 'UTF-16BE');

        if (preg_match_all('~beginbfchar(.*?)endbfchar~s', $cm, $blocks)) {
            foreach ($blocks[1] as $b) {
                if (preg_match_all('~<([0-9A-Fa-f]+)>\s*<([0-9A-Fa-f]+)>~', $b, $p, PREG_SET_ORDER)) {
                    foreach ($p as $x) { $map[hexdec($x[1])] = $u($x[2]); }
                }
            }
        }
        if (preg_match_all('~beginbfrange(.*?)endbfrange~s', $cm, $blocks)) {
            foreach ($blocks[1] as $b) {
                if (!preg_match_all('~<([0-9A-Fa-f]+)>\s*<([0-9A-Fa-f]+)>\s*(\[[^\]]*\]|<[0-9A-Fa-f]+>)~', $b, $p, PREG_SET_ORDER)) { continue; }
                foreach ($p as $x) {
                    $lo = hexdec($x[1]); $hi = min(hexdec($x[2]), $lo + 65535);
                    if ($x[3][0] === '[') {
                        preg_match_all('~<([0-9A-Fa-f]+)>~', $x[3], $arr);
                        foreach ($arr[1] as $i => $hex) { if ($lo + $i <= $hi) { $map[$lo + $i] = $u($hex); } }
                    } else {
                        $base = trim($x[3], '<>');
                        $unit = hexdec(substr($base, -4));
                        $head = substr($base, 0, -4);
                        for ($c = $lo; $c <= $hi; $c++) { $map[$c] = $u($head . str_pad(dechex(($unit + $c - $lo) & 0xFFFF), 4, '0', STR_PAD_LEFT)); }
                    }
                }
            }
        }

        return [$map, $width];
    }

    // ---- content stream

    /**
     * The text of one page. Chunks keep the order they are drawn in (which is the order of a CV's blocks), a new line
     * starts when the text moves down the page, and a space is added when the next chunk is on the same line.
     */
    private function contentText(string $c, array $fonts, array $unused): string
    {
        $out = '';
        $ctm = [1, 0, 0, 1, 0, 0]; $stack = [];
        $tm = $tlm = [1, 0, 0, 1, 0, 0]; $leading = 0.0; $size = 10.0; $font = null;
        $lastY = null; $moved = false; $any = false;

        $y = function () use (&$tm, &$ctm) { return $tm[4] * $ctm[1] + $tm[5] * $ctm[3] + $ctm[5]; };
        $show = function (string $raw, bool $hex) use (&$out, &$font, &$lastY, &$moved, &$any, &$size, $y) {
            $this->sawText = true;
            $text = $this->decodeText($raw, $hex, $font);
            if ($text === '') { return; }
            $cy = $y();
            if ($any && $lastY !== null && abs($cy - $lastY) > max(1.5, $size * 0.45)) { $out = rtrim($out, ' ') . "\n"; }
            elseif ($any && $moved && $out !== '' && !str_ends_with($out, "\n") && !str_ends_with($out, ' ')) { $out .= ' '; }
            $out .= $text; $lastY = $cy; $moved = false; $any = true;
        };

        foreach ($this->tokens($c) as [$op, $args]) {
            switch ($op) {
                case 'q': $stack[] = $ctm; break;
                case 'Q': if ($stack) { $ctm = array_pop($stack); } break;
                case 'cm': if (count($args) === 6) { $ctm = $this->mul($this->nums($args), $ctm); } break;
                case 'BT': $tm = $tlm = [1, 0, 0, 1, 0, 0]; break;
                case 'Tf':
                    $name = isset($args[0]) && is_array($args[0]) && $args[0]['n'] ? $args[0]['n'] : null;
                    $font = $name !== null && isset($fonts[$name]) ? $this->font($fonts[$name]) : null;
                    $size = isset($args[1]) && is_numeric($args[1]) ? abs((float) $args[1]) ?: 10.0 : $size;
                    break;
                case 'TL': $leading = isset($args[0]) ? (float) $args[0] : 0.0; break;
                case 'Tm': if (count($args) === 6) { $tm = $tlm = $this->nums($args); $moved = true; } break;
                case 'Td': case 'TD':
                    if (count($args) === 2) {
                        if ($op === 'TD') { $leading = -(float) $args[1]; }
                        $tlm = $this->mul([1, 0, 0, 1, (float) $args[0], (float) $args[1]], $tlm); $tm = $tlm; $moved = true;
                    }
                    break;
                case 'T*': $tlm = $this->mul([1, 0, 0, 1, 0, -$leading], $tlm); $tm = $tlm; $moved = true; break;
                case 'Tj': if ($args && is_array($args[0]) && isset($args[0]['s'])) { $show($args[0]['s'], $args[0]['h']); } break;
                case "'": case '"':
                    $tlm = $this->mul([1, 0, 0, 1, 0, -$leading], $tlm); $tm = $tlm; $moved = true;
                    $last = end($args);
                    if (is_array($last) && isset($last['s'])) { $show($last['s'], $last['h']); }
                    break;
                case 'TJ':
                    if ($args && is_array($args[0]) && isset($args[0]['a'])) {
                        foreach ($args[0]['a'] as $part) {
                            if (is_array($part)) { $show($part['s'], $part['h']); }
                            elseif ($part < -250 && $out !== '' && !str_ends_with($out, ' ') && !str_ends_with($out, "\n")) { $out .= ' '; }
                        }
                    }
                    break;
            }
            if (strlen($out) > 1_500_000) { break; }
        }

        return $out;
    }

    private function decodeText(string $raw, bool $hex, ?array $font): string
    {
        $bytes = $hex ? (string) hex2bin(preg_replace('/[^0-9A-Fa-f]/', '', $raw) . (strlen(preg_replace('/[^0-9A-Fa-f]/', '', $raw)) % 2 ? '0' : '')) : $raw;
        if ($font !== null && $font['map'] !== null) {
            $w = $font['width']; $t = '';
            foreach (str_split($bytes, $w) as $code) {
                if (strlen($code) < $w) { break; }
                $k = $w === 1 ? ord($code) : ($w === 2 ? (ord($code[0]) << 8 | ord($code[1])) : hexdec(bin2hex($code)));
                $t .= $font['map'][$k] ?? '';
            }

            return $t;
        }
        if ($font !== null && $font['composite']) { return ''; }          // 2-byte glyph ids and no table: no way to read them
        return mb_check_encoding($bytes, 'UTF-8') ? $bytes : mb_convert_encoding($bytes, 'UTF-8', 'Windows-1252');
    }

    // ---- tokenizer and matrices

    /** @return \Generator<int, array{0:string, 1:array}> operator and its operands */
    private function tokens(string $c): \Generator
    {
        $n = strlen($c); $i = 0; $args = [];
        while ($i < $n) {
            $ch = $c[$i];
            if (ctype_space($ch) || $ch === "\0") { $i++; continue; }
            if ($ch === '%') { $e = strcspn($c, "\r\n", $i); $i += $e; continue; }
            if ($ch === '(') { [$s, $i] = $this->literal($c, $i); $args[] = ['s' => $s, 'h' => false]; continue; }
            if ($ch === '<' && ($c[$i + 1] ?? '') === '<') { $i = $this->skipDict($c, $i); continue; }
            if ($ch === '<') { $e = strpos($c, '>', $i); $e = $e === false ? $n : $e; $args[] = ['s' => substr($c, $i + 1, $e - $i - 1), 'h' => true]; $i = $e + 1; continue; }
            if ($ch === '[') {
                $arr = []; $i++;
                while ($i < $n && $c[$i] !== ']') {
                    if (ctype_space($c[$i])) { $i++; continue; }
                    if ($c[$i] === '(') { [$s, $i] = $this->literal($c, $i); $arr[] = ['s' => $s, 'h' => false]; }
                    elseif ($c[$i] === '<') { $e = strpos($c, '>', $i); $e = $e === false ? $n : $e; $arr[] = ['s' => substr($c, $i + 1, $e - $i - 1), 'h' => true]; $i = $e + 1; }
                    else { $l = strcspn($c, " \t\r\n\0]([<", $i); $l = max(1, $l); $tok = substr($c, $i, $l); if (is_numeric($tok)) { $arr[] = (float) $tok; } $i += $l; }
                }
                $i++; $args[] = ['a' => $arr]; continue;
            }
            if ($ch === '/') { $l = strcspn($c, " \t\r\n\0/[]()<>", $i + 1); $args[] = ['n' => substr($c, $i + 1, $l)]; $i += $l + 1; continue; }
            $l = max(1, strcspn($c, " \t\r\n\0/[]()<>%", $i));
            $tok = substr($c, $i, $l); $i += $l;
            if (is_numeric($tok)) { $args[] = (float) $tok; continue; }
            if ($tok === 'BI') {                                         // an inline image: skip to its end
                $e = preg_match('/\sEI(?=\s|$)/', $c, $mm, PREG_OFFSET_CAPTURE, $i) ? $mm[0][1] + 3 : $n;
                $i = $e; $args = []; continue;
            }
            yield [$tok, $args];
            $args = [];
        }
    }

    /** @return array{0:string,1:int} the text of a (literal string) and the position after it */
    private function literal(string $c, int $i): array
    {
        $n = strlen($c); $depth = 0; $s = '';
        for ($j = $i; $j < $n; $j++) {
            $ch = $c[$j];
            if ($ch === '\\') {
                $nx = $c[$j + 1] ?? '';
                if ($nx >= '0' && $nx <= '7') { preg_match('/\G[0-7]{1,3}/', $c, $o, 0, $j + 1); $s .= chr(octdec($o[0]) & 255); $j += strlen($o[0]); }
                else { $s .= match ($nx) { 'n' => "\n", 'r' => "\r", 't' => "\t", 'b' => "\x08", 'f' => "\x0C", "\n" => '', "\r" => '', default => $nx }; $j++; }
                continue;
            }
            if ($ch === '(') { if ($depth++ > 0) { $s .= $ch; } continue; }
            if ($ch === ')') { if (--$depth === 0) { return [$s, $j + 1]; } $s .= $ch; continue; }
            $s .= $ch;
        }

        return [$s, $n];
    }

    private function skipDict(string $c, int $i): int
    {
        $depth = 0; $n = strlen($c);
        for ($j = $i; $j < $n - 1; $j++) {
            if ($c[$j] === '<' && $c[$j + 1] === '<') { $depth++; $j++; }
            elseif ($c[$j] === '>' && $c[$j + 1] === '>') { $depth--; $j++; if ($depth === 0) { return $j + 1; } }
        }

        return $n;
    }

    /** @param array<int,mixed> $a @return float[] */
    private function nums(array $a): array { return array_map(fn ($v) => is_numeric($v) ? (float) $v : 0.0, $a); }

    /** matrix product a x b for PDF [a b c d e f] matrices */
    private function mul(array $a, array $b): array
    {
        return [
            $a[0] * $b[0] + $a[1] * $b[2], $a[0] * $b[1] + $a[1] * $b[3],
            $a[2] * $b[0] + $a[3] * $b[2], $a[2] * $b[1] + $a[3] * $b[3],
            $a[4] * $b[0] + $a[5] * $b[2] + $b[4], $a[4] * $b[1] + $a[5] * $b[3] + $b[5],
        ];
    }
}
