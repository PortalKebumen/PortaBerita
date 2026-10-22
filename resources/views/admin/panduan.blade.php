@php
    $user = auth()->user();
    $role = $user->getRoleNames()->first();
    $roleLabel = $role ? config("rbac.role_aliases.{$role}", $role) : 'Pengguna';

    // Daftar isi: hanya bagian yang bisa dipakai pengguna ini (ability null = semua pengguna)
    $sections = collect([
        ['id' => 'mulai', 'label' => 'Mengenal role & hak akses', 'can' => null],
        ['id' => 'masuk', 'label' => 'Masuk, keluar & lupa kata sandi', 'can' => null],
        ['id' => 'tampilan', 'label' => 'Tampilan panel admin', 'can' => null],
        ['id' => 'profil', 'label' => 'Profil Saya', 'can' => null],
        ['id' => 'menulis', 'label' => 'Menulis artikel', 'can' => 'articles.create'],
        ['id' => 'review', 'label' => 'Review & publikasi artikel', 'can' => 'articles.approve'],
        ['id' => 'kategori', 'label' => 'Kategori & Tag', 'can' => 'categories.view'],
        ['id' => 'media', 'label' => 'Media Library', 'can' => 'media.view'],
        ['id' => 'analitik', 'label' => 'Laporan Analitik', 'can' => 'view-analytics'],
        ['id' => 'iklan', 'label' => 'Iklan', 'can' => 'ads.view'],
        ['id' => 'halaman', 'label' => 'Halaman & Menu situs', 'can' => 'pages.view'],
        ['id' => 'pengguna', 'label' => 'Pengguna & Role', 'can' => 'users.view'],
        ['id' => 'log', 'label' => 'Activity Log', 'can' => 'activity-log.view'],
        ['id' => 'pengaturan', 'label' => 'Pengaturan situs', 'can' => 'settings.view'],
        ['id' => 'masalah', 'label' => 'Masalah yang sering terjadi', 'can' => null],
    ])->filter(fn ($s) => $s['can'] === null || $user->can($s['can']))->values();
    $no = fn (string $id) => $sections->search(fn ($s) => $s['id'] === $id) + 1;
@endphp

