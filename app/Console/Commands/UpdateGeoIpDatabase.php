<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use MaxMind\Db\Reader;
use Throwable;

/**
 * Unduh / perbarui database lokasi IP (DB-IP City Lite) untuk statistik lokasi pembaca.
 * DB-IP merilis file baru tiap awal bulan; bila file bulan ini belum ada, dipakai bulan sebelumnya.
 */
class UpdateGeoIpDatabase extends Command
{
    protected $signature = 'geoip:update {--force : Unduh ulang walaupun file masih baru}';

    protected $description = 'Unduh atau perbarui database lokasi IP (DB-IP City Lite) untuk Laporan Analitik';

    public function handle(): int
    {
        $path = config('analytics.geoip.path');

        if (! $this->option('force') && is_file($path) && filemtime($path) > now()->subDays(25)->getTimestamp()) {
            $this->info('Database lokasi masih baru (' . date('d M Y', filemtime($path)) . '), tidak perlu diunduh. Pakai --force untuk mengunduh ulang.');

            return self::SUCCESS;
        }

        File::ensureDirectoryExists(dirname($path));
        $gz = $path . '.download.gz';
        $tmp = $path . '.download';

        try {
            $url = $this->download($gz);
            $this->info("Mengekstrak {$url} ...");
            $this->gunzip($gz, $tmp);

            // Pastikan file bisa dibaca sebelum menggantikan yang lama
            $reader = new Reader($tmp);
            $type = $reader->metadata()->databaseType;
            $reader->close();

            File::move($tmp, $path);
            $this->info("Selesai: {$type}, " . round(filesize($path) / 1048576) . ' MB di ' . $path);

            return self::SUCCESS;
        } catch (Throwable $e) {
            report($e);
            $this->error('Gagal memperbarui database lokasi: ' . $e->getMessage());

            return self::FAILURE;
        } finally {
            File::delete([$gz, $tmp]);
        }
    }

    private function download(string $target): string
    {
        foreach ([now(), now()->subMonthNoOverflow()] as $month) {
            $url = str_replace('{month}', $month->format('Y-m'), config('analytics.geoip.download_url'));
            $this->info("Mengunduh {$url} ...");

            // Tulis lewat handle file milik sendiri agar ukuran file bisa diperiksa dengan pasti setelahnya
            $handle = fopen($target, 'wb');
            try {
                $response = Http::connectTimeout(30)->timeout(1800)->withOptions(['sink' => $handle])->get($url);
            } finally {
                if (is_resource($handle)) {
                    fclose($handle);
                }
            }

            clearstatcache(true, $target);
            $size = is_file($target) ? filesize($target) : 0;

            if ($response->successful() && $size > 1_000_000) {
                $this->info('Terunduh ' . round($size / 1048576) . ' MB.');

                return $url;
            }

            $this->warn("Belum tersedia (HTTP {$response->status()}, " . round($size / 1024) . ' KB).');
        }

        throw new \RuntimeException('File database DB-IP tidak ditemukan untuk bulan ini maupun bulan lalu.');
    }

    private function gunzip(string $source, string $target): void
    {
        $in = gzopen($source, 'rb');
        $out = fopen($target, 'wb');

        if (! $in || ! $out) {
            throw new \RuntimeException('Tidak bisa membuka file untuk diekstrak.');
        }

        while (! gzeof($in)) {
            fwrite($out, gzread($in, 1 << 20));
        }

        gzclose($in);
        fclose($out);
    }
}
