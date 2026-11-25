<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Cleans the rich-text body of a reply before it is stored and emailed: a few formatting tags only, no scripts,
 * styles, event handlers or javascript: links. Links keep href (http, https, mailto) and open safely.
 */
class HtmlSanitizer
{
    private const ALLOWED = ['p', 'br', 'b', 'strong', 'i', 'em', 'u', 'ul', 'ol', 'li', 'a', 'blockquote', 'h3', 'h4', 'div', 'span'];

    public static function clean(string $html): string
    {
        $html = trim($html);
        if ($html === '') { return ''; }
        // drop dangerous elements together with their content before anything is parsed
        $html = preg_replace('#<(script|style|iframe|object|embed|form|svg|math|link|meta)\b[^>]*>.*?</\1\s*>#is', '', $html);
        $html = preg_replace('#<(script|style|iframe|object|embed|form|svg|math|link|meta)\b[^>]*/?>#is', '', $html);

        $doc = new DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><div id="nx-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $root = $doc->getElementById('nx-root');
        if (!$root) { return e(strip_tags($html)); }
        self::walk($root);

        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) { $out .= $doc->saveHTML($child); }

        return trim($out);
    }

    /** Plain-text version for the text/plain part and for previews. */
    public static function toText(string $html): string
    {
        $t = preg_replace(['#<br\s*/?>#i', '#</(p|div|li|h3|h4|blockquote)>#i'], "\n", $html);

        return trim(html_entity_decode(strip_tags((string) $t), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private static function walk(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);
                if (!in_array($tag, self::ALLOWED, true)) {
                    self::walk($child);                                   // keep its text, drop the tag
                    while ($child->firstChild) { $node->insertBefore($child->firstChild, $child); }
                    $node->removeChild($child);
                    continue;
                }
                $href = $tag === 'a' ? trim((string) $child->getAttribute('href')) : '';
                foreach (iterator_to_array($child->attributes) as $attr) { $child->removeAttribute($attr->name); }
                if ($tag === 'a') {
                    if (preg_match('#^(https?://|mailto:)#i', $href)) {
                        $child->setAttribute('href', $href);
                        $child->setAttribute('rel', 'noopener noreferrer');
                        $child->setAttribute('target', '_blank');
                    }
                }
                self::walk($child);
            } elseif ($child->nodeType === XML_COMMENT_NODE) {
                $node->removeChild($child);
            }
        }
    }
}
