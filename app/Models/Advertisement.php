<?php

namespace App\Models;

use App\Traits\LogsModelActivity;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Advertisement extends Model implements HasMedia
{
   use HasFactory, InteractsWithMedia, LogsModelActivity;

    protected array $logAttributes = [
        'advertiser_name',
        'advertiser_contact',
        'target_url',
        'placement',
        'status',
        'start_date',
        'end_date',
    ];

    protected string $logLabel = 'Iklan';

    protected $fillable = [
        'advertiser_name',
        'advertiser_contact',
        'target_url',
        'placement',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Ruang yang disediakan tiap lokasi iklan (piksel CSS di desktop). Banner ukuran berapa pun
     * diperkecil agar muat di ruang ini tanpa terpotong; "populer" hanya saran ukuran standar IAB.
     * Lebar maks mengikuti layout publik: kontainer 1216px, kolom artikel 800px, kolom sidebar 384px.
     */
    public const PLACEMENTS = [
        'header' => ['label' => 'Header Banner', 'max_width' => 1216, 'max_height' => 250, 'popular' => ['970×250', '970×90', '728×90']],
        'sidebar' => ['label' => 'Sidebar Banner', 'max_width' => 384, 'max_height' => 600, 'popular' => ['300×250', '336×280', '300×600']],
        'inline_artikel' => ['label' => 'Inline Artikel', 'max_width' => 800, 'max_height' => 250, 'popular' => ['728×90', '468×60', '300×250']],
        'footer' => ['label' => 'Footer Banner', 'max_width' => 1216, 'max_height' => 250, 'popular' => ['970×250', '970×90', '728×90']],
    ];

    /**
     * Relationship to ad metrics.
     */
    public function metrics(): HasMany
    {
        return $this->hasMany(AdMetric::class);
    }

    public function impressions(): HasMany
    {
        return $this->hasMany(AdMetric::class)->where('type', 'impression');
    }

    public function clicks(): HasMany
    {
        return $this->hasMany(AdMetric::class)->where('type', 'click');
    }

    /**
     * Get banner URL: media library first, then check local banner file in storage, then fallback.
     */
    public function getBannerUrlAttribute(): ?string
    {
        if ($media = $this->getFirstMedia('banner')) {
            return $media->hasGeneratedConversion('web') ? $media->getUrl('web') : $media->getUrl();
        }

        // Check if banner exists in storage/banners directory with convention: ad-{id}.*
        $patterns = [
            "banners/ad-{$this->id}.jpg",
            "banners/ad-{$this->id}.jpeg",
            "banners/ad-{$this->id}.png",
            "banners/ad-{$this->id}.webp",
            "banners/ad-{$this->id}.gif",
        ];

        foreach ($patterns as $pattern) {
            if (file_exists(public_path('storage/' . $pattern))) {
                return asset('storage/' . $pattern);
            }
        }

        return null;
    }

    /**
     * Dynamic effective status with auto-stop for expired ads.
     */
    public function getEffectiveStatusAttribute(): string
    {
        $today = Carbon::today();

        if ($this->status === 'inactive') {
            return 'inactive';
        }

        if ($this->end_date && $this->end_date->lt($today)) {
            return 'expired';
        }

        if ($this->start_date && $this->start_date->gt($today)) {
            return 'scheduled';
        }

        return 'active';
    }

    /**
     * Check if ad expires within next 7 days.
     */
    public function getIsExpiringSoonAttribute(): bool
    {
        $today = Carbon::today();
        $sevenDaysLater = Carbon::today()->addDays(7);

        return $this->status === 'active'
            && $this->end_date
            && $this->end_date->gte($today)
            && $this->end_date->lte($sevenDaysLater);
    }

    /**
     * Scope for active ads currently running in a placement.
     */
    public function scopeActiveRunning(Builder $query, ?string $placement = null): Builder
    {
        $today = Carbon::today()->toDateString();

        $q = $query->where('status', 'active')
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today);

        if ($placement) {
            $q->where('placement', $placement);
        }

        return $q;
    }

    /**
     * Format number helper (e.g. 173400 => 173.4K)
     */
    public static function formatMetricNumber(int $num): string
    {
        if ($num >= 1000000) {
            return round($num / 1000000, 1) . 'M';
        }
        if ($num >= 1000) {
            return round($num / 1000, 1) . 'K';
        }
        return (string) $num;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('banner')->singleFile();
    }

    /**
     * Banner disajikan sebagai WebP terkompresi dengan dimensi tetap (dibatasi 2440px agar file raksasa
     * tidak terkirim). GIF tidak dikonversi karena animasinya akan hilang.
     */
    public function registerMediaConversions(?\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        if ($media && $media->mime_type === 'image/gif') {
            return;
        }

        $this->addMediaConversion('web')
            ->fit(\Spatie\Image\Enums\Fit::Max, 2440, 2440)
            ->format('webp')
            ->quality(85)
            ->performOnCollections('banner')
            ->nonQueued();
    }
}
