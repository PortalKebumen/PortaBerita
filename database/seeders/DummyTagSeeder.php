<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DummyTagSeeder extends Seeder
{
    /**
     * Tag dummy dari kata kunci di judul + ringkasan artikel (maks. 4 per artikel).
     * Jalankan setelah DummyArticleSeeder.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $rules = [
            'Bencana' => ['banjir', 'longsor', 'kebakaran', 'siaga darurat', 'pohon tumbang', 'gelombang tinggi'],
            'Cuaca' => ['hujan', 'gelombang', 'bmkg', 'kemarau', 'kekeringan'],
            'Pantai' => ['pantai', 'pesisir', 'nelayan', 'rob'],
            'Pariwisata' => ['wisata', 'goa', 'benteng', 'resort', 'pemandu'],
            'Kuliner' => ['sate', 'lanting', 'jipang', 'kuliner', 'keripik', 'oleh-oleh', 'jajanan'],
            'UMKM' => ['umkm', 'koperasi', 'usaha', 'kredit', 'perajin', 'pameran'],
            'Ekspor' => ['ekspor', 'mancanegara', 'eropa', 'luar jawa'],
            'Harga Pangan' => ['harga', 'cabai', 'beras', 'minyak goreng', 'gabah'],
            'Pertanian' => ['petani', 'padi', 'gabah', 'gula kelapa', 'maggot', 'hasil tani'],
            'Pemkab Kebumen' => ['pemkab', 'bupati', 'pemerintah kabupaten', 'disdukcapil', 'mal pelayanan'],
            'DPRD Kebumen' => ['dprd', 'raperda', 'apbd'],
            'Kesehatan' => ['puskesmas', 'rsud', 'tenaga medis', 'dokter'],
            'Pendidikan' => ['sekolah', 'siswa', 'kampus', 'universitas', 'mahasiswa', 'pelajar', 'kurikulum'],
            'Beasiswa' => ['beasiswa', 'lpdp'],
            'Prestasi' => ['juara', 'medali', 'rekor', 'prestasi', 'penghargaan'],
            'Persak Kebumen' => ['persak', 'candradimuka'],
            'Olahraga' => ['turnamen', 'kejuaraan', 'atlet', 'lari', 'voli', 'bulu tangkis', 'silat', 'angkat besi', 'koni'],
            'Seni Budaya' => ['kesenian', 'tari', 'festival', 'batik', 'jamjaneng', 'cepetan', 'lengger', 'sanggar'],
            'Geopark' => ['geopark', 'karangsambung', 'geologi', 'karst'],
            'Lingkungan' => ['sampah', 'plastik', 'bersih pantai', 'limbah', 'energi terbarukan'],
            'Kriminal' => ['polres', 'pencurian', 'penipuan', 'miras', 'minuman keras', 'vonis'],
            'Teknologi' => ['aplikasi', 'digital', 'robot', 'startup', 'teknologi', 'online'],
        ];

        $tagIds = collect($rules)->keys()->mapWithKeys(fn (string $name) => [
            $name => Tag::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name])->id,
        ]);

        Article::query()->doesntHave('tags')->each(function (Article $article) use ($rules, $tagIds) {
            $text = Str::lower($article->title . ' ' . $article->excerpt);

            $ids = collect($rules)
                ->filter(fn (array $keywords) => Str::contains($text, $keywords))
                ->keys()
                ->take(4)
                ->map(fn (string $name) => $tagIds[$name]);

            $article->tags()->sync($ids->all());
        });
    }
}
