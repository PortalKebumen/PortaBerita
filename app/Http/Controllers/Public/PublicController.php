<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Models\Page;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PublicController extends Controller
{
    /* ---------------------------------------------------------------
     | Navigasi kategori (array biasa, aman untuk cache)
     |--------------------------------------------------------------- */

    public static function kategoriNav(): array
    {
        return Cache::remember('public.kategori_nav.v1', 300, function () {
            return Category::whereNull('parent_id')
                ->with(['children' => fn ($q) => $q->orderBy('name')])
                ->orderBy('id')
                ->get()
                ->map(fn ($cat) => [
                    'id' => $cat->id,
                    'label' => $cat->name,
                    'slug' => $cat->slug,
                    'children' => $cat->children->map(fn ($c) => [
                        'id' => $c->id,
                        'label' => $c->name,
                        'slug' => $c->slug,
                    ])->all(),
                ])
                ->all();
        });
    }

    public static function kategoriNavVisible(int $max = 7): array
    {
        return array_slice(self::kategoriNav(), 0, $max);
    }

    public static function kategoriNavOverflow(int $max = 7): array
    {
        return array_slice(self::kategoriNav(), $max);
    }

    /**
     * Berita terpopuler 7 hari untuk footer (array biasa, aman untuk cache).
     *
     * @return array<int, array{title: string, url: string, image: string, category: ?string, date: ?string}>
     */
    public static function footerPopular(int $limit = 4): array
    {
        return Cache::remember('public.footer_popular.v1', 600, function () use ($limit) {
            $popular = self::published()->reorder()
                ->where('published_at', '>=', now()->subDays(7))
                ->orderByDesc('views_count')
                ->take($limit)
                ->get();

            if ($popular->count() < $limit) {
                $popular = self::published()->reorder()->orderByDesc('views_count')->take($limit)->get();
            }

            return $popular->map(fn (Article $a) => [
                'title' => $a->title,
                'url' => $a->publicUrl(),
                'image' => $a->getFirstMediaUrl('featured', 'thumb'),
                'category' => $a->category?->name,
                'date' => $a->published_at?->translatedFormat('d M Y'),
            ])->all();
        });
    }

    /* ---------------------------------------------------------------
     | Query dasar: hanya artikel yang sudah terbit
     |--------------------------------------------------------------- */

    protected static function published(): Builder
    {
        return Article::query()
            ->where('status', 'published')
            ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->with(['category', 'author', 'media'])
            ->orderByDesc('published_at')
            ->orderByDesc('id');
    }

    /**
     * Terpopuler 30 hari terakhir, jatuh ke sepanjang waktu jika kosong.
     *
     * @param  array<int, int>  $exceptIds
     * @param  array<int, int>|null  $categoryIds
     */
    protected static function popular(int $limit = 5, array $exceptIds = [], ?array $categoryIds = null): Collection
    {
        $query = fn () => self::published()
            ->reorder()
            ->whereNotIn('id', $exceptIds)
            ->when($categoryIds !== null, fn ($q) => $q->whereIn('category_id', $categoryIds))
            ->orderByDesc('views_count')
            ->take($limit);

        $popular = $query()->where('published_at', '>=', now()->subDays(30))->get();

        return $popular->isNotEmpty() ? $popular : $query()->get();
    }

    /* ---------------------------------------------------------------
     | Halaman
     |--------------------------------------------------------------- */

    public function beranda(): View|RedirectResponse
    {
        // Tautan lama pencarian (/?q=) dialihkan ke halaman pencarian
        if (filled(request('q'))) {
            return redirect()->route('public.cari', request()->only('q'), 301);
        }

        $featured = self::published()->take(5)->get();

        $latest = self::published()
            ->whereNotIn('id', $featured->pluck('id'))
            ->take(8)
            ->get();

        $popular = self::popular();

        // Blok per kategori utama (maks. 4 kategori, tiap blok 4 artikel termasuk subkategori)
        $kategoriBlocks = Category::whereNull('parent_id')
            ->with('children')
            ->orderBy('id')
            ->take(4)
            ->get()
            ->map(function (Category $cat) {
                $ids = collect([$cat->id])->merge($cat->children->pluck('id'))->all();

                return [
                    'category' => $cat,
                    'articles' => self::published()->whereIn('category_id', $ids)->take(4)->get(),
                ];
            })
            ->filter(fn ($blok) => $blok['articles']->isNotEmpty())
            ->values();

        return view('public.beranda', [
            'featured' => $featured,
            'latest' => $latest,
            'popular' => $popular,
            'kategoriBlocks' => $kategoriBlocks,
        ]);
    }

    public function kategori(string $parent, ?string $sub = null): View
    {
        if ($sub) {
            // URL: /kategori/{parent}/{sub}
            $parentCategory = Category::whereNull('parent_id')->where('slug', $parent)->firstOrFail();
            $category = Category::where('parent_id', $parentCategory->id)->where('slug', $sub)->firstOrFail();
            $category->setRelation('parent', $parentCategory);
        } else {
            // URL: /kategori/{slug}
            $category = Category::with('parent')->where('slug', $parent)->firstOrFail();
        }

        // Tab sub-kategori: anak dari kategori utama (juga tampil saat membuka sub-kategori)
        $rootCategory = $category->parent ?? $category;
        $subCategories = $rootCategory->children()->orderBy('name')->get();

        // Kategori utama memuat artikel seluruh anaknya
        $categoryIds = $category->parent_id
            ? [$category->id]
            : collect([$category->id])->merge($subCategories->pluck('id'))->all();

        $articles = self::published()
            ->whereIn('category_id', $categoryIds)
            ->paginate(12)
            ->withQueryString();

        return view('public.kategori', [
            'category' => $category,
            'rootCategory' => $rootCategory,
            'subCategories' => $subCategories,
            'articles' => $articles,
            'popularArticles' => self::popular(5, [], $categoryIds),
        ]);
    }

    public function artikel(string $slug): View
    {
        $article = Article::query()
            ->where('slug', $slug)
            ->with(['author', 'category.parent', 'media', 'seoMeta'])
            ->firstOrFail();

        $isPublished = $article->status->value === 'published'
            && (! $article->published_at || $article->published_at->lte(now()));

        // Artikel yang belum terbit hanya bisa dilihat redaksi yang berhak (pratinjau)
        $isPreview = false;
        if (! $isPublished) {
            abort_unless(Auth::check() && Auth::user()->can('update', $article), 404);
            $isPreview = true;
        }

        // Hitung tayangan: sekali per sesi, abaikan bot dan pratinjau
        if (! $isPreview && ! $this->isBot()) {
            $key = 'viewed_article_' . $article->id;
            if (! session()->has($key)) {
                // Query builder biasa agar updated_at dan activity log tidak tersentuh
                DB::table('articles')->where('id', $article->id)->increment('views_count');
                session()->put($key, true);
            }
        }

        $relatedArticles = self::published()
            ->where('category_id', $article->category_id)
            ->where('id', '!=', $article->id)
            ->take(4)
            ->get();

        $popularArticles = self::popular(5, [$article->id]);

        $latestArticles = self::published()
            ->whereNotIn('id', $relatedArticles->pluck('id')->push($article->id))
            ->take(5)
            ->get();

        return view('public.artikel-detail', [
            'article' => $article,
            'relatedArticles' => $relatedArticles,
            'popularArticles' => $popularArticles,
            'latestArticles' => $latestArticles,
            'isPreview' => $isPreview,
        ]);
    }

    /** Halaman statis dari Admin > Halaman (Tentang Kami, Pedoman Media Siber, dll.). */
    public function halaman(string $slug): View
    {
        $page = Page::where('slug', $slug)->firstOrFail();

        // Draft hanya bisa dipratinjau oleh yang berhak mengelola halaman
        $isPreview = ! $page->isPublished();
        abort_if($isPreview && ! (Auth::check() && Auth::user()->can('pages.update')), 404);

        return view('public.halaman', [
            'page' => $page,
            'isPreview' => $isPreview,
            'popularArticles' => self::popular(5),
        ]);
    }

    public function byline(User $user, ?string $slug = null): View|RedirectResponse
    {
        // Samakan ke URL resmi /profile/{id}/{Nama.Penulis}; nama salah/kosong dialihkan permanen
        if ($slug !== $user->profileSlug()) {
            $query = request()->getQueryString();

            return redirect()->to($user->profileUrl() . ($query ? '?' . $query : ''), 301);
        }

        $publishedQuery = fn () => self::published()->where('author_id', $user->id);

        // Hanya akun aktif yang memang menulis (role redaksi atau punya artikel terbit)
        abort_unless(
            $user->is_active && ($user->hasAnyRole(['Penulis', 'Editor']) || $publishedQuery()->exists()),
            404
        );

        $stats = $publishedQuery()->reorder()
            ->selectRaw('COUNT(*) as total, COALESCE(SUM(views_count), 0) as views, MIN(published_at) as first_at')
            ->toBase()
            ->first();

        $topCategories = Category::query()
            ->whereIn('id', $publishedQuery()->reorder()->select('category_id'))
            ->with('parent')
            ->withCount(['articles' => fn ($q) => $q->where('author_id', $user->id)->where('status', 'published')])
            ->orderByDesc('articles_count')
            ->take(5)
            ->get();

        return view('public.byline', [
            'penulis' => $user,
            'articles' => $publishedQuery()->paginate(10)->withQueryString(),
            'stats' => $stats,
            'topCategories' => $topCategories,
            'popularArticles' => $publishedQuery()->reorder()->orderByDesc('views_count')->take(5)->get(),
        ]);
    }

    /** Pilihan urutan & rentang waktu di halaman pencarian. */
    public const SEARCH_SORTS = ['relevan' => 'Relevansi', 'terbaru' => 'Terbaru', 'terpopuler' => 'Terpopuler'];

    public const SEARCH_TIMES = ['semua' => 'Semua waktu', 'hari-ini' => 'Hari ini', 'kemarin' => 'Kemarin', 'sebulan' => 'Sebulan lalu', 'pilih' => 'Pilih waktu'];

    /**
     * Pencarian: kata kunci di judul & ringkasan, filter kategori dan waktu, urutan hasil.
     */
    public function cari(): View
    {
        $q = Str::limit(trim((string) request('q')), 100, '');
        $sort = array_key_exists((string) request('urut'), self::SEARCH_SORTS) ? (string) request('urut') : 'relevan';
        $time = array_key_exists((string) request('waktu'), self::SEARCH_TIMES) ? (string) request('waktu') : 'semua';

        $kategori = filled(request('kategori'))
            ? Category::with('children')->where('slug', (string) request('kategori'))->first()
            : null;

        [$from, $to] = match ($time) {
            'hari-ini' => [today(), today()->endOfDay()],
            'kemarin' => [today()->subDay(), today()->subDay()->endOfDay()],
            'sebulan' => [today()->subMonth(), now()],
            'pilih' => [$this->parseDate(request('dari')), $this->parseDate(request('sampai'))?->endOfDay()],
            default => [null, null],
        };
        if ($from && $to && $from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        $query = self::published()
            ->when($kategori, fn ($w) => $w->whereIn('category_id', collect([$kategori->id])->merge($kategori->children->pluck('id'))))
            ->when($from, fn ($w) => $w->where('published_at', '>=', $from))
            ->when($to, fn ($w) => $w->where('published_at', '<=', $to));

        if ($q !== '') {
            // Setiap kata (maks. 6) harus ada di judul atau ringkasan
            $words = collect(preg_split('/\s+/', Str::lower($q)))->filter()->unique()->take(6);
            foreach ($words as $word) {
                $like = '%' . $this->escapeLike($word) . '%';
                $query->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('excerpt', 'like', $like));
            }

            if ($sort === 'relevan') {
                // Frasa utuh di judul paling atas, lalu kata-kata yang muncul di judul
                $query->reorder()->orderByRaw('CASE WHEN LOWER(title) LIKE ? THEN 0 ELSE 1 END', ['%' . $this->escapeLike(Str::lower($q)) . '%']);
                foreach ($words as $word) {
                    $query->orderByRaw('CASE WHEN LOWER(title) LIKE ? THEN 0 ELSE 1 END', ['%' . $this->escapeLike($word) . '%']);
                }
                $query->orderByDesc('published_at');
            }
        }

        if ($sort === 'terpopuler') {
            $query->reorder()->orderByDesc('views_count')->orderByDesc('published_at');
        }

        $results = $query->paginate(10)->withQueryString();

        return view('public.cari', [
            'q' => $q,
            'sort' => $sort,
            'time' => $time,
            'kategori' => $kategori,
            'from' => $from,
            'to' => $to,
            'results' => $results,
            'kategoriOptions' => self::kategoriNav(),
            'suggestions' => self::popular(4, collect($results->items())->pluck('id')->all()),
        ]);
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
    }

    protected function isBot(): bool
    {
        $agent = strtolower((string) request()->userAgent());

        if ($agent === '') {
            return true;
        }

        foreach (['bot', 'crawler', 'spider', 'slurp', 'preview', 'facebookexternalhit', 'curl', 'wget'] as $needle) {
            if (str_contains($agent, $needle)) {
                return true;
            }
        }

        return false;
    }
}