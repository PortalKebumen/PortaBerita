<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Database\Seeder;

class PageMenuSeeder extends Seeder
{
    /**
     * Halaman statis bawaan + susunan menu awal (navbar, footer, footer bawah).
     * Aman dijalankan ulang: halaman dicari per slug, menu hanya diisi bila lokasinya masih kosong.
     */
    public function run(): void
    {
        $site = SiteSetting::get('site_name', 'PortalKebumen.com');
        $email = SiteSetting::get('editorial_email', 'redaksi@portalkebumen.com');

        $pages = [];
        foreach ($this->pages($site, $email) as $slug => [$title, $metaDescription, $content]) {
            $pages[$slug] = Page::withoutEvents(fn () => Page::firstOrCreate(
                ['slug' => $slug],
                ['title' => $title, 'meta_description' => $metaDescription, 'content' => $content, 'status' => 'published']
            ));
        }

        MenuItem::withoutEvents(function () use ($pages, $email) {
            $this->seedNavbar();
            $this->seedFooter($pages, $email);
            $this->seedFooterBottom($pages);
        });

        MenuItem::flushCache();
    }

    private function seedNavbar(): void
    {
        if (MenuItem::where('location', 'navbar')->exists()) {
            return;
        }

        // Kategori utama (sub-kategori otomatis tampil sebagai dropdown), lalu halaman Tentang Kami
        $categories = Category::whereNull('parent_id')->orderBy('id')->get();
        $categories->each(fn (Category $c, int $i) => MenuItem::create([
            'location' => 'navbar', 'type' => 'category', 'category_id' => $c->id, 'sort_order' => $i,
        ]));

        if ($about = Page::where('slug', 'tentang-kami')->first()) {
            MenuItem::create(['location' => 'navbar', 'type' => 'page', 'page_id' => $about->id, 'sort_order' => $categories->count()]);
        }
    }

    private function seedFooter(array $pages, string $email): void
    {
        if (MenuItem::where('location', 'footer')->exists()) {
            return;
        }

        $columns = [
            'Tentang Kami' => ['tentang-kami', 'redaksi', 'hubungi-kami'],
            'Layanan' => ['pasang-iklan', ['Kirim Berita / Rilis', 'mailto:' . $email . '?subject=Kirim%20Berita'], 'kebijakan-privasi'],
            'Informasi' => ['pedoman-media-siber', 'kode-etik-jurnalistik', 'syarat-ketentuan', 'disclaimer'],
        ];

        $order = 0;
        foreach ($columns as $heading => $links) {
            $column = MenuItem::create(['location' => 'footer', 'type' => 'heading', 'label' => $heading, 'sort_order' => $order++]);

            foreach ($links as $i => $link) {
                is_array($link)
                    ? MenuItem::create(['location' => 'footer', 'parent_id' => $column->id, 'type' => 'url', 'label' => $link[0], 'url' => $link[1], 'sort_order' => $i])
                    : MenuItem::create(['location' => 'footer', 'parent_id' => $column->id, 'type' => 'page', 'page_id' => $pages[$link]->id, 'sort_order' => $i]);
            }
        }
    }

    private function seedFooterBottom(array $pages): void
    {
        if (MenuItem::where('location', 'footer_bottom')->exists()) {
            return;
        }

        foreach (['pedoman-media-siber', 'kebijakan-privasi', 'syarat-ketentuan', 'disclaimer'] as $i => $slug) {
            MenuItem::create(['location' => 'footer_bottom', 'type' => 'page', 'page_id' => $pages[$slug]->id, 'sort_order' => $i]);
        }
    }

