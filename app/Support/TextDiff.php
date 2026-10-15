<?php

namespace App\Support;

/**
 * Perbandingan teks sederhana untuk Riwayat Versi artikel (tanpa dependensi tambahan).
 * Dua tahap agar tetap ringan untuk artikel panjang: paragraf dibandingkan dulu,
 * lalu paragraf yang berubah dibandingkan per kata.
 */
final class TextDiff
{
    /** Batas sel tabel LCS per perbandingan kata; di atas ini paragraf ditampilkan utuh (hapus + tambah). */
    private const MAX_WORD_CELLS = 250_000;

    /**
     * Ubah HTML editor menjadi daftar paragraf teks polos.
     *
     * @return list<string>
     */
    public static function paragraphs(?string $html): array
    {
        $text = preg_replace('~<\s*(br|/p|/h[1-6]|/li|/blockquote|/div|/tr|/figcaption)\b[^>]*>~i', "\n", (string) $html);
        $text = html_entity_decode(strip_tags((string) $text), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return array_values(array_filter(
            array_map(fn (string $line) => trim(preg_replace('/[ \t\x{00A0}]+/u', ' ', $line)), explode("\n", $text)),
            fn (string $line) => $line !== ''
        ));
    }

    /**
     * Bandingkan dua kumpulan paragraf. Setiap baris hasil: type same|add|del|change|skip dan html siap tampil
     * (sudah di-escape; bagian yang berubah dibungkus <del>/<ins>). Paragraf sama yang panjang diringkas jadi "skip".
     *
     * @param  list<string>  $old
     * @param  list<string>  $new
     * @return list<array{type: string, html: string, count?: int}>
     */
    public static function compare(array $old, array $new, int $context = 1): array
    {
        $rows = [];
        $dels = [];
        $adds = [];

        $flush = function () use (&$rows, &$dels, &$adds) {
            // Pasangkan paragraf yang dihapus & ditambah berurutan sebagai "diubah" agar terlihat per kata
            $pairs = min(count($dels), count($adds));
            for ($i = 0; $i < $pairs; $i++) {
                $rows[] = ['type' => 'change', 'html' => self::words($dels[$i], $adds[$i])];
            }
            foreach (array_slice($dels, $pairs) as $text) {
                $rows[] = ['type' => 'del', 'html' => e($text)];
            }
            foreach (array_slice($adds, $pairs) as $text) {
                $rows[] = ['type' => 'add', 'html' => e($text)];
            }
            $dels = $adds = [];
        };

        foreach (self::ops($old, $new) as [$op, $text]) {
            if ($op === '=') {
                $flush();
                $rows[] = ['type' => 'same', 'html' => e($text)];
            } elseif ($op === '-') {
                $dels[] = $text;
            } else {
                $adds[] = $text;
            }
        }
        $flush();

        return self::collapse($rows, $context);
    }

    /** Perbandingan per kata dalam satu teks; hasil HTML dengan <del>/<ins>. */
    public static function words(string $old, string $new): string
    {
        $a = preg_split('/(\s+)/u', $old, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];
        $b = preg_split('/(\s+)/u', $new, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($a) * count($b) > self::MAX_WORD_CELLS) {
            return ($old !== '' ? '<del>' . e($old) . '</del> ' : '') . ($new !== '' ? '<ins>' . e($new) . '</ins>' : '');
        }

        $html = '';
        $buffer = ['-' => '', '+' => ''];
        $emit = function () use (&$html, &$buffer) {
            if ($buffer['-'] !== '') {
                $html .= '<del>' . e($buffer['-']) . '</del>';
            }
            if ($buffer['+'] !== '') {
                $html .= '<ins>' . e($buffer['+']) . '</ins>';
            }
            $buffer = ['-' => '', '+' => ''];
        };

        foreach (self::ops($a, $b) as [$op, $token]) {
            if ($op === '=') {
                $emit();
                $html .= e($token);
            } else {
                $buffer[$op] .= $token;
            }
        }
        $emit();

        return $html;
    }

    /**
     * Operasi edit berbasis LCS: list of ['=', item] | ['-', item] | ['+', item].
     *
     * @param  list<string>  $a
     * @param  list<string>  $b
     * @return list<array{0: string, 1: string}>
     */
    private static function ops(array $a, array $b): array
    {
        // Potong awalan & akhiran yang sama dulu: kasus umum (sedikit perubahan) jadi sangat murah
        $prefix = [];
        while ($a && $b && $a[0] === $b[0]) {
            $prefix[] = ['=', array_shift($a)];
            array_shift($b);
        }
        $suffix = [];
        while ($a && $b && end($a) === end($b)) {
            array_unshift($suffix, ['=', array_pop($a)]);
            array_pop($b);
        }

        $n = count($a);
        $m = count($b);
        $lcs = array_fill(0, $n + 1, array_fill(0, $m + 1, 0));
        for ($i = $n - 1; $i >= 0; $i--) {
            for ($j = $m - 1; $j >= 0; $j--) {
                $lcs[$i][$j] = $a[$i] === $b[$j] ? $lcs[$i + 1][$j + 1] + 1 : max($lcs[$i + 1][$j], $lcs[$i][$j + 1]);
            }
        }

        $ops = [];
        $i = $j = 0;
        while ($i < $n && $j < $m) {
            if ($a[$i] === $b[$j]) {
                $ops[] = ['=', $a[$i++]];
                $j++;
            } elseif ($lcs[$i + 1][$j] >= $lcs[$i][$j + 1]) {
                $ops[] = ['-', $a[$i++]];
            } else {
                $ops[] = ['+', $b[$j++]];
            }
        }
        while ($i < $n) {
            $ops[] = ['-', $a[$i++]];
        }
        while ($j < $m) {
            $ops[] = ['+', $b[$j++]];
        }

        return array_merge($prefix, $ops, $suffix);
    }

    /**
     * Ringkas deretan paragraf "same" yang jauh dari perubahan menjadi satu baris "skip".
     *
     * @param  list<array{type: string, html: string}>  $rows
     * @return list<array{type: string, html: string, count?: int}>
     */
    private static function collapse(array $rows, int $context): array
    {
        $changed = array_keys(array_filter($rows, fn ($row) => $row['type'] !== 'same'));
        $keep = fn (int $i) => collect($changed)->contains(fn ($c) => abs($c - $i) <= $context);

        $result = [];
        $skipped = 0;
        foreach ($rows as $i => $row) {
            if ($row['type'] === 'same' && ! $keep($i)) {
                $skipped++;

                continue;
            }
            if ($skipped) {
                $result[] = ['type' => 'skip', 'html' => '', 'count' => $skipped];
                $skipped = 0;
            }
            $result[] = $row;
        }
        if ($skipped) {
            $result[] = ['type' => 'skip', 'html' => '', 'count' => $skipped];
        }

        return $result;
    }
}
