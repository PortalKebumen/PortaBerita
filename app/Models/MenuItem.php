<?php

namespace App\Models;

use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class MenuItem extends Model
{
    use LogsModelActivity;

    /** Lokasi menu yang tersedia di tema publik. */
    public const LOCATIONS = [
        'navbar' => 'Menu Utama (Navbar)',
        'footer' => 'Footer (Kolom Tautan)',
        'footer_bottom' => 'Footer Bawah (Baris Hak Cipta)',
    ];

    public const TYPES = [
        'page' => 'Halaman',
        'category' => 'Kategori',
        'url' => 'Tautan Kustom',
        'heading' => 'Judul Kolom (tanpa tautan)',
    ];

    protected $fillable = ['location', 'parent_id', 'label', 'type', 'page_id', 'category_id', 'url', 'new_tab', 'sort_order'];

    protected $casts = [
        'new_tab' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected array $logAttributes = ['location', 'label', 'type', 'url', 'parent_id', 'sort_order'];

    protected string $logLabel = 'Menu';

    protected static function booted(): void
    {
        static::saved(fn () => static::flushCache());
        static::deleted(fn () => static::flushCache());
    }

    /** @return BelongsTo<Page, $this> */
    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsTo<MenuItem, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class, 'parent_id');
    }

    /** @return HasMany<MenuItem, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(MenuItem::class, 'parent_id')->orderBy('sort_order')->orderBy('id');
    }

    /** Label tampil: label kustom, atau judul halaman / nama kategori. */
    public function displayLabel(): string
    {
        return filled($this->label) ? $this->label : match ($this->type) {
            'page' => (string) $this->page?->title,
            'category' => (string) $this->category?->name,
            default => (string) $this->url,
        };
    }

    public function resolvedUrl(): ?string
    {
        return match ($this->type) {
            'page' => $this->page?->url(),
            'category' => $this->category ? $this->categoryUrl($this->category) : null,
            'url' => $this->url,
            default => null,
        };
    }

    /** Item disembunyikan bila halaman belum terbit atau target sudah tidak ada. */
    public function isVisible(): bool
    {
        return match ($this->type) {
            'page' => $this->page?->isPublished() ?? false,
            'category' => $this->category !== null,
            'url' => filled($this->url),
            default => true,
        };
    }

    private function categoryUrl(Category $category): string
    {
        return $category->parent_id && $category->parent
            ? route('public.kategori.sub', [$category->parent->slug, $category->slug])
            : route('public.kategori', $category->slug);
    }

    /**
     * Pohon menu siap tampil (array biasa, aman untuk cache) untuk satu lokasi.
     * Item kategori di navbar otomatis membawa sub-kategorinya sebagai dropdown bila tidak punya anak manual.
     *
     * @return array<int, array{label: string, url: ?string, new_tab: bool, type: string, children: array}>
     */
    public static function tree(string $location): array
    {
        return Cache::remember(static::cacheKey($location), 600, function () use ($location) {
            $items = static::query()
                ->where('location', $location)
                ->with(['page', 'category.parent', 'category.children', 'children.page', 'children.category.parent'])
                ->whereNull('parent_id')
                ->orderBy('sort_order')->orderBy('id')
                ->get();

            return $items->filter->isVisible()->map(function (MenuItem $item) use ($location) {
                $children = $item->children->filter->isVisible()->map(fn (MenuItem $child) => static::toNode($child, []))->values()->all();

                if (! $children && $location === 'navbar' && $item->type === 'category' && $item->category) {
                    $children = $item->category->children->sortBy('name')->map(fn (Category $c) => [
                        'label' => $c->name,
                        'url' => route('public.kategori.sub', [$item->category->slug, $c->slug]),
                        'new_tab' => false,
                        'type' => 'category',
                        'children' => [],
                    ])->values()->all();
                }

                return static::toNode($item, $children);
            })->values()->all();
        });
    }

    private static function toNode(MenuItem $item, array $children): array
    {
        return [
            'label' => $item->displayLabel(),
            'url' => $item->resolvedUrl(),
            'new_tab' => $item->new_tab,
            'type' => $item->type,
            'children' => $children,
        ];
    }

    public static function cacheKey(string $location): string
    {
        return 'menu.tree.v1.' . $location;
    }

    public static function flushCache(): void
    {
        foreach (array_keys(static::LOCATIONS) as $location) {
            Cache::forget(static::cacheKey($location));
        }
    }
}
