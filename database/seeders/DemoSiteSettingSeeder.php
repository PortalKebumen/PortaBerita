<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class DemoSiteSettingSeeder extends Seeder
{
    /**
     * Pengaturan situs untuk server testing: akun sosial media (contoh) dan WhatsApp Channel.
     * Hanya mengisi key yang masih kosong, sehingga perubahan dari halaman Pengaturan tidak tertimpa.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $defaults = [
            'facebook' => 'https://www.facebook.com/portalkebumen',
            'instagram' => 'https://www.instagram.com/portalkebumen',
            'x_twitter' => 'https://x.com/portalkebumen',
            'youtube' => 'https://www.youtube.com/@portalkebumen',
            'whatsapp' => '0812-3456-7890',
            'whatsapp_channel' => 'https://whatsapp.com/channel/0029VbCnCQc5PO0zm2o3YD45',
        ];

        $current = SiteSetting::values();

        SiteSetting::setMany(array_filter(
            $defaults,
            fn ($value, $key) => blank($current[$key] ?? null),
            ARRAY_FILTER_USE_BOTH
        ));
    }
}
