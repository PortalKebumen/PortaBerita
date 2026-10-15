<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Rekap tayangan per wilayah pembaca per hari (Laporan Analitik > Lokasi Pembaca).
     * Hanya nama wilayah dari database lokasi IP; alamat IP pembaca tidak disimpan.
     * String kosong = tidak diketahui (dipakai agar indeks unik tetap berlaku).
     */
    public function up(): void
    {
        Schema::create('article_daily_locations', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->char('country_code', 2)->default('');
            $table->string('country', 100)->default('');
            $table->string('region', 100)->default('');
            $table->string('city', 100)->default('');
            $table->unsignedInteger('views')->default(0);

            $table->unique(['date', 'country_code', 'region', 'city'], 'adl_date_location_unique');
            $table->index(['country_code', 'region']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_daily_locations');
    }
};