    /**
     * Isi awal halaman. Bagian bertanda [ ... ] perlu dilengkapi redaksi lewat Admin > Halaman.
     *
     * @return array<string, array{0: string, 1: string, 2: string}>
     */
    private function pages(string $site, string $email): array
    {
        return [
            'tentang-kami' => ['Tentang Kami', "Profil {$site}, media siber yang menyajikan kabar terkini seputar Kabupaten Kebumen.", <<<HTML
<p><strong>{$site}</strong> adalah media siber yang menyajikan kabar terkini, terpercaya, dan mendalam seputar Kabupaten Kebumen dan sekitarnya: pemerintahan, ekonomi dan UMKM, pendidikan, olahraga, wisata dan budaya, hingga potensi daerah.</p>
<h2>Visi</h2>
<p>Menjadi rujukan utama informasi Kebumen yang akurat, berimbang, dan bermanfaat bagi masyarakat.</p>
<h2>Misi</h2>
<ul>
<li>Menyajikan berita yang terverifikasi dan mematuhi Kode Etik Jurnalistik.</li>
<li>Mengangkat potensi daerah, UMKM, dan prestasi warga Kebumen.</li>
<li>Menjadi ruang dialog publik yang sehat dan konstruktif.</li>
</ul>
<h2>Badan Hukum</h2>
<p>[Isi nama badan hukum, nomor akta pendirian, dan pengesahan Kemenkumham.]</p>
HTML],
            'redaksi' => ['Susunan Redaksi', "Susunan redaksi dan penanggung jawab {$site}.", <<<HTML
<p>Susunan redaksi {$site}:</p>
<ul>
<li><strong>Pemimpin Umum:</strong> [nama]</li>
<li><strong>Pemimpin Redaksi / Penanggung Jawab:</strong> [nama]</li>
<li><strong>Redaktur Pelaksana:</strong> [nama]</li>
<li><strong>Redaktur:</strong> [nama]</li>
<li><strong>Reporter:</strong> [nama-nama reporter]</li>
<li><strong>Fotografer:</strong> [nama]</li>
<li><strong>Bagian Iklan &amp; Pemasaran:</strong> [nama]</li>
</ul>
<h2>Alamat Redaksi</h2>
<p>[Alamat lengkap kantor redaksi]<br>Email: <a href="mailto:{$email}">{$email}</a></p>
<p>Wartawan {$site} dibekali kartu identitas dan surat tugas. Wartawan kami tidak dibenarkan menerima imbalan dalam bentuk apa pun dari narasumber.</p>
HTML],
            'pedoman-media-siber' => ['Pedoman Media Siber', 'Pedoman pemberitaan media siber yang dipatuhi redaksi sesuai Peraturan Dewan Pers.', <<<HTML
<p>{$site} berpedoman pada <strong>Pedoman Pemberitaan Media Siber</strong> yang ditetapkan Dewan Pers. Pokok-pokok yang kami terapkan:</p>
<h2>Verifikasi dan Keberimbangan Berita</h2>
<p>Setiap berita melalui verifikasi dan memuat keberimbangan. Berita yang belum terverifikasi hanya dapat dimuat dengan syarat tertentu dan wajib dilengkapi verifikasi lanjutan yang ditautkan pada berita awal.</p>
<h2>Isi Buatan Pengguna</h2>
<p>Konten dari pengguna (misalnya komentar) tidak boleh memuat kebohongan, fitnah, SARA, pornografi, maupun ajakan kekerasan. Redaksi berhak menyunting atau menghapus konten yang melanggar.</p>
<h2>Ralat, Koreksi, dan Hak Jawab</h2>
<p>Ralat, koreksi, dan hak jawab mengacu pada Undang-Undang Pers, Kode Etik Jurnalistik, dan Pedoman Hak Jawab Dewan Pers, serta ditautkan pada berita yang diralat.</p>
<h2>Pencabutan Berita</h2>
<p>Berita yang sudah dipublikasikan tidak dapat dicabut karena alasan penyensoran dari pihak luar redaksi, kecuali terkait SARA, kesusilaan, masa depan anak, pengalaman traumatik korban, atau pertimbangan khusus lain yang ditetapkan Dewan Pers.</p>
<h2>Iklan</h2>
<p>Konten berbayar ditandai dengan jelas sebagai <em>advertorial</em>, <em>iklan</em>, atau <em>sponsored content</em>.</p>
<p>Untuk pengaduan pemberitaan, hubungi <a href="mailto:{$email}">{$email}</a>.</p>
HTML],
            'kode-etik-jurnalistik' => ['Kode Etik Jurnalistik', 'Kode Etik Jurnalistik yang menjadi pegangan wartawan dalam menjalankan tugas.', <<<HTML
<p>Wartawan {$site} menaati <strong>Kode Etik Jurnalistik</strong> yang ditetapkan Dewan Pers. Ringkasan prinsipnya:</p>
<ol>
<li>Bersikap independen, menghasilkan berita yang akurat, berimbang, dan tidak beriktikad buruk.</li>
<li>Menempuh cara-cara profesional dalam melaksanakan tugas jurnalistik.</li>
<li>Selalu menguji informasi, memberitakan secara berimbang, tidak mencampurkan fakta dan opini yang menghakimi, serta menerapkan asas praduga tak bersalah.</li>
<li>Tidak membuat berita bohong, fitnah, sadis, dan cabul.</li>
<li>Tidak menyebutkan identitas korban kejahatan susila dan anak pelaku kejahatan.</li>
<li>Tidak menyalahgunakan profesi dan tidak menerima suap.</li>
<li>Menghormati hak narasumber, termasuk embargo, informasi latar belakang, dan <em>off the record</em>.</li>
<li>Tidak menulis berita berdasarkan prasangka atau diskriminasi SARA, jenis kelamin, bahasa, maupun kondisi fisik.</li>
<li>Menghormati hak narasumber tentang kehidupan pribadinya, kecuali untuk kepentingan publik.</li>
<li>Segera mencabut, meralat, dan memperbaiki berita yang keliru disertai permintaan maaf.</li>
<li>Melayani hak jawab dan hak koreksi secara proporsional.</li>
</ol>
HTML],
            'kebijakan-privasi' => ['Kebijakan Privasi', "Bagaimana {$site} mengumpulkan, menggunakan, dan melindungi data pengunjung.", <<<HTML
<p>Kebijakan ini menjelaskan cara {$site} mengumpulkan dan menggunakan informasi saat Anda mengakses situs kami.</p>
<h2>Informasi yang Kami Kumpulkan</h2>
<ul>
<li>Data teknis seperti alamat IP, jenis peramban, dan halaman yang dikunjungi, untuk statistik dan keamanan.</li>
<li>Cookie untuk menjaga sesi dan menghitung tayangan artikel.</li>
<li>Data yang Anda kirim secara sukarela, misalnya melalui email ke redaksi.</li>
</ul>
<h2>Penggunaan Informasi</h2>
<p>Informasi digunakan untuk meningkatkan layanan, mengukur kinerja konten dan iklan, serta menjaga keamanan situs. Kami tidak menjual data pribadi Anda kepada pihak ketiga.</p>
<h2>Layanan Pihak Ketiga</h2>
<p>Situs ini dapat menggunakan layanan pihak ketiga seperti Google Analytics yang memiliki kebijakan privasinya sendiri.</p>
<h2>Kontak</h2>
<p>Pertanyaan terkait privasi dapat dikirim ke <a href="mailto:{$email}">{$email}</a>.</p>
HTML],
            'syarat-ketentuan' => ['Syarat & Ketentuan', "Syarat dan ketentuan penggunaan situs {$site}.", <<<HTML
<p>Dengan mengakses {$site}, Anda menyetujui syarat dan ketentuan berikut.</p>
<h2>Hak Cipta</h2>
<p>Seluruh konten (teks, foto, grafis, dan video) dilindungi hak cipta. Pengutipan diperbolehkan dengan mencantumkan sumber dan tautan ke artikel asli. Penggunaan untuk tujuan komersial memerlukan izin tertulis.</p>
<h2>Penggunaan Situs</h2>
<p>Pengguna dilarang menggunakan situs untuk tindakan melanggar hukum, menyebarkan konten berbahaya, atau mengganggu sistem.</p>
<h2>Perubahan</h2>
<p>Syarat dan ketentuan dapat diperbarui sewaktu-waktu. Perubahan berlaku sejak dipublikasikan di halaman ini.</p>
HTML],
            'disclaimer' => ['Disclaimer', "Batasan tanggung jawab atas konten yang dimuat di {$site}.", <<<HTML
<p>Informasi di {$site} disajikan dengan itikad baik dan melalui proses verifikasi redaksi. Meski demikian, kami tidak bertanggung jawab atas kerugian yang timbul dari penggunaan informasi tanpa konfirmasi lebih lanjut.</p>
<p>Konten bertanda <em>advertorial</em> atau iklan merupakan tanggung jawab pengiklan. Tautan ke situs pihak ketiga disediakan untuk kemudahan dan berada di luar kendali kami.</p>
<p>Jika menemukan kekeliruan, silakan sampaikan melalui <a href="mailto:{$email}">{$email}</a> agar dapat kami koreksi.</p>
HTML],
            'pasang-iklan' => ['Pasang Iklan', "Informasi pemasangan iklan dan advertorial di {$site}.", <<<HTML
<p>Jangkau pembaca Kebumen dan sekitarnya bersama {$site}. Kami menyediakan beberapa pilihan iklan:</p>
<ul>
<li><strong>Banner Header</strong> (970×250) di bagian atas seluruh halaman.</li>
<li><strong>Banner Sidebar</strong> (300×250) di beranda, kategori, dan detail artikel.</li>
<li><strong>Banner Dalam Artikel</strong> (728×90) di sela isi berita.</li>
<li><strong>Advertorial</strong>: artikel promosi yang ditulis tim kami dan diberi label advertorial.</li>
</ul>
<p>Laporan tayangan dan klik tersedia untuk setiap iklan. Hubungi <a href="mailto:{$email}?subject=Pasang%20Iklan">{$email}</a> untuk <em>rate card</em> dan penawaran khusus.</p>
HTML],
            'hubungi-kami' => ['Hubungi Kami', "Kontak redaksi {$site} untuk informasi, kerja sama, dan pengaduan.", <<<HTML
<p>Kami senang mendengar dari Anda. Hubungi redaksi untuk informasi, rilis berita, kerja sama, maupun pengaduan pemberitaan.</p>
<ul>
<li><strong>Email redaksi:</strong> <a href="mailto:{$email}">{$email}</a></li>
<li><strong>Alamat:</strong> [alamat kantor redaksi]</li>
<li><strong>Telepon / WhatsApp:</strong> [nomor kontak]</li>
</ul>
<p>Untuk hak jawab dan koreksi berita, sertakan judul dan tautan artikel yang dimaksud.</p>
HTML],
        ];
    }
}
