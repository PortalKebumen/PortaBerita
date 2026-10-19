<?php

namespace App\Support;

/**
 * Batas ukuran unggahan yang benar-benar berlaku: batas aplikasi (5 MB) atau batas PHP di server
 * (upload_max_filesize / post_max_size), mana yang lebih kecil. Dipakai untuk cek ukuran di browser
 * sebelum mengunggah, teks petunjuk di form, dan pesan error, agar semuanya menyebut angka yang sama.
 */
final class UploadLimit
{
    /** Batas aplikasi dalam KB (aturan validasi "max:"). */
    public const APP_MAX_KB = 5120;

    public static function maxBytes(): int
    {
        $limits = [self::APP_MAX_KB * 1024];

        foreach (['upload_max_filesize', 'post_max_size'] as $key) {
            $bytes = self::iniBytes((string) ini_get($key));
            if ($bytes > 0) {
                $limits[] = $bytes;
            }
        }

        return min($limits);
    }

    /** Contoh: "5 MB", "2 MB", "1,5 MB". */
    public static function label(?int $bytes = null): string
    {
        $mb = ($bytes ?? self::maxBytes()) / 1024 / 1024;

        return rtrim(rtrim(number_format($mb, 1, ',', ''), '0'), ',').' MB';
    }

    /** true bila batas PHP server lebih kecil dari batas aplikasi (perlu dinaikkan di hosting). */
    public static function limitedByServer(): bool
    {
        return self::maxBytes() < self::APP_MAX_KB * 1024;
    }

    /** "2M" -> 2097152, "512K" -> 524288, "0"/"" -> 0 (tanpa batas). */
    private static function iniBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }

        $number = (float) $value;

        return (int) match (strtoupper(substr($value, -1))) {
            'G' => $number * 1024 * 1024 * 1024,
            'M' => $number * 1024 * 1024,
            'K' => $number * 1024,
            default => $number,
        };
    }
}
