<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PublicController extends Controller
{
    /**
     * Kategori navigasi utama.
     * TODO: ganti ke Category::whereNull('parent_id')->get() setelah modul
     * Kategori & Tag (Dev3) tersedia.
     */
    public static function kategoriNav(): array
    {
        return [
            ['label' => 'Berita Kebumen', 'slug' => 'berita-kebumen'],
            ['label' => 'Pemerintahan', 'slug' => 'pemerintahan'],
            ['label' => 'Ekonomi & UMKM', 'slug' => 'ekonomi-umkm'],
            ['label' => 'Wisata & Budaya', 'slug' => 'wisata-budaya'],
            ['label' => 'Pendidikan', 'slug' => 'pendidikan'],
            ['label' => 'Olahraga', 'slug' => 'olahraga'],
            ['label' => 'Potensi Daerah', 'slug' => 'potensi-daerah'],
            ['label' => 'Nasional', 'slug' => 'nasional'],
            ['label' => 'Internasional', 'slug' => 'internasional'],
        ];
    }

    public static function kategoriNavVisible(int $max = 5): array
    {
        return array_slice(self::kategoriNav(), 0, $max);
    }

    public static function kategoriNavOverflow(int $max = 5): array
    {
        return array_slice(self::kategoriNav(), $max);
    }

    public function beranda(): View
    {
        return view('public.beranda', [
            'featuredArticles' => [], // TODO: Article::with('category')->latest()->take(5)->get()
            'popularArticles' => [],  // TODO: Article::orderByDesc('views')->take(5)->get()
        ]);
    }

    public function kategori(string $slug): View
    {
        $kategori = collect(self::kategoriNav())->firstWhere('slug', $slug);
        abort_if(! $kategori, 404);

        return view('public.kategori', [
            'kategori' => $kategori,
            'articles' => [], // TODO: Article::whereHas('category', fn ($q) => $q->where('slug', $slug))->paginate(12)
        ]);
    }

    public function artikel(string $slug): View
    {
        // TODO: ganti dengan Article::where('slug', $slug)->with(['author','category'])->firstOrFail()
        // Data di bawah hanya contoh untuk keperluan wiring layout (PK-15).
        $article = (object) [
            'title' => 'Judul Artikel Contoh untuk Wiring Layout',
            'slug' => $slug,
            'excerpt' => 'Ringkasan singkat artikel akan tampil di sini.',
            'body' => '<p>Konten artikel akan ditampilkan di sini setelah modul Artikel tersedia.</p>',
            'category' => (object) ['label' => 'Berita Kebumen', 'slug' => 'berita-kebumen'],
            'published_at' => now(),
            'author' => Auth::user() ?? User::first(),
        ];

        return view('public.artikel-detail', [
            'article' => $article,
            'relatedArticles' => [],
        ]);
    }

    public function byline(User $user): View
    {
        return view('public.byline', [
            'penulis' => $user,
            'articles' => [], // TODO: Article::where('user_id', $user->id)->latest()->paginate(9)
        ]);
    }
}