<?php

namespace Database\Seeders;

use App\Models\Advertisement;
use Illuminate\Database\Seeder;

class DummyAdvertisementSeeder extends Seeder
{
    /**
     * Seed iklan bergambar dari public/images/iklan, dua iklan per lokasi agar rotasi terlihat.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $ads = [
            ['Toko Tas Kulit Nusantara', 'header', 'banner-header-1.png', 'https://example.com/tas-kulit'],
            ['Hot Dog Keju Kebumen', 'header', 'banner-header-2.png', 'https://example.com/hotdog-keju'],
            ['Angkringan Wedangan', 'sidebar', 'Sidebar-1.png', 'https://example.com/angkringan'],
            ['Warner & Spencer Property', 'sidebar', 'Sidebar-2.png', 'https://example.com/rumah-dijual'],
            ['PT Audio Elektronik', 'inline_artikel', 'Inline-1.png', 'https://example.com/audio-elektronik'],
            ['Borcelle Resort', 'inline_artikel', 'Inline-2.png', 'https://example.com/borcelle-resort'],
        ];

        foreach ($ads as $index => [$name, $placement, $file, $url]) {
            $path = public_path("images/iklan/{$file}");

            if (! is_file($path)) {
                $this->command?->warn("Gambar {$file} tidak ditemukan, iklan {$name} dilewati.");

                continue;
            }

            $ad = Advertisement::withoutEvents(fn () => Advertisement::updateOrCreate(
                ['advertiser_name' => $name, 'placement' => $placement],
                [
                    'advertiser_contact' => '0812-0000-' . str_pad((string) ($index + 1), 4, '0', STR_PAD_LEFT),
                    'target_url' => $url,
                    'start_date' => today()->subDays(3 + $index)->toDateString(),
                    'end_date' => today()->addDays(30 + $index * 5)->toDateString(),
                    'status' => 'active',
                ]
            ));

            if (! $ad->hasMedia('banner')) {
                $ad->addMedia($path)
                    ->preservingOriginal()
                    ->toMediaCollection('banner');
            }
        }
    }
}
