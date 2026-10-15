<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tayangan artikel per hari, untuk Laporan Analitik (tren & views per kategori/penulis per periode).
     * articles.views_count tetap menjadi total sepanjang masa.
     */
    public function up(): void
    {
        Schema::create('article_daily_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('views')->default(0);

            $table->unique(['article_id', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_daily_views');
    }
};
