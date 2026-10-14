<?php

namespace Database\Seeders;

use App\Models\Advertisement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DummyAdMetricSeeder extends Seeder
{
    /**
     * Impresi & klik dummy 30 hari terakhir untuk grafik laporan iklan.
     * Dilewati untuk iklan yang sudah punya data metrik.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        mt_srand(2026); // angka acak tetap, hasil seeder selalu sama

        Advertisement::query()->whereDoesntHave('metrics')->each(function (Advertisement $ad) {
            $rows = [];
            $from = max($ad->start_date->copy()->startOfDay()->getTimestamp(), now()->subDays(30)->startOfDay()->getTimestamp());

            for ($day = $from; $day <= now()->getTimestamp(); $day += 86400) {
                $impressions = mt_rand(15, 60);
                $clicks = mt_rand(0, max(1, intdiv($impressions, 12)));

                foreach (['impression' => $impressions, 'click' => $clicks] as $type => $count) {
                    for ($i = 0; $i < $count; $i++) {
                        $at = date('Y-m-d H:i:s', min(now()->getTimestamp(), $day + mt_rand(0, 86399)));
                        $rows[] = [
                            'advertisement_id' => $ad->id,
                            'type' => $type,
                            'ip_hash' => hash('sha256', 'seed-' . $ad->id . '-' . $type . '-' . $day . '-' . $i),
                            'created_at' => $at,
                        ];
                    }
                }
            }

            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table('ad_metrics')->insert($chunk);
            }
        });
    }
}
