<?php

namespace Database\Seeders;

use App\Models\LibraryMedia;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DummyMediaLibrarySeeder extends Seeder
{
    /** Foto dummy artikel (ikut di repo, karena public/storage tidak ikut git). */
    public const IMAGE = 'database/seeders/assets/sungai-odo-semarang-1787986639880_169.jpeg';

    /**
     * Masukkan foto dummy ke Media Library lengkap dengan Alt Text & Caption.
     * DummyArticleSeeder menyalin alt & caption dari sini ke setiap artikel.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $path = base_path(self::IMAGE);

        if (! is_file($path)) {
            $this->command?->warn('Foto dummy ' . self::IMAGE . ' tidak ditemukan, seeder dilewati.');

            return;
        }

        if (Media::where('collection_name', 'library')->where('file_name', basename($path))->exists()) {
            return;
        }

        $owner = User::role('Super Admin')->orderBy('id')->first();

        LibraryMedia::create(['uploaded_by' => $owner?->id])
            ->addMedia($path)
            ->preservingOriginal()
            ->withCustomProperties([
                'alt_text' => 'Warga berenang di aliran sungai jernih yang dikelilingi pepohonan rindang',
                'caption' => 'Warga menikmati segarnya aliran sungai jernih di tengah rimbun pepohonan. (Foto: detikcom)',
            ])
            ->toMediaCollection('library');
    }
}
