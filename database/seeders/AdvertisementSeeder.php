<?php

namespace Database\Seeders;

use App\Models\Advertisement;
use App\Models\AdMetric;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AdvertisementSeeder extends Seeder
{
    /**
     * Seed initial sample advertisements and metrics according to mockup.
     */
    public function run(): void
    {
        $today = Carbon::today();

        $samples = [
            [
                'advertiser_name' => 'Bank Kebumen Sejahtera',
                'advertiser_contact' => 'marketing@bkscorp.co.id',
                'target_url' => 'https://bkscorp.co.id',
                'placement' => 'header',
                'start_date' => $today->copy()->subDays(5)->toDateString(),
                'end_date' => $today->copy()->addDays(9)->toDateString(),
                'status' => 'active',
                'impressions' => 48200,
                'clicks' => 612,
            ],
            [
                'advertiser_name' => 'Toko Bangunan Makmur',
                'advertiser_contact' => '0812-xxxx-2210',
                'target_url' => 'https://tokomakmur.com',
                'placement' => 'sidebar',
                'start_date' => $today->copy()->subDays(3)->toDateString(),
                'end_date' => $today->copy()->addDays(5)->toDateString(), // Akan berakhir < 7 hari
                'status' => 'active',
                'impressions' => 31500,
                'clicks' => 284,
            ],
            [
                'advertiser_name' => 'Griya Sembako Ceria',
                'advertiser_contact' => 'griyasembako@gmail.com',
                'target_url' => 'https://sembakoceria.id',
                'placement' => 'inline_artikel',
                'start_date' => $today->copy()->subDays(20)->toDateString(),
                'end_date' => $today->copy()->subDays(5)->toDateString(),
                'status' => 'expired',
                'impressions' => 52700,
                'clicks' => 740,
            ],
            [
                'advertiser_name' => 'Kopi Kebumen Raya',
                'advertiser_contact' => 'hello@kopikebumen.id',
                'target_url' => 'https://kopikebumen.id',
                'placement' => 'footer',
                'start_date' => $today->copy()->subDays(2)->toDateString(),
                'end_date' => $today->copy()->addDays(12)->toDateString(),
                'status' => 'active',
                'impressions' => 12100,
                'clicks' => 98,
            ],
            [
                'advertiser_name' => 'Klinik Sehat Keluarga',
                'advertiser_contact' => '0813-xxxx-9081',
                'target_url' => 'https://sehatkeluarga.com',
                'placement' => 'header',
                'start_date' => $today->copy()->addDays(3)->toDateString(),
                'end_date' => $today->copy()->addDays(17)->toDateString(),
                'status' => 'scheduled',
                'impressions' => 0,
                'clicks' => 0,
            ],
            [
                'advertiser_name' => 'Batik Tanjungsari Store',
                'advertiser_contact' => 'cs@batiktanjungsari.co',
                'target_url' => 'https://batiktanjungsari.co',
                'placement' => 'sidebar',
                'start_date' => $today->copy()->subDays(35)->toDateString(),
                'end_date' => $today->copy()->subDays(20)->toDateString(),
                'status' => 'inactive',
                'impressions' => 28900,
                'clicks' => 205,
            ],
        ];

        foreach ($samples as $item) {
            $ad = Advertisement::firstOrCreate(
                [
                    'advertiser_name' => $item['advertiser_name'],
                    'placement' => $item['placement'],
                ],
                [
                    'advertiser_contact' => $item['advertiser_contact'],
                    'target_url' => $item['target_url'],
                    'start_date' => $item['start_date'],
                    'end_date' => $item['end_date'],
                    'status' => $item['status'],
                ]
            );

            // Buat sample metric bila belum ada
            if ($item['impressions'] > 0 && $ad->metrics()->count() === 0) {
                // Buat 1 batch impresi untuk bulan ini
                AdMetric::create([
                    'advertisement_id' => $ad->id,
                    'type' => 'impression',
                    'ip_hash' => 'seed-hash',
                    'created_at' => $today->copy()->subDays(1),
                ]);

                if ($item['clicks'] > 0) {
                    AdMetric::create([
                        'advertisement_id' => $ad->id,
                        'type' => 'click',
                        'ip_hash' => 'seed-hash',
                        'created_at' => $today->copy()->subDays(1),
                    ]);
                }
            }
        }
    }
}
