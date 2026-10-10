<?php

namespace App\Models;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SiteSetting
{
    public const CACHE_KEY = 'site_settings.values';

    public static function defaults(): array
    {
        return [
            'site_name' => 'PortalKebumen.com',
            'tagline' => 'Kabar Terkini Seputar Kebumen',
            'editorial_email' => 'redaksi@portalkebumen.com',
            'phone' => null,
            'address' => null,
            'timezone' => 'Asia/Jakarta',
            'locale' => 'id',
            'logo' => null,        // path relatif di disk public
            'meta_title' => 'PortalKebumen.com - Kabar Terkini Seputar Kebumen',
            'meta_description' => 'Berita terkini, terpercaya, dan mendalam seputar Kabupaten Kebumen - pemerintahan, ekonomi, olahraga, dan potensi daerah.',
            'og_image' => null,    // path relatif di disk public
            'ga_id' => null,
            'gsc_verification' => null,
            'facebook' => null,
            'instagram' => null,
            'x_twitter' => null,
            'youtube' => null,
            'whatsapp' => null,
        ];
    }

    /** Semua nilai (default digabung dengan isi tabel settings) + logo_url & og_image_url. */
    public static function values(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            $stored = DB::table('settings')->pluck('value', 'key')->all();
            $values = array_merge(static::defaults(), array_intersect_key($stored, static::defaults()));

            foreach ($values as $k => $v) {
                $values[$k] = ($v === '' ? null : $v);
            }

            $values['logo_url'] = $values['logo'] ? Storage::disk('public')->url($values['logo']) : null;
            $values['og_image_url'] = $values['og_image'] ? Storage::disk('public')->url($values['og_image']) : null;

            return $values;
        });
    }

    /** Pakai di mana saja: SiteSetting::get('site_name', 'Fallback') */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = static::values()[$key] ?? null;

        return filled($value) ? $value : $default;
    }

    /** Simpan beberapa key sekaligus. */
    public static function setMany(array $data): void
    {
        foreach ($data as $key => $value) {
            DB::table('settings')->updateOrInsert(['key' => $key], ['value' => $value]);
        }

        static::flushCache();
    }

    /** Hapus hanya key milik halaman Pengaturan ini (key lain aman). */
    public static function reset(): void
    {
        DB::table('settings')->whereIn('key', array_keys(static::defaults()))->delete();
        static::flushCache();
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}