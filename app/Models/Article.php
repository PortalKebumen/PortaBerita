<?php

namespace App\Models;

use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\InteractsWithMedia;

#[Fillable(['title', 'slug', 'excerpt', 'content', 'status', 'author_id', 'category_id', 'is_breaking', 'is_advertorial', 'scheduled_at', 'published_at', 'archived_at'])]
class Article extends Model implements HasMedia
{
    use InteractsWithMedia, LogsModelActivity;

    // Dipakai juga oleh Riwayat Versi (App\Services\ArticleHistory); SEO & gambar dicatat lewat recordExtras()
    protected array $logAttributes = ['title', 'slug', 'status', 'excerpt', 'content', 'category_id', 'author_id', 'is_breaking', 'is_advertorial', 'scheduled_at', 'published_at'];
    protected string $logLabel = 'Artikel';

    protected $casts = [
        'status' => ArticleStatus::class,
        'is_breaking' => 'boolean',
        'is_advertorial' => 'boolean',
        'published_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** @return BelongsToMany<Tag, $this> */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /** @return HasOne<SeoMeta, $this> */
    public function seoMeta(): HasOne
    {
        return $this->hasOne(SeoMeta::class);
    }

    /** @return HasMany<RevisionNote, $this> */
    public function revisions(): HasMany
    {
        return $this->hasMany(RevisionNote::class)->latest();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('featured')
            ->singleFile()
            ->useFallbackUrl(asset('images/placeholder-article.jpg'))
            ->useFallbackPath(public_path('images/placeholder-article.jpg'));

        $this->addMediaCollection('og')
            ->singleFile();
    }

    /**
     * Ukuran turunan gambar artikel. Semua 16:9 (sama dengan bingkai tampilan) dan WebP agar ringan;
     * dipakai sebagai srcset lewat <x-responsive-image> sehingga HP mengunduh versi kecil.
     * Dibuat langsung saat disimpan (nonQueued) karena hosting tidak menjalankan queue worker.
     */
    public const IMAGE_SIZES = ['thumb' => 320, 'medium' => 640, 'large' => 1280];

    public function registerMediaConversions(?\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        foreach (self::IMAGE_SIZES as $name => $width) {
            $this->addMediaConversion($name)
                ->fit(Fit::Crop, $width, (int) round($width * 9 / 16))
                ->format('webp')
                ->quality($width <= 320 ? 75 : 80)
                ->performOnCollections('featured', 'og')
                ->nonQueued();
        }

        // Pratinjau tautan di WhatsApp/Facebook/X: rasio 1.91:1 & JPEG (WebP belum didukung semua platform)
        $this->addMediaConversion('social')
            ->fit(Fit::Crop, 1200, 630)
            ->format('jpg')
            ->quality(82)
            ->performOnCollections('featured', 'og')
            ->nonQueued();
    }

    public function publicUrl(): string
    {
        return url('/artikel/' . $this->slug);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($article) {
            if (empty($article->slug)) {
                $article->slug = Str::slug($article->title);
            }
        });
    }
}