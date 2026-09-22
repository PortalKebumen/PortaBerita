<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Berita Kebumen',
                'slug' => 'berita-kebumen',
                'children' => [
                    'Peristiwa',
                    'Kriminal & Hukum',
                    'Komunitas',
                ],
            ],
            [
                'name' => 'Pemerintahan',
                'slug' => 'pemerintahan',
                'children' => [
                    'Kebijakan Daerah',
                    'DPRD Kebumen',
                    'Pelayanan Publik',
                ],
            ],
            [
                'name' => 'Ekonomi & UMKM',
                'slug' => 'ekonomi-umkm',
                'children' => [
                    'Peluang Bisnis',
                    'Pasar & Harga',
                    'Koperasi & UMKM',
                ],
            ],
            [
                'name' => 'Wisata & Budaya',
                'slug' => 'wisata-budaya',
                'children' => [
                    'Destinasi Wisata',
                    'Seni & Tradisi',
                    'Kuliner Khas',
                ],
            ],
            [
                'name' => 'Pendidikan',
                'slug' => 'pendidikan',
                'children' => [
                    'Sekolah & Kampus',
                    'Prestasi',
                    'Beasiswa',
                ],
            ],
            [
                'name' => 'Olahraga',
                'slug' => 'olahraga',
                'children' => [
                    'Sepak Bola',
                    'Turnamen Lokal',
                    'Atlet Daerah',
                ],
            ],
            [
                // Rubrik Wajib: Potensi Daerah & sub-kategorinya
                'name' => 'Potensi Daerah',
                'slug' => 'potensi-daerah',
                'children' => [
                    'Pertanian & Perkebunan',
                    'Perikanan & Kelautan',
                    'Kerajinan Rakyat',
                    'Industri Kreatif',
                    'Geopark Kebumen',
                ],
            ],
            [
                'name' => 'Nasional',
                'slug' => 'nasional',
                'children' => [],
            ],
            [
                'name' => 'Internasional',
                'slug' => 'internasional',
                'children' => [],
            ],
        ];

        foreach ($categories as $catData) {
            $parent = Category::firstOrCreate(
                ['slug' => $catData['slug'] ?? Str::slug($catData['name'])],
                ['name' => $catData['name']]
            );

            // Update nama jika sudah ada tapi namanya berbeda
            if ($parent->name !== $catData['name']) {
                $parent->update(['name' => $catData['name']]);
            }

            if (!empty($catData['children'])) {
                foreach ($catData['children'] as $childName) {
                    $childSlug = Str::slug($childName);
                    Category::firstOrCreate(
                        ['slug' => $childSlug],
                        [
                            'name' => $childName,
                            'parent_id' => $parent->id,
                        ]
                    );
                }
            }
        }
    }
}
