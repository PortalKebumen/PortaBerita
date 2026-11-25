<?php

namespace App\Models;

use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class Category extends Model
{
    use LogsModelActivity;

    protected $fillable = [
        'name', 'slug', 'parent_id'
    ];

    protected array $logAttributes = ['name', 'slug', 'parent_id'];
    protected string $logLabel = 'Kategori';

    // otomatis membuat slug
    protected static function booted(): void
    {
        static::saving(function (Category $category) {
            if (empty($category->slug) || $category->isDirty('name')) {
                $category->slug = Str::slug($category->name);
            }
        });

        // Nama/slug kategori dipakai di menu navbar & footer
        static::saved(fn () => MenuItem::flushCache());
        static::deleted(fn () => MenuItem::flushCache());
    }

    // relasi ke kategori parent
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    // relasi ke sub-kategori children
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    // relasi ke kategori article
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    /**
     * ID kategori yang punya artikel terbit, termasuk kategori induk yang artikelnya ada di sub-kategori.
     * Kategori kosong disembunyikan dari menu & sitemap (halaman tipis menurunkan penilaian kualitas situs).
     *
     * @return array<int, true>
     */
    public static function withPublishedArticleIds(): array
    {
        return Cache::remember('categories.with_published_articles.v1', 300, function () {
            $ids = Article::query()
                ->where('status', ArticleStatus::Published->value)
                ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))
                ->whereNotNull('category_id')
                ->distinct()
                ->pluck('category_id');

            $parents = static::whereIn('id', $ids)->whereNotNull('parent_id')->pluck('parent_id');

            return $ids->merge($parents)->unique()->mapWithKeys(fn ($id) => [(int) $id => true])->all();
        });
    }

    public function hasPublishedArticles(): bool
    {
        return isset(static::withPublishedArticleIds()[$this->id]);
    }
}