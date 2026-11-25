<?php

namespace App\Support;

use App\Services\SystemNotifier;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

/**
 * Kirim email + notifikasi lonceng ke admin (izin settings.update) saat terjadi error di production.
 * Error yang sama hanya dilaporkan sekali per 6 jam, dan maksimal 3 email error per jam agar tidak
 * menghabiskan kuota email hosting (±10 email/jam). Detail lengkap tetap di storage/logs/laravel.log.
 */
final class ErrorNotifier
{
    private const MAX_PER_HOUR = 3;

    private static bool $sending = false;

    public static function notify(Throwable $e): void
    {
        // Hanya production; cegah perulangan bila pengiriman notifikasi sendiri memicu error
        if (self::$sending || ! app()->isProduction()) {
            return;
        }

        self::$sending = true;

        try {
            $where = Str::after($e->getFile(), base_path() . DIRECTORY_SEPARATOR) . ':' . $e->getLine();
            $key = 'error:' . md5(get_class($e) . '|' . $where);

            // Error yang sama cukup sekali per 6 jam; lalu batas jumlah email per jam
            if (! Cache::add('error-notify:' . $key, true, now()->addHours(6))) {
                return;
            }
            $hourKey = 'error-notify:count:' . now()->format('YmdH');
            Cache::add($hourKey, 0, now()->addHour());
            if (Cache::increment($hourKey) > self::MAX_PER_HOUR) {
                return;
            }

            $url = app()->runningInConsole() ? 'perintah terjadwal / konsol' : request()->fullUrl();

            SystemNotifier::alert(
                key: $key,
                title: 'Terjadi error di situs',
                message: class_basename($e) . ': ' . Str::limit($e->getMessage(), 300)
                    . "\nAlamat: {$url}"
                    . "\nLokasi: {$where}"
                    . "\nWaktu: " . now()->translatedFormat('d M Y, H:i') . ' WIB'
                    . "\nDetail lengkap ada di storage/logs/laravel.log di server.",
                permission: 'settings.update',
            );
        } catch (Throwable) {
            // Gagal mengirim notifikasi tidak boleh mengganggu situs; error aslinya tetap tercatat di log
        } finally {
            self::$sending = false;
        }
    }
}
