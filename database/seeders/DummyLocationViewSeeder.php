<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DummyLocationViewSeeder extends Seeder
{
    /**
     * Sebaran wilayah pembaca dummy untuk Laporan Analitik > Lokasi Pembaca: total tayangan harian
     * (article_daily_views) dibagi ke wilayah dengan proporsi khas portal daerah Kebumen.
     * Tanggal yang sudah punya data lokasi dilewati.
     */
    private const LOCATIONS = [
        // [kode negara, negara, provinsi, kota, bobot]
        ['ID', 'Indonesia', 'Central Java', 'Kebumen', 38],
        ['ID', 'Indonesia', 'Central Java', 'Semarang', 8],
        ['ID', 'Indonesia', 'Central Java', 'Purworejo', 6],
        ['ID', 'Indonesia', 'Central Java', 'Purwokerto', 6],
        ['ID', 'Indonesia', 'Central Java', 'Cilacap', 5],
        ['ID', 'Indonesia', 'Central Java', '', 3],
        ['ID', 'Indonesia', 'Special Region of Yogyakarta', 'Yogyakarta', 6],
        ['ID', 'Indonesia', 'Jakarta', 'Jakarta', 10],
        ['ID', 'Indonesia', 'West Java', 'Bandung', 4],
        ['ID', 'Indonesia', 'East Java', 'Surabaya', 3],
        ['ID', 'Indonesia', 'Banten', 'Tangerang', 2],
        ['MY', 'Malaysia', 'Kuala Lumpur', 'Kuala Lumpur', 2],
        ['TW', 'Taiwan', 'Taipei City', 'Taipei', 1],
        ['SG', 'Singapore', '', 'Singapore', 1],
        ['', '', '', '', 5], // tidak diketahui
    ];

    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        mt_srand(2028); // angka acak tetap, hasil seeder selalu sama

        $existing = DB::table('article_daily_locations')->distinct()->pluck('date')->flip();

        $daily = DB::table('article_daily_views')
            ->selectRaw('date, SUM(views) as views')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        foreach ($daily->chunk(60) as $chunk) {
            $rows = [];

            foreach ($chunk as $day) {
                if (isset($existing[$day->date])) {
                    continue;
                }

                // Bobot divariasikan ±20% per hari, lalu dibagi dengan sisa terbesar agar jumlahnya
                // tepat sama dengan total tayangan hari itu
                $weights = array_map(fn ($loc) => $loc[4] * mt_rand(80, 120), self::LOCATIONS);
                $sumWeights = array_sum($weights);
                $exact = array_map(fn ($w) => $day->views * $w / $sumWeights, $weights);
                $alloc = array_map('floor', $exact);
                $remainders = array_map(fn ($e, $a) => $e - $a, $exact, $alloc);
                arsort($remainders);
                foreach (array_slice(array_keys($remainders), 0, (int) $day->views - (int) array_sum($alloc)) as $i) {
                    $alloc[$i]++;
                }

                foreach (self::LOCATIONS as $i => [$code, $country, $region, $city]) {
                    $views = (int) $alloc[$i];

                    if ($views > 0) {
                        $rows[] = [
                            'date' => $day->date,
                            'country_code' => $code,
                            'country' => $country,
                            'region' => $region,
                            'city' => $city,
                            'views' => $views,
                        ];
                    }
                }
            }

            if ($rows) {
                DB::table('article_daily_locations')->insert($rows);
            }
        }
    }
}
