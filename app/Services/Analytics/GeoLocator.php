<?php

namespace App\Services\Analytics;

use MaxMind\Db\Reader;
use Throwable;

/**
 * Cari wilayah (negara, provinsi, kota) dari alamat IP memakai database DB-IP City Lite.
 * IP hanya dipakai saat itu untuk dicocokkan, tidak disimpan. Bila database belum diunduh
 * atau IP bersifat lokal/privat, hasilnya null dan tayangan tetap dihitung tanpa lokasi.
 */
class GeoLocator
{
    /** Nama provinsi Indonesia: nama di database (bahasa Inggris) => nama resmi. */
    public const ID_PROVINCES = [
        'Aceh' => 'Aceh',
        'North Sumatra' => 'Sumatera Utara',
        'West Sumatra' => 'Sumatera Barat',
        'Riau' => 'Riau',
        'Riau Islands' => 'Kepulauan Riau',
        'Jambi' => 'Jambi',
        'Bengkulu' => 'Bengkulu',
        'South Sumatra' => 'Sumatera Selatan',
        'Bangka–Belitung Islands' => 'Kepulauan Bangka Belitung',
        'Bangka-Belitung Islands' => 'Kepulauan Bangka Belitung',
        'Bangka Belitung Islands' => 'Kepulauan Bangka Belitung',
        'Lampung' => 'Lampung',
        'Banten' => 'Banten',
        'Jakarta' => 'DKI Jakarta',
        'West Java' => 'Jawa Barat',
        'Central Java' => 'Jawa Tengah',
        'Special Region of Yogyakarta' => 'DI Yogyakarta',
        'Yogyakarta' => 'DI Yogyakarta',
        'East Java' => 'Jawa Timur',
        'Bali' => 'Bali',
        'West Nusa Tenggara' => 'Nusa Tenggara Barat',
        'East Nusa Tenggara' => 'Nusa Tenggara Timur',
        'West Kalimantan' => 'Kalimantan Barat',
        'Central Kalimantan' => 'Kalimantan Tengah',
        'South Kalimantan' => 'Kalimantan Selatan',
        'East Kalimantan' => 'Kalimantan Timur',
        'North Kalimantan' => 'Kalimantan Utara',
        'North Sulawesi' => 'Sulawesi Utara',
        'Gorontalo' => 'Gorontalo',
        'Central Sulawesi' => 'Sulawesi Tengah',
        'West Sulawesi' => 'Sulawesi Barat',
        'South Sulawesi' => 'Sulawesi Selatan',
        'Southeast Sulawesi' => 'Sulawesi Tenggara',
        'Maluku' => 'Maluku',
        'North Maluku' => 'Maluku Utara',
        'Papua' => 'Papua',
        'West Papua' => 'Papua Barat',
        'Southwest Papua' => 'Papua Barat Daya',
        'Central Papua' => 'Papua Tengah',
        'Highland Papua' => 'Papua Pegunungan',
        'South Papua' => 'Papua Selatan',
    ];

    private ?Reader $reader = null;

    private bool $unavailable = false;

    /**
     * @return array{country_code: string, country: string, region: string, city: string}|null
     */
    public function lookup(?string $ip): ?array
    {
        if (! config('analytics.geoip.enabled') || ! $ip
            || ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return null;
        }

        $reader = $this->reader();
        if (! $reader) {
            return null;
        }

        try {
            $record = $reader->get($ip);
        } catch (Throwable) {
            return null;
        }

        if (! is_array($record) || empty($record['country']['iso_code'])) {
            return null;
        }

        return [
            'country_code' => (string) $record['country']['iso_code'],
            'country' => mb_substr((string) ($record['country']['names']['en'] ?? $record['country']['iso_code']), 0, 100),
            'region' => mb_substr((string) ($record['subdivisions'][0]['names']['en'] ?? ''), 0, 100),
            'city' => mb_substr((string) ($record['city']['names']['en'] ?? ''), 0, 100),
        ];
    }

    public function available(): bool
    {
        return $this->reader() !== null;
    }

    /** Nama provinsi untuk ditampilkan (provinsi Indonesia memakai nama resmi). */
    public static function regionLabel(?string $region, ?string $countryCode): string
    {
        if (! $region) {
            return 'Tidak diketahui';
        }

        return $countryCode === 'ID' ? (self::ID_PROVINCES[$region] ?? $region) : $region;
    }

    private function reader(): ?Reader
    {
        if ($this->reader || $this->unavailable) {
            return $this->reader;
        }

        $path = config('analytics.geoip.path');

        try {
            return $this->reader = is_file($path) ? new Reader($path) : null;
        } catch (Throwable $e) {
            report($e);

            return null;
        } finally {
            $this->unavailable = $this->reader === null;
        }
    }
}
