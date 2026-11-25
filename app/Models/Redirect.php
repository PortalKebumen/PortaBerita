<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pengalihan 301 dari alamat lama ke alamat baru, agar tautan yang sudah dibagikan atau terindeks Google
 * tidak berujung 404. Dibuat otomatis saat slug artikel terbit diganti, atau manual dari Admin > Pengaturan.
 * old_url disimpan sebagai path (mis. /artikel/judul-lama); new_url boleh path atau URL lengkap.
 */
class Redirect extends Model
{
    protected $fillable = ['old_url', 'new_url', 'status_code'];

    protected $casts = ['hits' => 'integer', 'status_code' => 'integer'];

    /** Seragamkan alamat menjadi path berawalan "/" tanpa domain, query, dan garis miring di akhir. */
    public static function normalizePath(string $url): string
    {
        $path = parse_url(trim($url), PHP_URL_PATH) ?: '/';

        return '/' . trim(rawurldecode($path), '/');
    }

    /** Cari pengalihan untuk path yang diminta dan catat jumlah pemakaiannya. */
    public static function resolve(string $path): ?self
    {
        $redirect = static::where('old_url', static::normalizePath($path))->first();
        $redirect?->increment('hits');

        return $redirect;
    }

    /** Catat pengalihan artikel saat slug berganti; rantai lama (A→B) ikut diarahkan ke slug terbaru (A→C). */
    public static function forArticleSlugChange(string $oldSlug, string $newSlug): void
    {
        $old = '/artikel/' . $oldSlug;
        $new = '/artikel/' . $newSlug;

        static::where('new_url', $old)->update(['new_url' => $new]);
        static::where('old_url', $new)->delete(); // slug dikembalikan ke nama lama: hindari pengalihan berputar
        static::updateOrCreate(['old_url' => $old], ['new_url' => $new, 'status_code' => 301]);
    }
}
