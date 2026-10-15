<?php

namespace Database\Seeders;

use App\Models\MenuItem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Cache;

/**
 * Isi lengkap untuk server testing/staging: role & permission, akun per role, kategori,
 * halaman & menu, pengaturan situs, 75 artikel dummy (foto, SEO, tag, tayangan harian), iklan bergambar + metriknya.
 *
 *   php artisan migrate:fresh --seed --seeder=TestingServerSeeder
 *
 * Semua seeder di sini aman dijalankan ulang (tidak membuat data dobel). Ditolak di APP_ENV=production.
 */
class TestingServerSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('TestingServerSeeder berisi data dummy dan tidak boleh dijalankan di production.');

            return;
        }

        $this->call([
            // Dasar aplikasi
            RolePermissionSeeder::class,
            DemoUserSeeder::class,
            SystemAccountSeeder::class,
            CategorySeeder::class,
            PageMenuSeeder::class,
            DemoSiteSettingSeeder::class,

            // Konten dummy (urutan penting: penulis & foto harus ada sebelum artikel, artikel sebelum tag)
            DummyAuthorProfileSeeder::class,
            DummyMediaLibrarySeeder::class,
            DummyArticleSeeder::class,
            DummyTagSeeder::class,
            DummyArticleViewSeeder::class,
            DummyLocationViewSeeder::class,

            // Iklan
            DummyAdvertisementSeeder::class,
            DummyAdMetricSeeder::class,
        ]);

        // Data di atas sebagian ditulis tanpa event model, jadi bersihkan cache tampilan publik
        MenuItem::flushCache();
        foreach (['public.kategori_nav.v1', 'public.breaking_ticker.v2', 'public.footer_popular.v1'] as $key) {
            Cache::forget($key);
        }

        $this->command?->info('Selesai. Jalankan "php artisan storage:link" bila gambar belum tampil.');
    }
}
