<?php

namespace App\Models;

use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Page extends Model
{
    use LogsModelActivity;

    public const STATUSES = ['draft' => 'Draft', 'published' => 'Terbit'];

    protected $fillable = ['title', 'slug', 'content', 'meta_title', 'meta_description', 'status', 'updated_by'];

    protected array $logAttributes = ['title', 'slug', 'status', 'meta_title', 'meta_description'];

    protected string $logLabel = 'Halaman';

    protected static function booted(): void
    {
        static::saving(function (Page $page) {
            $page->slug = Str::slug($page->slug ?: $page->title);
        });

        // Judul/status halaman ikut tampil di menu, jadi cache menu harus diperbarui.
        static::saved(fn () => MenuItem::flushCache());
        static::deleted(fn () => MenuItem::flushCache());
    }

    /** @param  Builder<Page>  $query */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function url(): string
    {
        return route('public.halaman', $this->slug);
    }

    /** @return BelongsTo<User, $this> */
    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** @return HasMany<MenuItem, $this> */
    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class);
    }
}