<x-layouts.admin title="Panduan Pengguna" :breadcrumbs="[['label' => 'Panduan Pengguna']]">
    <div class="panduan grid grid-cols-1 gap-8 lg:grid-cols-[240px_minmax(0,1fr)] max-w-[1200px]">

        {{-- Daftar isi --}}
        <nav class="lg:sticky lg:top-24 lg:self-start" aria-label="Daftar isi panduan">
            <div class="card card-body !p-4">
                <div class="text-[11px] font-bold uppercase tracking-wider text-[#848CA3] mb-2">Daftar isi</div>
                <ol class="space-y-0.5 text-[13px]">
                    @foreach ($sections as $i => $s)
                        <li>
                            <a href="#{{ $s['id'] }}" class="flex gap-2 rounded-md px-2 py-1.5 text-[#3B4257] hover:bg-[#F1F3F7] hover:text-brand-600">
                                <span class="w-5 shrink-0 text-right text-[#848CA3]">{{ $i + 1 }}.</span>
                                <span>{{ $s['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ol>
            </div>
        </nav>

        {{-- Isi --}}
        <div class="min-w-0 space-y-6">
            <div class="alert-info">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0 mt-0.5"><circle cx="12" cy="12" r="9"></circle><path d="M12 16v-5M12 8h.01"></path></svg>
                <div>Halo <strong class="font-semibold">{{ $user->name }}</strong>, Anda masuk sebagai <strong class="font-semibold">{{ $roleLabel }}</strong>. Panduan ini hanya menampilkan menu yang bisa Anda pakai. Klik gambar untuk melihat ukuran penuh.</div>
            </div>

            {{-- 1. Role --}}
            <section id="mulai" class="card card-body scroll-mt-24">
                <h2>{{ $no('mulai') }}. Mengenal role & hak akses</h2>
                <p>Setiap akun punya satu <strong>role</strong>. Role menentukan menu yang tampil di sidebar dan tombol yang bisa ditekan. Role diatur oleh Super Admin di menu <em>Pengguna & Role</em>.</p>
                <div class="overflow-x-auto">
                    <table class="panduan-table">
                        <thead><tr><th>Role</th><th>Tugas utama</th><th>Menu</th></tr></thead>
                        <tbody>
                            <tr><td><strong>Reporter</strong> (Penulis)</td><td>Menulis artikel, mengunggah foto, mengirim artikel ke redaktur.</td><td>Dashboard, Artikel, Media Library</td></tr>
                            <tr><td><strong>Redaktur</strong> (Editor)</td><td>Mereview, menyetujui/menolak, menerbitkan & menjadwalkan artikel, mengelola kategori.</td><td>+ Kategori & Tag, Laporan Analitik, Activity Log</td></tr>
                            <tr><td><strong>Ads Manager</strong></td><td>Mengelola iklan, jadwal tayang, dan laporan performa iklan.</td><td>Dashboard, Iklan, Laporan Analitik (tab Iklan)</td></tr>
                            <tr><td><strong>Super Admin</strong></td><td>Semua tugas di atas, plus pengguna, halaman, menu, dan pengaturan situs.</td><td>Semua menu</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>

            {{-- 2. Masuk --}}
            <section id="masuk" class="card card-body scroll-mt-24">
                <h2>{{ $no('masuk') }}. Masuk, keluar & lupa kata sandi</h2>
                <h3>Masuk</h3>
                <ol>
                    <li>Buka <strong>alamat login khusus</strong> yang diberikan administrator. Alamat ini sengaja dirahasiakan, jadi jangan dibagikan atau ditulis di tempat umum. Membuka <code>/admin</code> tanpa login akan menampilkan halaman tidak ditemukan.</li>
                    <li>Isi <strong>Email</strong> dan <strong>Kata Sandi</strong>, lalu klik <strong>Masuk</strong>. Centang <em>Ingat saya</em> hanya di perangkat pribadi.</li>
                    <li>Setelah 5 kali salah, form dikunci sementara dan ada hitung mundur sebelum bisa mencoba lagi.</li>
                </ol>
                <x-panduan.shot src="login" caption="Halaman login panel redaksi." />

                <h3>Akun baru & kata sandi sementara</h3>
                <p>Bila administrator mengirim <em>info login</em> ke email Anda, Anda akan menerima email berisi alamat login, email, dan <strong>kata sandi sementara</strong>. Setelah masuk, Anda langsung diarahkan ke <a href="#profil">Profil Saya</a> dan wajib mengganti kata sandi sebelum bisa membuka menu lain.</p>

                <h3>Lupa kata sandi</h3>
                <ol>
                    <li>Di halaman login, klik <strong>Lupa kata sandi?</strong></li>
                    <li>Masukkan email akun Anda, lalu klik <strong>Kirim tautan reset</strong>.</li>
                    <li>Buka email dari PortalKebumen (cek juga folder <em>Spam</em>/<em>Promosi</em>), klik tautannya, lalu buat kata sandi baru. Tautan berlaku 60 menit.</li>
                </ol>
                <x-panduan.shot src="lupa-password" caption="Form lupa kata sandi. Demi keamanan, pesan yang muncul selalu sama, baik email terdaftar maupun tidak." />

                <h3>Keluar</h3>
                <p>Klik nama Anda di pojok kanan atas, lalu pilih <strong>Keluar</strong>. Selalu keluar setelah memakai komputer bersama.</p>
            </section>

            {{-- 3. Tampilan --}}
            <section id="tampilan" class="card card-body scroll-mt-24">
                <h2>{{ $no('tampilan') }}. Tampilan panel admin</h2>
                <ul>
                    <li><strong>Sidebar (kiri)</strong>: daftar menu sesuai role. Tombol <span class="kbd">☰</span> di atas menciutkan sidebar; di HP, tombol yang sama membuka menu.</li>
                    <li><strong>Dashboard</strong>: ringkasan sesuai role, misalnya artikel Anda, artikel yang menunggu review, tayangan 14 hari terakhir, dan aktivitas terbaru.</li>
                    <li><strong>Lonceng notifikasi</strong>: kabar alur kerja, misalnya artikel dikirim untuk review, disetujui, ditolak, atau terbit, juga iklan yang akan berakhir. Klik notifikasi untuk membuka artikel atau iklannya; <em>Tandai semua dibaca</em> mengosongkan penanda.</li>
                    @can('articles.view')
                        <li><strong>Pencarian cepat</strong>: klik kotak <em>Cari atau ketik perintah…</em> atau tekan <span class="kbd">Ctrl</span> + <span class="kbd">K</span> untuk mencari artikel dan membuka menu dengan cepat.</li>
                    @endcan
                </ul>
                <x-panduan.shot src="dashboard" caption="Dashboard (contoh tampilan Super Admin)." />
                <x-panduan.shot src="notifikasi" caption="Panel notifikasi dari ikon lonceng." />
                @can('articles.view')
                    <x-panduan.shot src="pencarian" caption="Pencarian cepat (Ctrl + K)." />
                @endcan
                <p>Panel admin juga bisa dipakai di HP. Menu dibuka dengan tombol <span class="kbd">☰</span> di kiri atas.</p>
                <x-panduan.shot src="dashboard-mobile" caption="Dashboard di layar HP." narrow />
            </section>

            {{-- 4. Profil --}}
            <section id="profil" class="card card-body scroll-mt-24">
                <h2>{{ $no('profil') }}. Profil Saya</h2>
                <p>Buka dari nama Anda di pojok kanan atas lalu pilih <strong>Profil Saya</strong>.</p>
                <ul>
                    <li><strong>Informasi Profil</strong>: nama lengkap, email (dipakai untuk login dan email reset kata sandi), dan bio singkat. Nama dan bio tampil di halaman profil penulis dan di bawah artikel Anda. Klik <strong>Simpan Profil</strong>.</li>
                    <li><strong>Ganti Kata Sandi</strong>: isi kata sandi saat ini, lalu kata sandi baru dua kali. Minimal 8 karakter dan harus memuat huruf dan angka.</li>
                    <li>Role dan status akun hanya bisa diubah administrator.</li>
                </ul>
                <x-panduan.shot src="profil" caption="Halaman Profil Saya." />
            </section>

            {{-- 5. Menulis --}}
            @can('articles.create')
                <section id="menulis" class="card card-body scroll-mt-24">
                    <h2>{{ $no('menulis') }}. Menulis artikel</h2>

                    <h3>Alur status artikel</h3>
                    <div class="overflow-x-auto">
                        <table class="panduan-table">
                            <thead><tr><th>Status</th><th>Artinya</th><th>Siapa yang bertindak</th></tr></thead>
                            <tbody>
                                <tr><td><span class="badge-neutral">Draft</span></td><td>Masih ditulis, belum dikirim. Hanya penulis & redaktur yang melihat.</td><td>Penulis menyimpan & menyunting</td></tr>
                                <tr><td><span class="badge-warning">Submitted</span></td><td>Dikirim ke redaktur, menunggu review.</td><td>Redaktur menyetujui atau menolak</td></tr>
                                <tr><td><span class="badge-danger">Rejected</span></td><td>Dikembalikan dengan catatan revisi.</td><td>Penulis memperbaiki lalu <em>Kirim Review Ulang</em></td></tr>
                                <tr><td><span class="badge-info">Approved</span></td><td>Disetujui, siap terbit.</td><td>Redaktur menerbitkan atau menjadwalkan</td></tr>
                                <tr><td><span class="badge-info">Scheduled</span></td><td>Terbit otomatis pada tanggal & jam yang ditentukan.</td><td>Sistem</td></tr>
                                <tr><td><span class="badge-success">Published</span></td><td>Tayang di situs publik.</td><td>-</td></tr>
                                <tr><td><span class="badge-neutral">Archived</span></td><td>Diturunkan dari situs, tetap tersimpan.</td><td>Redaktur</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <h3>Membuat artikel baru</h3>
                    <ol>
                        <li>Buka menu <strong>Artikel</strong>, lalu klik <strong>+ Tambah Artikel</strong>.</li>
                        <li>Isi <strong>Judul Artikel</strong>. <strong>Slug</strong> (alamat URL) terisi otomatis dari judul; ubah hanya bila perlu, dengan huruf kecil, angka, dan tanda hubung.</li>
                        <li>Pilih <strong>Kategori</strong>. Artikel baru selalu berstatus <em>Draft</em>.</li>
                        <li>Tulis <strong>Ringkasan</strong> 1–2 kalimat. Ringkasan tampil di kartu berita, hasil pencarian Google, dan pratinjau saat dibagikan ke WhatsApp/Facebook.</li>
                        <li>Pilih <strong>Gambar Sampul</strong> (disarankan 1200×675 px). Lihat langkah memilih gambar di bawah.</li>
                        <li>Tulis <strong>Isi Artikel</strong> di editor (lihat tips editor).</li>
                        <li>Klik <strong>Simpan Artikel</strong>. Artikel tersimpan sebagai Draft dan bisa disunting kapan saja.</li>
                    </ol>
                    <x-panduan.shot src="artikel-tambah" caption="Form tambah artikel." />

                    <h3>Memilih gambar sampul</h3>
                    <ol>
                        <li>Klik <strong>Pilih Gambar</strong>.</li>
                        <li>Tab <strong>Unggah Berkas</strong>: klik kotak unggah atau seret foto dari komputer, isi <em>Teks Alternatif</em> (deskripsi gambar untuk aksesibilitas & SEO) dan <em>Keterangan</em> (tampil di bawah foto, misalnya sumber foto), lalu klik <strong>Unggah & Gunakan</strong>.</li>
                        <li>Atau buka tab <strong>Pustaka Media</strong> untuk memakai foto yang sudah pernah diunggah.</li>
                    </ol>
                    <x-panduan.shot src="media-picker" caption="Jendela Pilih Gambar: unggah baru atau pilih dari Pustaka Media." />

                    <h3>Tips editor isi artikel</h3>
                    <ul>
                        <li>Gunakan <strong>Heading 2/3</strong> dari menu <em>Paragraph</em> untuk subjudul; jangan hanya menebalkan teks.</li>
                        <li>Sisipkan foto di tengah artikel dengan ikon gambar di toolbar, lalu klik ikon folder untuk memilih dari Pustaka Media. Keterangan foto dari Pustaka Media otomatis tampil di bawah gambar; bisa juga mencentang <em>Keterangan</em> di dialog gambar lalu mengetik sendiri.</li>
                        <li>Saat menempel (paste) dari Word atau situs lain, periksa ulang format dan hapus tautan yang tidak perlu.</li>
                        <li>Klik <strong>Pratinjau</strong> untuk melihat tampilan artikel di situs sebelum terbit.</li>
                    </ul>

                    <h3>Pengaturan SEO (opsional)</h3>
                    <p>Buka bagian <strong>Pengaturan SEO</strong> di bawah editor bila ingin judul/deskripsi di Google berbeda dari judul dan ringkasan, atau gambar pratinjau share berbeda dari gambar sampul. Bila dikosongkan, sistem otomatis memakai judul, ringkasan, dan gambar sampul.</p>
                    <x-panduan.shot src="artikel-tambah-seo" caption="Bagian Pengaturan SEO: Meta Title, Meta Description, dan OG Image." />

                    <h3>Mengirim ke redaktur</h3>
                    <ol>
                        <li>Buka artikel Draft dari daftar Artikel (klik judulnya).</li>
                        <li>Pastikan isi sudah lengkap, lalu klik <strong>Kirim Review</strong> di kanan atas dan konfirmasi <strong>Ya, Kirim Review</strong>.</li>
                        <li>Redaktur mendapat notifikasi. Setelah dikirim, artikel tidak bisa disunting penulis sampai redaktur memutuskan.</li>
                        <li>Bila <strong>ditolak</strong>, Anda mendapat notifikasi berisi alasan penolakan. Perbaiki artikelnya, lalu klik <strong>Kirim Review Ulang</strong>.</li>
                    </ol>
                    <x-panduan.shot src="artikel-draft-penulis" caption="Artikel Draft milik penulis dengan tombol Kirim Review." />
                    <x-panduan.shot src="artikel-kirim-review" caption="Konfirmasi sebelum artikel dikirim ke redaktur." />
                </section>
            @endcan

            {{-- 6. Review --}}
            @can('articles.approve')
                <section id="review" class="card card-body scroll-mt-24">
                    <h2>{{ $no('review') }}. Review & publikasi artikel</h2>
                    <h3>Menemukan artikel yang perlu direview</h3>
                    <p>Buka <strong>Artikel</strong>, lalu pilih filter status <strong>Submitted</strong>. Artikel baru yang dikirim juga muncul di lonceng notifikasi dan di Dashboard.</p>
                    <x-panduan.shot src="artikel-daftar" caption="Daftar artikel dengan pencarian, filter kategori & status, dan Aksi Massal." />

                    <h3>Menyetujui atau menolak</h3>
                    <ol>
                        <li>Buka artikel berstatus <em>Submitted</em>. Periksa judul, isi, foto, kategori, dan SEO; perbaiki langsung bila perlu lalu <strong>Simpan Perubahan</strong>.</li>
                        <li>Klik <strong>Setujui</strong> bila layak terbit, atau <strong>Tolak</strong> lalu tulis alasan dan saran revisi yang jelas. Penulis menerima notifikasi.</li>
                    </ol>
                    <x-panduan.shot src="artikel-review-redaktur" caption="Artikel yang menunggu review, dengan tombol Setujui dan Tolak." />
                    <x-panduan.shot src="artikel-tolak" caption="Menolak artikel wajib disertai alasan penolakan." />

                    <h3>Menerbitkan, menjadwalkan & mengarsipkan</h3>
                    <ul>
                        <li><strong>Terbitkan</strong>: artikel yang sudah disetujui langsung tayang di situs.</li>
                        <li><strong>Jadwalkan</strong>: pilih tanggal & jam; artikel terbit otomatis pada waktunya.</li>
                        <li><strong>Arsipkan</strong>: menurunkan artikel dari situs tanpa menghapusnya.</li>
                        <li><strong>Tandai sebagai Breaking News</strong> menampilkan artikel di pita berita terkini; <strong>Advertorial</strong> memberi label konten berbayar.</li>
                        <li><strong>Aksi Massal</strong> di daftar Artikel: centang beberapa artikel lalu pilih <em>Setujui</em>, <em>Terbitkan</em>, <em>Arsipkan</em>, atau <em>Hapus</em>.</li>
                    </ul>

                    <h3>Riwayat versi</h3>
                    <p>Di bagian bawah halaman edit artikel terdapat <strong>Riwayat Versi</strong>: siapa mengubah apa dan kapan, lengkap dengan perbandingan isi sebelum dan sesudah, serta catatan revisi dari redaktur.</p>
                    <x-panduan.shot src="artikel-riwayat" caption="Catatan revisi dan riwayat perubahan artikel." />
                </section>
            @endcan

            {{-- 7. Kategori --}}
            @can('categories.view')
                <section id="kategori" class="card card-body scroll-mt-24">
                    <h2>{{ $no('kategori') }}. Kategori & Tag</h2>
                    <ul>
                        <li><strong>Kategori</strong> mengelompokkan rubrik dan tampil di menu situs. Klik <strong>+ Tambah Kategori</strong>, isi nama (slug otomatis), dan pilih <em>Kategori Induk</em> bila ingin menjadikannya sub-kategori.</li>
                        <li>Ikon pensil untuk mengubah, ikon tempat sampah untuk menghapus. Kategori yang masih dipakai artikel sebaiknya diubah, bukan dihapus.</li>
                        <li><strong>Tag</strong> (tab kedua) adalah kata kunci topik, misalnya nama tokoh atau acara, untuk menghubungkan artikel terkait.</li>
                    </ul>
                    <x-panduan.shot src="kategori-tag" caption="Daftar kategori." />
                    <x-panduan.shot src="kategori-tambah" caption="Form tambah kategori." />
                </section>
            @endcan

            {{-- 8. Media --}}
            @can('media.view')
                <section id="media" class="card card-body scroll-mt-24">
                    <h2>{{ $no('media') }}. Media Library</h2>
                    <p>Tempat semua foto dan dokumen. Reporter melihat dan mengelola unggahannya sendiri; Redaktur dan Super Admin melihat semua media.</p>
                    <ol>
                        <li>Klik <strong>Unggah Media</strong>. Pilih <strong>beberapa berkas sekaligus</strong> (tahan Ctrl/Shift) atau seret ke kotak unggah, maksimal 20 berkas per unggahan.</li>
                        <li>Tunggu sampai setiap berkas berlabel <span class="badge-success">Siap</span>, lalu isi alt text dan keterangan masing-masing.</li>
                        <li>Klik <strong>Unggah N Berkas</strong>.</li>
                    </ol>
                    <ul>
                        <li>Format: JPG, PNG, WEBP, PDF. Batas ukuran per berkas tertulis di form; berkas yang terlalu besar langsung ditolak dengan pesan yang menyebut ukurannya. Kecilkan atau kompres dulu bila perlu.</li>
                        <li>Sistem otomatis membuat versi ringan (WebP) dan ukuran kecil/sedang agar situs tetap cepat.</li>
                        <li>Gunakan pencarian, filter tipe, dan rentang tanggal untuk menemukan berkas; pakai ikon pensil pada berkas untuk mengubah alt text/keterangan, atau ikon tempat sampah untuk menghapusnya.</li>
                    </ul>
                    <x-panduan.shot src="media-library" caption="Media Library." />
                    <x-panduan.shot src="media-upload" caption="Unggah banyak berkas sekaligus." />
                </section>
            @endcan

            {{-- 9. Analitik --}}
            @can('view-analytics')
                <section id="analitik" class="card card-body scroll-mt-24">
                    <h2>{{ $no('analitik') }}. Laporan Analitik</h2>
                    <ul>
                        <li>Pilih periode cepat (<em>7 hari</em>, <em>30 hari</em>, <em>90 hari</em>, <em>Bulan ini</em>, <em>Bulan lalu</em>, <em>Tahun ini</em>) atau rentang tanggal bebas. Angka dibandingkan otomatis dengan periode sebelumnya.</li>
                        @can('analytics.view-content')
                            <li><strong>Konten & Tayangan</strong>: total tayangan, tren harian, artikel terpopuler, performa per kategori dan per penulis, serta lokasi pembaca.</li>
                        @endcan
                        @can('ads.view-reports')
                            <li><strong>Performa Iklan</strong>: impresi, klik, dan CTR per iklan dan per posisi.</li>
                        @endcan
                        @can('analytics.export')
                            <li><strong>Ekspor</strong>: unduh tab yang sedang dibuka sebagai Excel (.xlsx) atau CSV, dengan periode yang sama.</li>
                        @endcan
                    </ul>
                    @can('analytics.view-content')
                        <x-panduan.shot src="analitik" caption="Tab Konten & Tayangan." />
                    @endcan
                    @can('ads.view-reports')
                        <x-panduan.shot src="analitik-iklan" caption="Tab Performa Iklan." />
                    @endcan
                </section>
            @endcan

            {{-- 10. Iklan --}}
            @can('ads.view')
                <section id="iklan" class="card card-body scroll-mt-24">
                    <h2>{{ $no('iklan') }}. Iklan</h2>
                    <p>Ringkasan di atas menunjukkan iklan aktif, terjadwal, dan yang akan berakhir kurang dari 7 hari.</p>
                    <ol>
                        <li>Klik <strong>+ Tambah Iklan</strong>.</li>
                        <li>Pilih <strong>banner</strong> dari Pustaka Media atau unggah langsung.</li>
                        <li>Isi nama & kontak pengiklan dan <strong>Target URL</strong> (alamat tujuan saat banner diklik, diawali <code>https://</code>).</li>
                        <li>Pilih <strong>Posisi Penempatan</strong> dan <strong>Status</strong>, lalu tanggal mulai & berakhir. Klik <strong>Simpan Iklan</strong>.</li>
                    </ol>
                    <div class="overflow-x-auto">
                        <table class="panduan-table">
                            <thead><tr><th>Posisi</th><th>Ruang maksimal</th><th>Ukuran populer</th></tr></thead>
                            <tbody>
                                @foreach (\App\Models\Advertisement::PLACEMENTS as $p)
                                    <tr><td>{{ $p['label'] }}</td><td>{{ $p['max_width'] }} × {{ $p['max_height'] }} px</td><td>{{ implode(', ', $p['popular']) }}</td></tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <ul>
                        <li>Banner ukuran berapa pun ditampilkan utuh: diperkecil agar muat, tidak dipotong.</li>
                        <li>Status <strong>Aktif</strong> tayang selama periode; <strong>Terjadwal</strong> tayang mulai tanggal mulai; <strong>Nonaktif</strong> tidak tayang. Bila beberapa iklan di posisi yang sama, banner bergantian otomatis.</li>
                        <li>Impresi (tampil di layar pembaca) dan klik tercatat otomatis; lihat hasilnya di Laporan Analitik tab Performa Iklan.</li>
                        <li>Notifikasi dikirim saat iklan akan berakhir, supaya bisa dikonfirmasi perpanjangan ke pengiklan.</li>
                    </ul>
                    <x-panduan.shot src="iklan" caption="Daftar iklan dengan impresi dan klik." />
                    <x-panduan.shot src="iklan-tambah" caption="Form tambah iklan." />
                </section>
            @endcan

            {{-- 11. Halaman & menu --}}
            @can('pages.view')
                <section id="halaman" class="card card-body scroll-mt-24">
                    <h2>{{ $no('halaman') }}. Halaman & Menu situs</h2>
                    <h3>Halaman statis</h3>
                    <p>Untuk konten tetap seperti <em>Tentang Kami</em>, <em>Redaksi</em>, <em>Pedoman Media Siber</em>, <em>Kebijakan Privasi</em>, dan <em>Disclaimer</em>. Klik <strong>+ Tambah Halaman</strong> atau judul halaman untuk mengubah; atur status <em>Terbit</em>/<em>Draft</em> di panel kanan.</p>
                    <x-panduan.shot src="halaman" caption="Daftar halaman statis." />
                    <x-panduan.shot src="halaman-edit" caption="Menyunting halaman." />
                    @can('menus.view')
                        <h3>Menu</h3>
                        <p>Mengatur tautan di <strong>Menu Utama (Navbar)</strong>, <strong>Footer (Kolom Tautan)</strong>, dan <strong>Footer Bawah</strong>. Pilih lokasi di atas, tambahkan halaman, kategori, atau tautan bebas dari panel kiri, lalu atur urutan dan sub-menu di panel kanan.</p>
                        <x-panduan.shot src="menu" caption="Pengaturan menu situs." />
                    @endcan
                </section>
            @endcan

            {{-- 12. Pengguna --}}
            @can('users.view')
                <section id="pengguna" class="card card-body scroll-mt-24">
                    <h2>{{ $no('pengguna') }}. Pengguna & Role</h2>
                    <h3>Menambah pengguna</h3>
                    <ol>
                        <li>Klik <strong>+ Tambah Pengguna</strong>, isi nama, email, dan role.</li>
                        <li>Kosongkan kata sandi dan biarkan <strong>Kirim info login ke email pengguna</strong> tercentang. Sistem membuatkan kata sandi sementara dan mengirimkannya bersama alamat login. Pengguna wajib menggantinya saat pertama masuk.</li>
                        <li>Klik <strong>Simpan Pengguna</strong>.</li>
                    </ol>
                    <x-panduan.shot src="pengguna-tambah" caption="Form tambah pengguna." />
                    <h3>Mengelola pengguna</h3>
                    <ul>
                        <li>Ikon <strong>amplop</strong>: kirim ulang info login dengan kata sandi sementara baru, misalnya bila pengguna lupa kata sandi dan email reset tidak sampai.</li>
                        <li>Ikon <strong>pensil</strong>: ubah data, role, atau status. Matikan <strong>Akun aktif</strong> untuk menonaktifkan akun; pengguna langsung dikeluarkan dan tidak bisa masuk lagi.</li>
                        <li>Ikon <strong>tempat sampah</strong>: hapus pengguna. Artikelnya tetap ada dan dipindahkan ke akun Redaksi.</li>
                        <li>Label <span class="badge-warning">Sandi sementara</span> berarti pengguna belum mengganti kata sandi dari email info login.</li>
                    </ul>
                    <x-panduan.shot src="pengguna" caption="Daftar pengguna." />
                    @can('roles.update-permissions')
                        <h3>Role & Izin</h3>
                        <p>Tab <strong>Role & Izin</strong> menampilkan setiap role. Klik <strong>Kelola Izin</strong> untuk mencentang atau menghapus hak akses tertentu. Ubah dengan hati-hati: perubahan langsung berlaku untuk semua pengguna dengan role tersebut. Role Super Admin selalu memiliki semua izin.</p>
                        <x-panduan.shot src="role-izin" caption="Daftar role." />
                        <x-panduan.shot src="role-izin-kelola" caption="Mengelola izin sebuah role." />
                    @endcan
                </section>
            @endcan

            {{-- 13. Log --}}
            @can('activity-log.view')
                <section id="log" class="card card-body scroll-mt-24">
                    <h2>{{ $no('log') }}. Activity Log</h2>
                    <p>Catatan audit setiap aktivitas penting: login, perubahan artikel, kategori, media, iklan, pengguna, dan pengaturan, lengkap dengan waktu, pelaku, dan alamat IP. Redaktur melihat aktivitasnya sendiri; Super Admin melihat semua aktivitas.</p>
                    <ul>
                        <li>Cari berdasarkan nama pengguna atau objek, lalu saring berdasarkan pengguna, jenis aksi, atau tanggal; klik baris untuk melihat detail perubahan (nilai lama dan baru).</li>
                        @can('activity-log.purge')
                            <li>Tombol <strong>Bersihkan Log Lama</strong> menghapus catatan yang lebih lama dari 1 minggu sampai 1 tahun (pilih di jendela konfirmasi) agar database tetap ringan. Tindakan ini tidak bisa dibatalkan.</li>
                        @endcan
                    </ul>
                    <x-panduan.shot src="activity-log" caption="Activity Log." />
                </section>
            @endcan

            {{-- 14. Pengaturan --}}
            @can('settings.view')
                <section id="pengaturan" class="card card-body scroll-mt-24">
                    <h2>{{ $no('pengaturan') }}. Pengaturan situs</h2>
                    <ul>
                        <li><strong>Umum</strong>: logo, nama situs, tagline, kontak redaksi, alamat, dan zona waktu.</li>
                        <li><strong>SEO</strong>: judul dan deskripsi default situs, gambar share default, <em>Google Analytics ID</em> (format <code>G-XXXXXXXXXX</code>), kode verifikasi <em>Google Search Console</em>, serta informasi sitemap.</li>
                        <li><strong>Media Sosial</strong>: tautan akun resmi yang tampil di header/footer situs.</li>
                        <li><strong>Email</strong>: menampilkan pengaturan email server dan tombol <strong>Kirim Email Uji</strong> untuk memastikan email reset kata sandi dan info login bisa terkirim.</li>
                        <li><strong>Zona Bahaya</strong>: bersihkan cache, ekspor data, dan reset. Gunakan hanya bila benar-benar perlu.</li>
                    </ul>
                    <p>Setiap perubahan di tab Umum, SEO, dan Media Sosial baru berlaku setelah menekan <strong>Simpan Perubahan</strong> di bagian bawah.</p>
                    <x-panduan.shot src="pengaturan" caption="Tab Umum." />
                    <x-panduan.shot src="pengaturan-seo" caption="Tab SEO: Google Analytics, Search Console, dan sitemap." />
                    <x-panduan.shot src="pengaturan-email" caption="Tab Email: uji pengiriman email." />
                </section>
            @endcan

            {{-- 15. Masalah --}}
            <section id="masalah" class="card card-body scroll-mt-24">
                <h2>{{ $no('masalah') }}. Masalah yang sering terjadi</h2>
                <dl class="panduan-faq">
                    <dt>Muncul "Sesi telah berakhir" (419) saat menyimpan</dt>
                    <dd>Sesi habis karena halaman dibiarkan terbuka terlalu lama. Salin dulu tulisan Anda bila ada, muat ulang halaman, lalu simpan lagi.</dd>

                    <dt>Gambar gagal diunggah</dt>
                    <dd>Biasanya ukuran melebihi batas yang tertulis di form unggah. Kecilkan atau kompres gambar (misalnya dengan squoosh.app atau tinypng.com) lalu coba lagi. Pastikan koneksi stabil.</dd>

                    <dt>Email reset kata sandi atau info login tidak masuk</dt>
                    <dd>Cek folder Spam/Promosi dan pastikan email yang dimasukkan sama dengan email akun. Server email membatasi jumlah kiriman per jam, jadi email bisa tertunda beberapa menit sampai satu jam. Bila tetap tidak masuk, minta administrator mengirim ulang info login dari menu Pengguna & Role.</dd>

                    <dt>Artikel tidak muncul di situs</dt>
                    <dd>Hanya artikel berstatus <em>Published</em> yang tampil. Artikel <em>Scheduled</em> baru tampil pada jadwalnya. Cek status di daftar Artikel.</dd>

                    <dt>Tidak bisa mengedit artikel</dt>
                    <dd>Reporter hanya bisa menyunting artikel miliknya yang berstatus Draft atau Rejected. Artikel yang sedang direview atau sudah terbit hanya bisa diubah redaktur.</dd>

                    <dt>Akun tidak bisa masuk padahal kata sandi benar</dt>
                    <dd>Kemungkinan akun dinonaktifkan atau terkunci sementara setelah beberapa kali salah memasukkan kata sandi. Tunggu hitung mundurnya selesai, atau hubungi administrator.</dd>
                </dl>
                <p class="mt-5 text-[13px] text-[#6C7387]">Masih butuh bantuan? Hubungi administrator atau tim teknis PortalKebumen.</p>
            </section>
        </div>
    </div>
</x-layouts.admin>
