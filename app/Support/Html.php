<?php

namespace App\Support;

/**
 * Defense-in-depth sanitizer for admin rich-editor HTML rendered on public pages.
 */
final class Html
{
    private const ALLOWED_TAGS = '<p><br><strong><b><em><i><u><s><ul><ol><li><h2><h3><blockquote><a><img><figure><figcaption><hr>';

    public static function clean(?string $html): string
    {
        $html = strip_tags((string) $html, self::ALLOWED_TAGS);

        // Remove event handlers (onclick=...) and style attributes.
        $html = preg_replace('/\s(on\w+|style)\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);

        // Neutralize javascript:/data: URLs in href/src.
        $html = preg_replace('/\s(href|src)\s*=\s*(["\']?)\s*(javascript|vbscript|data):[^"\'\s>]*\2/i', ' $1="#"', $html);

        // External links open safely.
        return preg_replace('/<a\s(?![^>]*\brel=)/i', '<a rel="noopener nofollow" ', $html);
    }
}
