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
use Spatie\MediaLibrary\InteractsWithMedia;

enum ArticleStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Published = 'published';
    case Archived = 'archived';

    public function badge(): string
    {
        return match ($this) {
            self::Draft, self::Archived => 'neutral',
            self::Submitted => 'warning',
            self::Approved => 'info',
            self::Rejected => 'danger',
            self::Published => 'success',
        };
    }
}

#[Fillable(['title', 'slug', 'excerpt', 'content', 'status', 'author_id', 'category_id', 'is_breaking', 'is_advertorial', 'scheduled_at', 'published_at', 'archived_at'])]
class Article extends Model implements HasMedia
{
    use InteractsWithMedia, LogsModelActivity;

    protected array $logAttributes = ['title', 'slug', 'status', 'excerpt', 'content', 'is_breaking', 'is_advertorial', 'scheduled_at'];
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

    public function registerMediaConversions(?\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(150)
            ->height(150)
            ->sharpen(10)
            ->nonQueued();

        $this->addMediaConversion('medium')
            ->width(400)
            ->height(300)
            ->sharpen(10)
            ->nonQueued();

        $this->addMediaConversion('large')
            ->width(1200)
            ->height(675)
            ->sharpen(10);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($article) {
            if (empty($article->slug)) {
                $article->slug = Str::slug($article->title);
            }
        });

        static::updating(function ($article) {
            if ($article->isDirty('title') && !$article->isDirty('slug')) {
                $article->slug = Str::slug($article->title);
            }
        });
    }
}