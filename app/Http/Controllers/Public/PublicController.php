<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PublicController extends Controller
{
    /**
     * Kategori navigasi utama dari database.
     */
    public static function kategoriNav(): array
    {
        return \App\Models\Category::whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->orderBy('name')])
            ->orderBy('id')
            ->get()
            ->map(function ($cat) {
                return [
                    'id' => $cat->id,
                    'label' => $cat->name,
                    'slug' => $cat->slug,
                    'children' => $cat->children->map(fn ($c) => [
                        'id' => $c->id,
                        'label' => $c->name,
                        'slug' => $c->slug,
                    ])->toArray(),
                ];
            })
            ->toArray();
    }

    public static function kategoriNavVisible(int $max = 7): array
    {
        return array_slice(self::kategoriNav(), 0, $max);
    }

    public static function kategoriNavOverflow(int $max = 7): array
    {
        return array_slice(self::kategoriNav(), $max);
    }

    public function beranda(): View
    {
        return view('public.beranda', [
            'featuredArticles' => [], // TODO: Article::with('category')->latest()->take(5)->get()
            'popularArticles' => [],  // TODO: Article::orderByDesc('views_count')->take(5)->get()
        ]);
    }

    public function kategori(string $parent, ?string $sub = null): View
    {
        if ($sub) {
            // URL: /kategori/{parent}/{sub}
            $parentCategory = \App\Models\Category::whereNull('parent_id')
                ->where('slug', $parent)
                ->firstOrFail();

            $category = \App\Models\Category::where('parent_id', $parentCategory->id)
                ->where('slug', $sub)
                ->firstOrFail();

            $kategoriData = [
                'id' => $category->id,
                'label' => $category->name,
                'slug' => $category->slug,
                'parent' => [
                    'id' => $parentCategory->id,
                    'label' => $parentCategory->name,
                    'slug' => $parentCategory->slug,
                ],
            ];

            $articles = \App\Models\Article::where('category_id', $category->id)
                ->where('status', 'published')
                ->latest('published_at')
                ->paginate(12);
        } else {
            // URL: /kategori/{slug}
            // Cari apakah kategori utama atau sub-kategori
            $category = \App\Models\Category::with(['parent', 'children'])
                ->where('slug', $parent)
                ->firstOrFail();

            $kategoriData = [
                'id' => $category->id,
                'label' => $category->name,
                'slug' => $category->slug,
                'parent' => $category->parent ? [
                    'id' => $category->parent->id,
                    'label' => $category->parent->name,
                    'slug' => $category->parent->slug,
                ] : null,
            ];

            // Ambil artikel untuk kategori ini beserta seluruh anak kategorinya jika ada
            $categoryIds = collect([$category->id])->merge($category->children->pluck('id'))->all();

            $articles = \App\Models\Article::whereIn('category_id', $categoryIds)
                ->where('status', 'published')
                ->latest('published_at')
                ->paginate(12);
        }

        return view('public.kategori', [
            'kategori' => $kategoriData,
            'category' => $category,
            'articles' => $articles,
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