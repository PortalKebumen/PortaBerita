<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\ArticleStatus;
use App\Models\Category;
use App\Models\SeoMeta;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DummyArticleSeeder extends Seeder
{
    /**
     * Urutan status yang diputar ke seluruh artikel supaya setiap status alur kerja terwakili.
     */
    private const STATUS_CYCLE = [
        ArticleStatus::Published,
        ArticleStatus::Draft,
        ArticleStatus::Submitted,
        ArticleStatus::Published,
        ArticleStatus::Approved,
        ArticleStatus::Rejected,
        ArticleStatus::Published,
        ArticleStatus::Archived,
        ArticleStatus::Published,
        ArticleStatus::Draft,
    ];

    /**
     * Seed 3 artikel dummy per kategori dengan status, foto, dan SEO lengkap.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $authors = User::role('Penulis')->orderBy('id')->take(2)->get();

        if ($authors->isEmpty()) {
            $this->command?->warn('Tidak ada user dengan role Penulis, seeder dilewati.');

            return;
        }

        $editor = User::role(['Editor', 'Super Admin'])->orderBy('id')->first();
        $categories = Category::pluck('id', 'slug');
        $image = $this->dummyImage();

        $rows = collect($this->articles())
            ->flatMap(fn ($items, $slug) => collect($items)->map(fn ($item) => [$slug, ...$item]))
            ->values();
        $breakingCount = 0;

        foreach ($rows as $index => [$categorySlug, $title, $excerpt, $paragraphs]) {
            $flags = $rows[$index][4] ?? [];

            if (! isset($categories[$categorySlug])) {
                $this->command?->warn("Kategori {$categorySlug} tidak ditemukan, artikel \"{$title}\" dilewati.");

                continue;
            }

            // Breaking news selalu dipublikasikan agar tampil di ticker beranda.
            $status = in_array('breaking', $flags, true)
                ? ArticleStatus::Published
                : self::STATUS_CYCLE[$index % count(self::STATUS_CYCLE)];

            // Ticker breaking hanya memuat artikel 48 jam terakhir, jadi breaking dibuat baru terbit.
            $createdAt = in_array('breaking', $flags, true)
                ? now()->subHours(6 + 9 * $breakingCount++)->startOfMinute()
                : now()->subDays($rows->count() - $index)->setTime(7 + ($index % 12), ($index * 7) % 60);
            $publishedAt = in_array($status, [ArticleStatus::Published, ArticleStatus::Archived], true)
                ? $createdAt->copy()->addHours(3)
                : null;

            $article = Article::withoutEvents(fn () => Article::updateOrCreate(
                ['slug' => Str::slug($title)],
                [
                    'title' => $title,
                    'excerpt' => $excerpt,
                    'content' => collect($paragraphs)->map(fn ($p) => "<p>{$p}</p>")->implode("\n"),
                    'status' => $status,
                    // Digeser tiap satu putaran status agar status terbit terbagi rata antarpenulis.
                    'author_id' => $authors[($index + intdiv($index, count(self::STATUS_CYCLE))) % $authors->count()]->id,
                    'category_id' => $categories[$categorySlug],
                    'is_breaking' => in_array('breaking', $flags, true),
                    'is_advertorial' => in_array('advertorial', $flags, true),
                    'views_count' => $publishedAt ? (($index * 137) % 2400) + 35 : 0,
                    'published_at' => $publishedAt,
                    'scheduled_at' => null,
                    'archived_at' => $status === ArticleStatus::Archived
                        ? Carbon::parse(min($publishedAt->copy()->addDays(10), now()->subHour()))
                        : null,
                    'created_at' => $createdAt,
                    'updated_at' => $publishedAt ?? $createdAt,
                ]
            ));

            if ($status === ArticleStatus::Rejected && $editor && ! $article->revisions()->exists()) {
                $article->revisions()->create([
                    'editor_id' => $editor->id,
                    'content' => 'Mohon lengkapi narasumber resmi dan perjelas data angka pada paragraf kedua sebelum diajukan ulang.',
                ]);
            }

            $this->attachImages($article, $image);

            SeoMeta::updateOrCreate(
                ['article_id' => $article->id],
                [
                    'meta_title' => Str::limit($title, 57),
                    'meta_description' => Str::limit($excerpt, 157),
                    'canonical_url' => $article->publicUrl(),
                    'og_image' => $article->getFirstMedia('og')?->getUrl(),
                    'noindex' => false,
                    'nofollow' => false,
                ]
            );
        }
    }

    private function attachImages(Article $article, ?string $image): void
    {
        if (! $image) {
            return;
        }

        foreach (['featured', 'og'] as $collection) {
            if (! $article->hasMedia($collection)) {
                $article->addMedia($image)
                    ->preservingOriginal()
                    ->toMediaCollection($collection);
            }
        }

        $article->unsetRelation('media');

        // Alt & caption mengikuti data foto yang sama di Media Library.
        $library = Media::where('collection_name', 'library')->where('file_name', basename($image))->first();
        $featured = $article->getFirstMedia('featured');

        if ($library && $featured) {
            $featured->setCustomProperty('alt_text', (string) $library->getCustomProperty('alt_text', ''))
                ->setCustomProperty('caption', (string) $library->getCustomProperty('caption', ''))
                ->save();
        }
    }

    /**
     * Foto dummy yang dipakai bersama oleh semua artikel seeder.
     */
    private function dummyImage(): ?string
    {
        $files = glob(public_path('storage/21/*.{jpg,jpeg,png,webp}'), GLOB_BRACE) ?: [];

        return $files[0] ?? null;
    }

    /**
     * Artikel per slug kategori: [judul, ringkasan, paragraf, flag opsional ('breaking'/'advertorial')].
     *
     * @return array<string, array<int, array{0: string, 1: string, 2: array<int, string>, 3?: array<int, string>}>>
     */
    private function articles(): array
    {
        return [
            'peristiwa' => [
                ['Banjir Rob Genangi Permukiman Warga Pesisir Ayah', 'Puluhan rumah warga di pesisir Kecamatan Ayah tergenang banjir rob setinggi 40 sentimeter sejak Senin malam.', [
                    'Banjir rob kembali melanda permukiman warga di pesisir Kecamatan Ayah, Kabupaten Kebumen. Air laut mulai masuk ke rumah warga sejak Senin malam dan belum sepenuhnya surut hingga Selasa siang.',
                    'Kepala desa setempat menyebut sedikitnya 37 rumah terdampak. Warga memindahkan barang berharga ke tempat yang lebih tinggi sambil menunggu air surut.',
                    'BPBD Kebumen telah menerjunkan petugas untuk mendata kerugian dan menyalurkan bantuan logistik berupa beras, mi instan, dan air bersih.',
                ], ['breaking']],
                ['Kebakaran Gudang Mebel di Pejagoan, Kerugian Ditaksir Ratusan Juta', 'Api diduga berasal dari korsleting listrik dan baru berhasil dipadamkan setelah dua jam.', [
                    'Sebuah gudang mebel di Kecamatan Pejagoan ludes terbakar pada Rabu dini hari. Warga sekitar melihat kepulan asap tebal dari bagian belakang gudang sekitar pukul 02.00 WIB.',
                    'Empat unit mobil pemadam kebakaran dikerahkan untuk menjinakkan api. Petugas membutuhkan waktu sekitar dua jam hingga api benar-benar padam.',
                    'Tidak ada korban jiwa dalam peristiwa ini. Polisi masih menyelidiki penyebab pasti kebakaran, namun dugaan sementara berasal dari korsleting listrik.',
                ]],
                ['Pohon Tumbang Tutup Jalan Nasional Kebumen–Gombong Saat Hujan Deras', 'Arus lalu lintas sempat tersendat hampir satu jam sebelum petugas berhasil mengevakuasi pohon.', [
                    'Hujan deras disertai angin kencang menyebabkan sebuah pohon besar tumbang dan menutup ruas jalan nasional Kebumen–Gombong pada Kamis sore.',
                    'Petugas gabungan dari BPBD, Dinas PU, dan kepolisian segera memotong batang pohon menggunakan gergaji mesin. Kendaraan dialihkan sementara melalui jalan desa.',
                    'BPBD mengimbau pengendara untuk lebih waspada saat melintas di jalur yang banyak ditumbuhi pohon besar selama musim hujan.',
                ]],
            ],
            'kriminal-hukum' => [
                ['Polres Kebumen Ungkap Jaringan Pencurian Sepeda Motor Antarkabupaten', 'Tiga tersangka diamankan beserta 11 unit sepeda motor hasil curian dari berbagai wilayah.', [
                    'Satreskrim Polres Kebumen berhasil mengungkap jaringan pencurian sepeda motor yang beroperasi di Kebumen, Purworejo, dan Banyumas.',
                    'Tiga tersangka diamankan dalam penggerebekan di sebuah rumah kontrakan. Polisi turut menyita 11 unit sepeda motor serta sejumlah kunci letter T.',
                    'Para tersangka dijerat Pasal 363 KUHP tentang pencurian dengan pemberatan dengan ancaman hukuman maksimal tujuh tahun penjara.',
                ]],
                ['Pengadilan Negeri Kebumen Vonis Pelaku Penipuan Arisan Online', 'Terdakwa dijatuhi hukuman tiga tahun penjara karena merugikan puluhan korban hingga Rp800 juta.', [
                    'Majelis hakim Pengadilan Negeri Kebumen menjatuhkan vonis tiga tahun penjara kepada terdakwa kasus penipuan arisan online.',
                    'Dalam persidangan terungkap terdakwa menjanjikan keuntungan berlipat kepada 46 anggota arisan, namun dana yang terkumpul justru digunakan untuk kepentingan pribadi.',
                    'Jaksa penuntut umum menyatakan pikir-pikir atas putusan tersebut, sementara para korban berharap aset terdakwa dapat disita untuk mengganti kerugian.',
                ]],
                ['Satpol PP Kebumen Sita Ratusan Botol Minuman Keras Ilegal', 'Razia dilakukan di sejumlah warung di wilayah perkotaan menjelang perayaan hari besar.', [
                    'Satpol PP Kabupaten Kebumen menyita 312 botol minuman keras tanpa izin dalam razia yang digelar di sejumlah warung di wilayah perkotaan.',
                    'Kepala Satpol PP menyebut razia rutin ini bertujuan menjaga ketertiban umum, terutama menjelang perayaan hari besar dan pergantian tahun.',
                    'Para pemilik warung didata dan diminta membuat surat pernyataan tidak akan mengulangi perbuatannya. Barang bukti akan dimusnahkan bersama aparat terkait.',
                ]],
            ],
            'komunitas' => [
                ['Komunitas Relawan Muda Gelar Aksi Bersih Pantai Menganti', 'Lebih dari 200 relawan mengumpulkan hampir satu ton sampah plastik dalam aksi bersih pantai akhir pekan lalu.', [
                    'Ratusan relawan muda dari berbagai komunitas di Kebumen menggelar aksi bersih Pantai Menganti pada Minggu pagi.',
                    'Koordinator kegiatan mengatakan sampah yang terkumpul didominasi plastik kemasan makanan dan minuman yang terbawa arus maupun ditinggalkan pengunjung.',
                    'Sampah yang telah dipilah selanjutnya diserahkan ke bank sampah desa untuk didaur ulang.',
                ]],
                ['Komunitas Sepeda Kebumen Galang Donasi untuk Korban Longsor', 'Kegiatan gowes amal berhasil mengumpulkan dana Rp45 juta untuk warga terdampak di Karangsambung.', [
                    'Ratusan pesepeda dari berbagai komunitas di Kebumen mengikuti gowes amal yang digelar untuk membantu korban tanah longsor di Kecamatan Karangsambung.',
                    'Peserta menempuh rute sejauh 30 kilometer sambil membagikan kotak donasi di sepanjang perjalanan.',
                    'Dana yang terkumpul akan disalurkan dalam bentuk bahan bangunan dan kebutuhan pokok bagi warga yang rumahnya rusak.',
                ]],
                ['Taman Baca Desa di Puring Hidupkan Minat Literasi Anak', 'Diinisiasi pemuda karang taruna, taman baca kini memiliki lebih dari 1.500 koleksi buku.', [
                    'Sekelompok pemuda karang taruna di Kecamatan Puring mendirikan taman baca untuk anak-anak desa yang jauh dari perpustakaan.',
                    'Koleksi buku diperoleh dari donasi warga, alumni, dan sejumlah penerbit. Setiap sore, taman baca ramai dikunjungi anak-anak sepulang sekolah.',
                    'Pengelola berencana menambah kegiatan mendongeng dan kelas menulis setiap akhir pekan.',
                ]],
            ],
            'kebijakan-daerah' => [
                ['Pemkab Kebumen Terbitkan Aturan Baru Penataan Pedagang Kaki Lima', 'Peraturan bupati terbaru mengatur zonasi dan jam operasional PKL di kawasan pusat kota.', [
                    'Pemerintah Kabupaten Kebumen menerbitkan peraturan bupati baru tentang penataan pedagang kaki lima di kawasan pusat kota.',
                    'Aturan tersebut membagi area berjualan menjadi tiga zona dengan jam operasional berbeda, serta mewajibkan pedagang menjaga kebersihan lapak.',
                    'Dinas terkait akan melakukan sosialisasi selama satu bulan sebelum penertiban mulai diberlakukan.',
                ]],
                ['Bupati Kebumen Tetapkan Status Siaga Darurat Bencana Hidrometeorologi', 'Status siaga berlaku hingga akhir musim hujan dan memungkinkan penggunaan dana tak terduga.', [
                    'Bupati Kebumen resmi menetapkan status siaga darurat bencana hidrometeorologi menyusul meningkatnya curah hujan di sejumlah kecamatan.',
                    'Dengan status ini, pemerintah daerah dapat menggunakan dana belanja tak terduga untuk penanganan cepat banjir, longsor, dan angin kencang.',
                    'Seluruh camat dan kepala desa diminta mengaktifkan posko siaga serta memperbarui data warga di daerah rawan bencana.',
                ], ['breaking']],
                ['Pemkab Kebumen Larang Penggunaan Kantong Plastik di Pasar Modern', 'Larangan bertahap mulai berlaku awal tahun depan dan akan diperluas ke pasar tradisional.', [
                    'Pemerintah Kabupaten Kebumen akan melarang penggunaan kantong plastik sekali pakai di pasar modern dan pusat perbelanjaan mulai awal tahun depan.',
                    'Kebijakan ini merupakan bagian dari upaya pengurangan sampah plastik yang terus meningkat di tempat pembuangan akhir.',
                    'Pelaku usaha diminta menyediakan alternatif tas belanja ramah lingkungan, sementara masyarakat diimbau membawa tas belanja sendiri.',
                ]],
            ],
            'dprd-kebumen' => [
                ['DPRD Kebumen Bahas Rancangan Perda Perlindungan Lahan Pertanian', 'Panitia khusus DPRD menargetkan raperda rampung sebelum akhir tahun anggaran.', [
                    'DPRD Kabupaten Kebumen mulai membahas rancangan peraturan daerah tentang perlindungan lahan pertanian pangan berkelanjutan.',
                    'Ketua pansus menyebut alih fungsi lahan sawah menjadi perumahan terus meningkat dalam lima tahun terakhir sehingga perlu payung hukum yang tegas.',
                    'Pembahasan akan melibatkan kelompok tani, akademisi, dan pengembang perumahan agar aturan yang dihasilkan berimbang.',
                ]],
                ['DPRD Kebumen Sahkan APBD Perubahan dengan Fokus Perbaikan Jalan', 'Anggaran perbaikan jalan kabupaten naik signifikan dibanding rencana awal.', [
                    'Rapat paripurna DPRD Kebumen mengesahkan APBD Perubahan tahun anggaran berjalan setelah melalui pembahasan bersama tim anggaran pemerintah daerah.',
                    'Sebagian besar tambahan anggaran dialokasikan untuk perbaikan jalan kabupaten yang rusak, terutama di wilayah utara dan selatan.',
                    'Pimpinan dewan meminta organisasi perangkat daerah segera merealisasikan program agar tidak terjadi penumpukan pekerjaan di akhir tahun.',
                ]],
                ['Komisi D DPRD Kebumen Sidak Puskesmas, Temukan Kekurangan Tenaga Medis', 'Dewan meminta dinas kesehatan menambah dokter di puskesmas yang melayani wilayah terpencil.', [
                    'Komisi D DPRD Kebumen melakukan inspeksi mendadak ke sejumlah puskesmas di wilayah pegunungan utara.',
                    'Dalam sidak tersebut, dewan menemukan beberapa puskesmas hanya memiliki satu dokter umum untuk melayani puluhan ribu warga.',
                    'Komisi D akan memanggil dinas kesehatan untuk membahas rencana penambahan tenaga medis dan insentif bagi dokter yang bertugas di daerah terpencil.',
                ]],
            ],
            'pelayanan-publik' => [
                ['Mal Pelayanan Publik Kebumen Tambah Layanan Malam Hari', 'Mulai bulan depan, sejumlah layanan administrasi kependudukan dibuka hingga pukul 20.00 WIB.', [
                    'Mal Pelayanan Publik Kebumen akan membuka layanan pada malam hari untuk memudahkan warga yang bekerja di siang hari.',
                    'Layanan yang tersedia meliputi perekaman KTP elektronik, pencetakan kartu keluarga, dan pengurusan akta kelahiran.',
                    'Kebijakan ini akan dievaluasi setelah tiga bulan berjalan berdasarkan jumlah pengunjung dan kepuasan masyarakat.',
                ]],
                ['Disdukcapil Kebumen Luncurkan Layanan Jemput Bola untuk Lansia', 'Petugas akan mendatangi rumah warga lansia dan penyandang disabilitas untuk perekaman KTP-el.', [
                    'Dinas Kependudukan dan Pencatatan Sipil Kebumen meluncurkan layanan jemput bola bagi warga lanjut usia dan penyandang disabilitas.',
                    'Warga cukup mendaftar melalui pemerintah desa, kemudian petugas akan datang membawa peralatan perekaman ke rumah.',
                    'Program ini diharapkan dapat meningkatkan cakupan kepemilikan dokumen kependudukan yang masih rendah di kelompok rentan.',
                ]],
                ['RSUD Kebumen Terapkan Antrean Online untuk Poliklinik', 'Pasien kini dapat mengambil nomor antrean dari rumah melalui aplikasi dan situs web.', [
                    'RSUD Kebumen resmi menerapkan sistem antrean online untuk seluruh layanan poliklinik rawat jalan.',
                    'Dengan sistem ini, pasien dapat memilih jadwal dokter dan memperkirakan waktu kedatangan sehingga tidak perlu menunggu lama di rumah sakit.',
                    'Manajemen rumah sakit tetap menyediakan loket antrean manual bagi pasien yang belum terbiasa menggunakan aplikasi.',
                ]],
            ],
            'peluang-bisnis' => [
                ['Peluang Usaha Budidaya Maggot Kian Diminati Warga Kebumen', 'Selain mengurangi sampah organik, maggot laku dijual sebagai pakan ternak dan ikan.', [
                    'Budidaya maggot atau larva lalat Black Soldier Fly mulai dilirik warga Kebumen sebagai peluang usaha baru.',
                    'Maggot dibudidayakan dengan memanfaatkan sampah organik rumah tangga dan pasar, lalu dijual sebagai pakan alternatif yang kaya protein.',
                    'Salah satu pembudidaya di Kecamatan Sruweng mengaku mampu meraup omzet hingga Rp6 juta per bulan dari penjualan maggot segar dan kering.',
                ]],
                ['Bank Daerah Tawarkan Kredit Usaha Rakyat Bunga Ringan untuk Pemula', 'Program pembiayaan ditujukan bagi pelaku usaha mikro yang baru merintis usaha di Kebumen.', [
                    'Sebuah bank daerah di Kebumen menawarkan program kredit usaha rakyat dengan bunga ringan khusus untuk wirausaha pemula.',
                    'Calon debitur cukup melampirkan surat keterangan usaha dari desa dan tidak diwajibkan menyertakan agunan tambahan untuk plafon tertentu.',
                    'Pihak bank juga menyediakan pendampingan pembukuan sederhana agar usaha yang dibiayai dapat berkembang secara berkelanjutan.',
                ], ['advertorial']],
                ['Jasa Titip Oleh-oleh Khas Kebumen Raup Untung dari Perantau', 'Permintaan lanting, sale pisang, dan gula kelapa melonjak menjelang hari raya.', [
                    'Usaha jasa titip oleh-oleh khas Kebumen tumbuh pesat seiring tingginya permintaan dari warga perantau di berbagai kota besar.',
                    'Produk yang paling banyak dipesan antara lain lanting, sale pisang, dan gula kelapa, yang dikirim melalui jasa ekspedisi.',
                    'Para pelaku usaha memanfaatkan media sosial dan grup percakapan perantau untuk menjangkau pelanggan.',
                ]],
            ],
            'pasar-harga' => [
                ['Harga Cabai Rawit di Pasar Tumenggungan Melonjak Jelang Musim Hujan', 'Harga cabai rawit merah naik hingga Rp70 ribu per kilogram akibat pasokan dari petani berkurang.', [
                    'Harga cabai rawit merah di Pasar Tumenggungan Kebumen melonjak dalam sepekan terakhir.',
                    'Pedagang menyebut pasokan dari sentra produksi berkurang karena banyak tanaman cabai rusak akibat hujan yang mulai turun.',
                    'Dinas Perdagangan berencana menggelar operasi pasar jika kenaikan harga terus berlanjut.',
                ]],
                ['Harga Beras Medium di Kebumen Stabil Usai Panen Raya', 'Pasokan melimpah membuat harga beras medium bertahan di kisaran Rp12.500 per kilogram.', [
                    'Harga beras medium di sejumlah pasar tradisional Kebumen terpantau stabil setelah panen raya di beberapa kecamatan.',
                    'Pedagang menyebut stok beras dari penggilingan lokal cukup melimpah sehingga harga tidak mengalami lonjakan.',
                    'Dinas Pertanian memperkirakan stok beras aman hingga beberapa bulan ke depan.',
                ]],
                ['Operasi Pasar Minyak Goreng Digelar di Lima Kecamatan', 'Warga dapat membeli minyak goreng dengan harga lebih murah dari harga pasaran.', [
                    'Pemerintah Kabupaten Kebumen menggelar operasi pasar minyak goreng di lima kecamatan untuk menekan harga yang mulai merangkak naik.',
                    'Setiap warga dibatasi membeli maksimal dua liter dengan menunjukkan KTP agar distribusi lebih merata.',
                    'Antusiasme warga cukup tinggi, terlihat dari antrean panjang di setiap titik operasi pasar sejak pagi hari.',
                ]],
            ],
            'koperasi-umkm' => [
                ['Koperasi Perempuan di Petanahan Sukses Pasarkan Keripik Singkong ke Luar Jawa', 'Produk keripik singkong olahan anggota koperasi kini rutin dikirim ke Kalimantan dan Sulawesi.', [
                    'Sebuah koperasi yang beranggotakan ibu rumah tangga di Kecamatan Petanahan berhasil memasarkan keripik singkong hingga ke luar Pulau Jawa.',
                    'Ketua koperasi mengatakan pemasaran melalui marketplace dan media sosial menjadi kunci perluasan pasar dalam dua tahun terakhir.',
                    'Saat ini koperasi mampu memproduksi hingga 500 kilogram keripik per minggu dan menyerap tenaga kerja dari warga sekitar.',
                ]],
                ['Ratusan UMKM Ikuti Pelatihan Digital Marketing Gratis', 'Pelatihan meliputi pembuatan konten, foto produk, dan pengelolaan toko online.', [
                    'Sebanyak 250 pelaku UMKM di Kebumen mengikuti pelatihan digital marketing gratis yang diselenggarakan dinas koperasi dan UMKM.',
                    'Peserta dibekali keterampilan membuat foto produk menarik, menulis deskripsi, hingga mengelola iklan berbayar di media sosial.',
                    'Usai pelatihan, peserta akan didampingi selama tiga bulan untuk memastikan materi benar-benar diterapkan dalam usaha mereka.',
                ]],
                ['Pameran Produk UMKM Kebumen Catat Transaksi Rp1,2 Miliar', 'Pameran tiga hari di alun-alun diikuti lebih dari seratus pelaku usaha lokal.', [
                    'Pameran produk UMKM yang digelar di Alun-alun Kebumen selama tiga hari mencatat nilai transaksi mencapai Rp1,2 miliar.',
                    'Produk yang paling laris antara lain kerajinan anyaman, batik, dan aneka olahan makanan khas Kebumen.',
                    'Panitia berencana menjadikan pameran ini sebagai agenda tahunan dengan melibatkan lebih banyak pelaku usaha dari desa.',
                ], ['advertorial']],
            ],
            'destinasi-wisata' => [
                ['Goa Jatijajar Siapkan Wahana Baru untuk Libur Akhir Tahun', 'Pengelola menambah jalur susur goa dan area edukasi geologi untuk menarik wisatawan.', [
                    'Objek wisata Goa Jatijajar bersiap menyambut libur akhir tahun dengan menghadirkan sejumlah wahana baru.',
                    'Pengelola menambah jalur susur goa dengan pemandu serta area edukasi tentang proses terbentuknya stalaktit dan stalagmit.',
                    'Selama libur akhir tahun, pengelola menargetkan kunjungan hingga 50 ribu wisatawan.',
                ]],
                ['Pantai Menganti Raih Penghargaan Destinasi Wisata Terbaik Jawa Tengah', 'Keindahan tebing karang dan pengelolaan berbasis masyarakat menjadi nilai unggul.', [
                    'Pantai Menganti di Kecamatan Ayah meraih penghargaan sebagai salah satu destinasi wisata terbaik tingkat Jawa Tengah.',
                    'Dewan juri menilai Pantai Menganti unggul dari sisi keindahan alam, kebersihan, serta keterlibatan warga dalam pengelolaan wisata.',
                    'Pengelola berkomitmen menjaga kelestarian kawasan sekaligus meningkatkan fasilitas bagi pengunjung.',
                ], ['advertorial']],
                ['Benteng Van der Wijck Gombong Ramai Dikunjungi Pelajar', 'Wisata sejarah peninggalan Belanda ini menjadi tujuan favorit kunjungan studi sekolah.', [
                    'Benteng Van der Wijck di Gombong ramai dikunjungi rombongan pelajar dari berbagai daerah dalam beberapa pekan terakhir.',
                    'Benteng berbentuk segi delapan yang dibangun pada abad ke-19 ini menjadi sarana belajar sejarah yang menarik bagi siswa.',
                    'Pengelola menyediakan pemandu yang menjelaskan sejarah benteng dan peranannya pada masa kolonial.',
                ]],
            ],
            'seni-tradisi' => [
                ['Kesenian Cepetan Alas Kembali Dipentaskan di Karanggayam', 'Pentas kesenian tradisional ini menjadi bagian dari upaya pelestarian budaya lokal oleh pemuda desa.', [
                    'Kesenian tradisional Cepetan Alas kembali dipentaskan oleh kelompok pemuda di Kecamatan Karanggayam.',
                    'Kesenian yang menampilkan penari bertopeng raksasa ini menggambarkan kisah pembukaan hutan oleh para leluhur.',
                    'Pemerintah desa berencana menjadikan pentas Cepetan Alas sebagai agenda rutin setiap bulan Suro.',
                ]],
                ['Festival Jamjaneng Hadirkan Puluhan Grup dari Seluruh Kebumen', 'Kesenian musik religi khas Kebumen ini terus dilestarikan lintas generasi.', [
                    'Festival kesenian Jamjaneng digelar di Pendopo Kabupaten Kebumen dengan menghadirkan puluhan grup dari berbagai kecamatan.',
                    'Jamjaneng merupakan kesenian musik bernuansa religi yang memadukan terbang, kendang, dan syair berbahasa Jawa.',
                    'Panitia berharap festival ini dapat menarik minat generasi muda untuk ikut melestarikan kesenian khas Kebumen.',
                ]],
                ['Sanggar Tari di Kebumen Ajarkan Lengger kepada Anak-anak', 'Puluhan anak belajar tari lengger setiap akhir pekan secara gratis.', [
                    'Sebuah sanggar tari di Kebumen membuka kelas tari lengger gratis bagi anak-anak setiap akhir pekan.',
                    'Pemilik sanggar mengatakan kelas ini dibuka untuk mengenalkan tari tradisional sejak dini di tengah gempuran budaya populer.',
                    'Anak-anak yang telah mahir akan dilibatkan dalam pentas seni pada berbagai acara di tingkat kabupaten.',
                ]],
            ],
            'kuliner-khas' => [
                ['Sate Ambal, Kuliner Legendaris yang Tetap Diburu Pemudik', 'Sate ayam dengan bumbu tempe khas Ambal tetap menjadi buruan wisatawan yang melintas jalur selatan.', [
                    'Sate Ambal merupakan kuliner khas Kebumen yang dikenal dengan bumbu berbahan dasar tempe dan cabai.',
                    'Berbeda dengan sate pada umumnya, daging ayam kampung pada sate Ambal dimarinasi bumbu rempah sebelum dibakar.',
                    'Deretan warung sate di sepanjang jalan Ambal selalu ramai, terutama saat musim mudik dan libur panjang.',
                ]],
                ['Lanting, Camilan Renyah Khas Kebumen yang Mendunia', 'Camilan berbahan singkong ini kini diekspor ke beberapa negara Asia.', [
                    'Lanting, camilan berbentuk angka delapan yang terbuat dari singkong, menjadi oleh-oleh wajib saat berkunjung ke Kebumen.',
                    'Sentra produksi lanting di Kecamatan Kuwarasan memproduksi berbagai varian rasa, mulai dari bawang, pedas, hingga keju.',
                    'Berkat pemasaran digital, lanting Kebumen kini telah menembus pasar ekspor ke Malaysia, Singapura, dan Hongkong.',
                ], ['advertorial']],
                ['Mengenal Jipang Kacang, Jajanan Tradisional yang Mulai Langka', 'Hanya segelintir perajin yang masih memproduksi jajanan berbahan kacang dan gula merah ini.', [
                    'Jipang kacang merupakan jajanan tradisional Kebumen yang terbuat dari campuran kacang tanah dan gula merah.',
                    'Saat ini hanya beberapa perajin yang masih memproduksi jipang kacang secara rumahan karena proses pembuatannya yang cukup rumit.',
                    'Para perajin berharap ada regenerasi agar jajanan tradisional ini tidak punah.',
                ]],
            ],
            'sekolah-kampus' => [
                ['Universitas di Kebumen Buka Program Studi Baru Bidang Teknologi', 'Program studi informatika dan sistem informasi mulai menerima mahasiswa tahun ajaran depan.', [
                    'Salah satu perguruan tinggi swasta di Kebumen resmi membuka dua program studi baru di bidang teknologi informasi.',
                    'Rektor mengatakan pembukaan program studi ini menjawab kebutuhan tenaga kerja digital yang terus meningkat.',
                    'Kampus juga menjalin kerja sama dengan sejumlah perusahaan teknologi untuk program magang mahasiswa.',
                ], ['advertorial']],
                ['Puluhan Sekolah di Kebumen Terapkan Kurikulum Berbasis Proyek', 'Siswa diajak memecahkan masalah nyata di lingkungan sekitar sekolah.', [
                    'Puluhan sekolah dasar dan menengah di Kebumen mulai menerapkan pembelajaran berbasis proyek secara menyeluruh.',
                    'Siswa diajak merancang solusi atas persoalan di lingkungan sekitar, seperti pengelolaan sampah dan penghijauan sekolah.',
                    'Dinas pendidikan menilai metode ini mampu meningkatkan kreativitas dan kemampuan kerja sama siswa.',
                ]],
                ['Gedung SD di Kebumen Rusak, Siswa Terpaksa Belajar di Teras', 'Atap ruang kelas ambrol akibat hujan deras dan belum diperbaiki.', [
                    'Siswa sebuah sekolah dasar negeri di wilayah utara Kebumen terpaksa belajar di teras sekolah setelah atap ruang kelas ambrol.',
                    'Kepala sekolah telah melaporkan kerusakan tersebut kepada dinas pendidikan dan berharap segera ada perbaikan.',
                    'Dinas pendidikan menyatakan perbaikan akan dimasukkan dalam anggaran rehabilitasi sekolah tahun ini.',
                ]],
            ],
            'prestasi' => [
                ['Siswa SMA di Kebumen Raih Medali Emas Olimpiade Sains Nasional', 'Prestasi ini menjadi yang pertama bagi Kebumen di bidang kimia dalam lima tahun terakhir.', [
                    'Seorang siswa SMA negeri di Kebumen berhasil meraih medali emas Olimpiade Sains Nasional bidang kimia.',
                    'Siswa tersebut mengaku mempersiapkan diri selama hampir satu tahun dengan bimbingan guru dan latihan soal secara mandiri.',
                    'Bupati Kebumen menyampaikan apresiasi dan menjanjikan beasiswa pendidikan bagi siswa berprestasi tersebut.',
                ]],
                ['Tim Robotik SMK Kebumen Juara Kompetisi Tingkat Nasional', 'Robot pemilah sampah buatan siswa mengungguli puluhan tim dari berbagai provinsi.', [
                    'Tim robotik dari sebuah SMK di Kebumen berhasil menjuarai kompetisi robotik tingkat nasional di Jakarta.',
                    'Robot pemilah sampah otomatis karya siswa dinilai inovatif dan memiliki manfaat nyata bagi masyarakat.',
                    'Pihak sekolah berencana mengembangkan robot tersebut agar dapat digunakan di bank sampah desa.',
                ]],
                ['Mahasiswa Asal Kebumen Raih Beasiswa Doktoral ke Jepang', 'Ia akan meneliti teknologi pengolahan limbah pertanian menjadi energi terbarukan.', [
                    'Seorang mahasiswa asal Kebumen berhasil meraih beasiswa penuh untuk menempuh program doktoral di Jepang.',
                    'Ia akan meneliti teknologi pengolahan limbah pertanian menjadi energi terbarukan yang bisa diterapkan di desa-desa.',
                    'Keberhasilan ini diharapkan menjadi motivasi bagi pelajar Kebumen lainnya untuk terus berprestasi.',
                ]],
            ],
            'beasiswa' => [
                ['Pendaftaran Beasiswa Kuliah dari Pemkab Kebumen Dibuka', 'Beasiswa diperuntukkan bagi lulusan SMA/SMK sederajat dari keluarga kurang mampu.', [
                    'Pemerintah Kabupaten Kebumen membuka pendaftaran beasiswa kuliah untuk tahun ajaran baru.',
                    'Beasiswa mencakup biaya pendidikan penuh serta uang saku bulanan selama masa studi.',
                    'Pendaftaran dilakukan secara daring dan calon penerima wajib melampirkan surat keterangan tidak mampu dari desa.',
                ]],
                ['Baznas Kebumen Salurkan Beasiswa untuk 300 Pelajar', 'Bantuan diberikan kepada pelajar berprestasi dari keluarga prasejahtera.', [
                    'Badan Amil Zakat Nasional Kabupaten Kebumen menyalurkan beasiswa kepada 300 pelajar tingkat SD hingga SMA.',
                    'Penerima beasiswa merupakan siswa berprestasi yang berasal dari keluarga prasejahtera di berbagai kecamatan.',
                    'Ketua Baznas berharap bantuan ini dapat membantu siswa melanjutkan pendidikan tanpa terkendala biaya.',
                ]],
                ['Tips Lolos Seleksi Beasiswa LPDP dari Alumni Asal Kebumen', 'Persiapan esai dan wawancara menjadi kunci utama keberhasilan.', [
                    'Sejumlah alumni penerima beasiswa LPDP asal Kebumen berbagi tips lolos seleksi dalam sebuah seminar di aula kabupaten.',
                    'Mereka menekankan pentingnya menyusun esai yang jujur dan relevan dengan kontribusi bagi daerah asal.',
                    'Selain itu, persiapan wawancara dan kemampuan bahasa Inggris juga menjadi faktor penentu kelulusan.',
                ]],
            ],
            'sepak-bola' => [
                ['Persak Kebumen Matangkan Persiapan Jelang Liga 3 Jawa Tengah', 'Tim berjuluk Laskar Jaka Sangkrip menggelar uji coba rutin untuk mematangkan strategi.', [
                    'Persak Kebumen terus mematangkan persiapan menjelang bergulirnya kompetisi Liga 3 Jawa Tengah.',
                    'Pelatih kepala menyebut timnya telah menjalani enam laga uji coba dengan hasil yang cukup memuaskan.',
                    'Manajemen berharap dukungan suporter dapat menjadi motivasi tambahan bagi para pemain.',
                ]],
                ['Persak Kebumen Menang Telak 3-0 di Laga Perdana', 'Dua gol dicetak pada babak pertama dan satu gol penutup di menit akhir.', [
                    'Persak Kebumen mengawali kompetisi dengan kemenangan meyakinkan 3-0 atas tim tamu di Stadion Candradimuka.',
                    'Dua gol tercipta pada babak pertama melalui skema serangan balik cepat, sementara gol ketiga lahir dari tendangan bebas di menit akhir.',
                    'Pelatih mengapresiasi kerja keras pemain namun meminta tim tetap fokus menghadapi laga tandang berikutnya.',
                ], ['breaking']],
                ['Stadion Candradimuka Direnovasi Jelang Musim Kompetisi', 'Perbaikan meliputi rumput lapangan, tribun penonton, dan lampu stadion.', [
                    'Stadion Candradimuka Kebumen menjalani renovasi untuk memenuhi standar penyelenggaraan kompetisi resmi.',
                    'Pekerjaan meliputi penggantian rumput lapangan, perbaikan tribun penonton, serta pemasangan lampu stadion.',
                    'Renovasi ditargetkan rampung sebelum musim kompetisi dimulai agar Persak dapat bermain di kandang sendiri.',
                ]],
            ],
            'turnamen-lokal' => [
                ['Turnamen Bola Voli Antardesa Diikuti 48 Tim', 'Turnamen digelar sebulan penuh di lapangan desa dan disiarkan langsung lewat media sosial.', [
                    'Sebanyak 48 tim dari berbagai desa di Kebumen mengikuti turnamen bola voli antardesa yang digelar selama satu bulan.',
                    'Pertandingan berlangsung setiap malam dan selalu dipadati penonton dari desa sekitar.',
                    'Panitia juga menyiarkan pertandingan secara langsung melalui media sosial agar warga perantau dapat ikut menyaksikan.',
                ]],
                ['Kejuaraan Bulu Tangkis Pelajar Kebumen Lahirkan Bibit Baru', 'Juara turnamen akan dibina di pusat pelatihan daerah.', [
                    'Kejuaraan bulu tangkis tingkat pelajar se-Kabupaten Kebumen berhasil menjaring sejumlah atlet muda berbakat.',
                    'Turnamen diikuti lebih dari 200 peserta dari tingkat SD hingga SMA yang bertanding di berbagai kategori.',
                    'Para juara akan mendapatkan kesempatan mengikuti pembinaan di pusat pelatihan olahraga daerah.',
                ]],
                ['Lomba Lari 10K Kebumen Diikuti Ribuan Peserta', 'Rute melewati kawasan perkotaan dan persawahan dengan pemandangan Gunung Sumbing.', [
                    'Ribuan pelari dari berbagai daerah mengikuti lomba lari 10K yang digelar di Kebumen pada Minggu pagi.',
                    'Rute lomba melewati kawasan perkotaan, persawahan, hingga perkampungan dengan pemandangan alam yang menarik.',
                    'Panitia berencana menambah kategori half marathon pada penyelenggaraan tahun depan.',
                ], ['advertorial']],
            ],
            'atlet-daerah' => [
                ['Atlet Pencak Silat Kebumen Raih Emas di Porprov Jawa Tengah', 'Kemenangan diraih setelah mengalahkan juara bertahan di babak final.', [
                    'Atlet pencak silat asal Kebumen berhasil meraih medali emas pada Pekan Olahraga Provinsi Jawa Tengah.',
                    'Di babak final, ia mengalahkan juara bertahan dengan keunggulan poin yang cukup jauh.',
                    'KONI Kebumen menyatakan akan memberikan bonus dan terus membina atlet tersebut menuju ajang nasional.',
                ]],
                ['Lifter Muda Kebumen Pecahkan Rekor Daerah Angkat Besi', 'Atlet berusia 17 tahun itu mengangkat total beban 210 kilogram.', [
                    'Seorang lifter muda asal Kebumen berhasil memecahkan rekor daerah cabang angkat besi kelas 67 kilogram.',
                    'Atlet berusia 17 tahun tersebut mencatatkan total angkatan 210 kilogram dalam kejuaraan tingkat provinsi.',
                    'Pelatih optimistis anak didiknya mampu bersaing di tingkat nasional dalam beberapa tahun ke depan.',
                ]],
                ['KONI Kebumen Siapkan Bonus untuk Atlet Berprestasi', 'Bonus diberikan kepada atlet peraih medali di ajang provinsi dan nasional.', [
                    'KONI Kabupaten Kebumen menyiapkan bonus bagi atlet yang meraih medali di berbagai ajang provinsi dan nasional.',
                    'Ketua KONI menyebut bonus ini sebagai bentuk apresiasi sekaligus motivasi bagi atlet untuk terus berprestasi.',
                    'Selain bonus, KONI juga akan meningkatkan fasilitas latihan di sejumlah cabang olahraga unggulan.',
                ]],
            ],
            'pertanian-perkebunan' => [
                ['Petani Gula Kelapa Kebumen Tembus Pasar Ekspor Eropa', 'Gula semut organik produksi kelompok tani di Kebumen kini dikirim rutin ke Belanda dan Jerman.', [
                    'Gula semut organik hasil olahan petani nira kelapa di Kebumen berhasil menembus pasar ekspor Eropa.',
                    'Kelompok tani telah mengantongi sertifikasi organik internasional yang menjadi syarat utama ekspor.',
                    'Peningkatan permintaan diharapkan dapat menaikkan kesejahteraan para penderes nira kelapa.',
                ]],
                ['Petani Kebumen Mulai Tanam Padi Varietas Tahan Kekeringan', 'Varietas baru diharapkan menjaga produktivitas saat musim kemarau panjang.', [
                    'Petani di sejumlah kecamatan di Kebumen mulai menanam padi varietas baru yang tahan terhadap kekeringan.',
                    'Dinas Pertanian membagikan benih secara gratis kepada kelompok tani yang lahannya kerap kekurangan air.',
                    'Hasil uji coba menunjukkan produktivitas varietas ini tetap stabil meski curah hujan rendah.',
                ]],
                ['Harga Gabah Anjlok, Petani Kebumen Minta Bulog Turun Tangan', 'Harga gabah kering panen turun di bawah harga pembelian pemerintah.', [
                    'Petani di Kebumen mengeluhkan anjloknya harga gabah kering panen saat musim panen raya.',
                    'Harga gabah di tingkat petani turun di bawah harga pembelian pemerintah sehingga petani merugi.',
                    'Kelompok tani meminta Bulog segera menyerap gabah petani untuk menstabilkan harga.',
                ]],
            ],
            'perikanan-kelautan' => [
                ['Nelayan Kebumen Panen Ikan Layur, Harga Tembus Rp50 Ribu per Kilo', 'Hasil tangkapan melimpah di perairan selatan membawa berkah bagi nelayan.', [
                    'Nelayan di pesisir selatan Kebumen sedang menikmati musim panen ikan layur dalam beberapa pekan terakhir.',
                    'Harga ikan layur di tempat pelelangan ikan mencapai Rp50 ribu per kilogram karena tingginya permintaan ekspor.',
                    'Nelayan berharap cuaca tetap bersahabat agar musim panen dapat berlangsung lebih lama.',
                ]],
                ['Budidaya Udang Vaname di Pesisir Kebumen Terus Berkembang', 'Puluhan tambak baru dibuka dengan teknologi kolam bundar.', [
                    'Budidaya udang vaname di pesisir Kebumen terus berkembang dengan dibukanya puluhan tambak baru.',
                    'Para pembudidaya mulai menggunakan teknologi kolam bundar yang lebih hemat lahan dan mudah dikontrol.',
                    'Dinas Kelautan dan Perikanan mengingatkan pentingnya pengelolaan limbah tambak agar tidak mencemari lingkungan.',
                ]],
                ['Gelombang Tinggi, Nelayan Kebumen Diimbau Tidak Melaut', 'BMKG memperkirakan gelombang setinggi empat meter di perairan selatan Jawa.', [
                    'BMKG mengeluarkan peringatan gelombang tinggi hingga empat meter di perairan selatan Jawa, termasuk wilayah Kebumen.',
                    'Nelayan diimbau tidak melaut sementara waktu demi keselamatan.',
                    'Sebagian nelayan memanfaatkan waktu ini untuk memperbaiki jaring dan perahu.',
                ], ['breaking']],
            ],
            'kerajinan-rakyat' => [
                ['Anyaman Pandan Kebumen Diminati Pasar Mancanegara', 'Tas dan tikar anyaman pandan kini dikirim ke Jepang dan Australia.', [
                    'Kerajinan anyaman pandan dari Kebumen semakin diminati pasar mancanegara.',
                    'Produk seperti tas, tikar, dan topi anyaman kini rutin dikirim ke Jepang dan Australia.',
                    'Para perajin berharap ada dukungan pemerintah dalam penyediaan bahan baku dan pelatihan desain.',
                ]],
                ['Perajin Genteng Sokka Bertahan di Tengah Gempuran Genteng Metal', 'Genteng tanah liat khas Kebumen ini tetap memiliki pasar setia.', [
                    'Perajin genteng Sokka di Kebumen tetap bertahan meski menghadapi persaingan ketat dengan genteng metal.',
                    'Genteng Sokka dikenal memiliki kualitas baik dan tahan lama sehingga tetap diminati sebagian konsumen.',
                    'Para perajin mulai berinovasi dengan membuat genteng berglasir untuk menarik minat pembeli.',
                ]],
                ['Batik Tulis Kebumen Kembangkan Motif Khas Daerah', 'Motif burung walet dan gunung karst menjadi ciri khas batik Kebumen.', [
                    'Perajin batik tulis di Kebumen terus mengembangkan motif-motif khas daerah untuk memperkuat identitas.',
                    'Motif burung walet dan gunung karst menjadi ciri khas yang membedakan batik Kebumen dari batik daerah lain.',
                    'Pemerintah daerah mendorong aparatur sipil negara mengenakan batik Kebumen pada hari tertentu.',
                ], ['advertorial']],
            ],
            'industri-kreatif' => [
                ['Kreator Konten Kebumen Promosikan Wisata Lewat Video Pendek', 'Video tentang pantai dan goa di Kebumen ditonton jutaan kali di media sosial.', [
                    'Sejumlah kreator konten asal Kebumen aktif mempromosikan potensi wisata daerah melalui video pendek di media sosial.',
                    'Beberapa video tentang keindahan pantai dan goa di Kebumen berhasil ditonton jutaan kali.',
                    'Dinas Pariwisata berencana menggandeng para kreator konten dalam program promosi wisata daerah.',
                ]],
                ['Startup Lokal Kebumen Kembangkan Aplikasi Pemesanan Hasil Tani', 'Aplikasi menghubungkan petani langsung dengan pembeli tanpa perantara.', [
                    'Sekelompok anak muda Kebumen mengembangkan aplikasi yang menghubungkan petani langsung dengan pembeli.',
                    'Melalui aplikasi ini, petani dapat menjual hasil panen dengan harga lebih baik tanpa melalui tengkulak.',
                    'Saat ini aplikasi telah digunakan oleh ratusan petani di beberapa kecamatan.',
                ]],
                ['Kebumen Creative Hub Resmi Dibuka untuk Anak Muda', 'Ruang kreatif ini menyediakan fasilitas co-working, studio, dan pelatihan gratis.', [
                    'Pemerintah Kabupaten Kebumen meresmikan Kebumen Creative Hub sebagai ruang berkreasi bagi anak muda.',
                    'Fasilitas yang tersedia meliputi ruang kerja bersama, studio foto dan video, serta ruang pelatihan.',
                    'Berbagai kelas gratis seperti desain grafis, fotografi, dan pemrograman akan digelar secara rutin.',
                ]],
            ],
            'geopark-kebumen' => [
                ['Geopark Kebumen Bersiap Menuju Pengakuan UNESCO Global Geopark', 'Tim penilai dijadwalkan melakukan verifikasi lapangan ke sejumlah situs geologi tahun depan.', [
                    'Geopark Nasional Kebumen terus berbenah menuju pengakuan sebagai UNESCO Global Geopark.',
                    'Pengelola memperkuat konservasi situs geologi, edukasi masyarakat, serta pengembangan ekonomi warga di sekitar kawasan.',
                    'Situs seperti Bukit Wagir Sambeng dan kawasan karst Gombong Selatan menjadi andalan dalam proses penilaian.',
                ]],
                ['Kampus Lapangan Geologi Karangsambung Jadi Tujuan Wisata Edukasi', 'Pengunjung dapat melihat batuan berusia ratusan juta tahun.', [
                    'Kampus lapangan geologi di Karangsambung semakin ramai dikunjungi sebagai tujuan wisata edukasi.',
                    'Di kawasan ini, pengunjung dapat melihat batuan berusia ratusan juta tahun yang menjadi bukti tumbukan lempeng bumi.',
                    'Pengelola menyediakan pemandu dan museum mini untuk menjelaskan sejarah geologi kawasan tersebut.',
                ]],
                ['Warga Sekitar Geopark Kebumen Dilatih Menjadi Pemandu Wisata', 'Pelatihan bertujuan meningkatkan ekonomi masyarakat di kawasan geopark.', [
                    'Puluhan warga di sekitar kawasan Geopark Kebumen mengikuti pelatihan menjadi pemandu wisata.',
                    'Peserta dibekali pengetahuan geologi dasar, teknik bercerita, serta pertolongan pertama.',
                    'Pelatihan ini diharapkan dapat membuka lapangan kerja baru dan meningkatkan ekonomi masyarakat setempat.',
                ]],
            ],
            'nasional' => [
                ['Pemerintah Pusat Alokasikan Dana Desa Lebih Besar Tahun Depan', 'Kenaikan dana desa diprioritaskan untuk ketahanan pangan dan penanganan stunting.', [
                    'Pemerintah pusat mengumumkan kenaikan alokasi dana desa untuk tahun anggaran mendatang.',
                    'Dana tersebut diprioritaskan untuk program ketahanan pangan, penanganan stunting, dan pembangunan infrastruktur desa.',
                    'Pemerintah daerah diminta memperkuat pengawasan agar dana desa digunakan tepat sasaran.',
                ]],
                ['BMKG Prediksi Puncak Musim Hujan Terjadi Januari', 'Masyarakat diimbau mewaspadai potensi banjir dan tanah longsor.', [
                    'Badan Meteorologi, Klimatologi, dan Geofisika memprediksi puncak musim hujan di sebagian besar wilayah Indonesia terjadi pada Januari.',
                    'BMKG mengimbau masyarakat mewaspadai potensi bencana hidrometeorologi seperti banjir dan tanah longsor.',
                    'Pemerintah daerah diminta menyiapkan langkah mitigasi sejak dini.',
                ]],
                ['Kementerian Pendidikan Umumkan Jadwal Ujian Nasional Berbasis Komputer', 'Pelaksanaan ujian dibagi dalam beberapa gelombang sesuai kesiapan sekolah.', [
                    'Kementerian Pendidikan mengumumkan jadwal pelaksanaan asesmen berbasis komputer untuk tahun ajaran berjalan.',
                    'Pelaksanaan ujian dibagi dalam beberapa gelombang menyesuaikan kesiapan perangkat di masing-masing sekolah.',
                    'Sekolah diminta melakukan simulasi agar siswa terbiasa dengan sistem ujian berbasis komputer.',
                ]],
            ],
            'internasional' => [
                ['Harga Minyak Dunia Naik Akibat Ketegangan di Timur Tengah', 'Kenaikan harga dikhawatirkan berdampak pada biaya energi di dalam negeri.', [
                    'Harga minyak mentah dunia mengalami kenaikan signifikan menyusul meningkatnya ketegangan di kawasan Timur Tengah.',
                    'Para analis memperkirakan harga masih akan berfluktuasi dalam beberapa pekan ke depan.',
                    'Pemerintah menyatakan terus memantau perkembangan harga untuk menjaga stabilitas energi nasional.',
                ]],
                ['Indonesia dan Jepang Sepakati Kerja Sama Pengembangan Energi Terbarukan', 'Kerja sama mencakup investasi pembangkit tenaga surya dan angin.', [
                    'Pemerintah Indonesia dan Jepang menandatangani nota kesepahaman kerja sama pengembangan energi terbarukan.',
                    'Kerja sama ini mencakup investasi pembangkit listrik tenaga surya dan angin di sejumlah wilayah Indonesia.',
                    'Kedua negara berharap kerja sama ini dapat mempercepat transisi menuju energi bersih.',
                ]],
                ['Pekerja Migran Asal Kebumen Pulang dengan Selamat dari Malaysia', 'Kepulangan difasilitasi pemerintah daerah bersama perwakilan RI di Kuala Lumpur.', [
                    'Sejumlah pekerja migran asal Kebumen yang bermasalah di Malaysia akhirnya dapat dipulangkan dengan selamat.',
                    'Kepulangan mereka difasilitasi pemerintah daerah bersama perwakilan Republik Indonesia di Kuala Lumpur.',
                    'Pemerintah daerah mengimbau warga yang ingin bekerja ke luar negeri agar menggunakan jalur resmi.',
                ]],
            ],
        ];
    }
}
