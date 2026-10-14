<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
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

    public function beranda(): View
    {
        // Pencarian dari form di header (?q=)
        $q = trim((string) request('q'));

        if ($q !== '') {
            $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $q) . '%';

            $results = self::published()
                ->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('excerpt', 'like', $like))
                ->paginate(12)
                ->withQueryString();

            return view('public.beranda', [
                'search' => $q,
                'searchResults' => $results,
                'featured' => collect(),
                'latest' => collect(),
                'popular' => collect(),
                'kategoriBlocks' => collect(),
            ]);
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
            'search' => null,
            'searchResults' => null,
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

    public function byline(User $user): View
    {
        return view('public.byline', [
            'penulis' => $user,
            'articles' => self::published()
                ->where('author_id', $user->id)
                ->paginate(9)
                ->withQueryString(),
        ]);
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