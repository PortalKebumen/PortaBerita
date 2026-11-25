<?php

namespace App\Support;

/**
 * Olah HTML isi artikel (hasil TinyMCE) sebelum ditampilkan.
 */
final class ArticleContent
{
    /** Tag pembungkus yang tidak boleh disisipi di tengahnya (paragraf di dalamnya bukan paragraf utama). */
    private const CONTAINERS = ['blockquote', 'figure', 'table', 'ul', 'ol', 'div'];

    /**
     * Sisipkan $snippet setelah paragraf utama ke-$after, hanya bila artikel punya minimal $minParagraphs
     * paragraf (artikel pendek tidak disisipi agar tidak mengganggu). Paragraf di dalam kutipan, tabel,
     * daftar, atau figure tidak dihitung.
     */
    public static function insertAfterParagraph(string $html, string $snippet, int $after = 3, int $minParagraphs = 5): string
    {
        if ($snippet === '' || ! preg_match_all('#</p\s*>#i', $html, $matches, PREG_OFFSET_CAPTURE)) {
            return $html;
        }

        $topLevel = [];
        foreach ($matches[0] as [$tag, $offset]) {
            $end = $offset + strlen($tag);
            if (self::isTopLevel(substr($html, 0, $end))) {
                $topLevel[] = $end;
            }
        }

        if (count($topLevel) < max($after, $minParagraphs)) {
            return $html;
        }

        $position = $topLevel[$after - 1];

        return substr($html, 0, $position) . $snippet . substr($html, $position);
    }

    private static function isTopLevel(string $before): bool
    {
        foreach (self::CONTAINERS as $tag) {
            $open = preg_match_all("#<{$tag}[\\s>]#i", $before);
            $close = preg_match_all("#</{$tag}\\s*>#i", $before);
            if ($open > $close) {
                return false;
            }
        }

        return true;
    }
}
