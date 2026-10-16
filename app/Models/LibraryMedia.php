<?php

namespace App\Models;

use Spatie\Image\Enums\Fit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class LibraryMedia extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'uploaded_by',
    ];

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('library');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // Kotak pratinjau di grid Media Library & pemilih media
        $this
            ->addMediaConversion('small')
            ->fit(Fit::Crop, 300, 300)
            ->format('webp')
            ->quality(75)
            ->performOnCollections('library')
            ->nonQueued();

        // Max = diperkecil bila lebih besar, tidak pernah diperbesar
        $this
            ->addMediaConversion('medium')
            ->fit(Fit::Max, 800, 800)
            ->format('webp')
            ->quality(80)
            ->performOnCollections('library')
            ->nonQueued();

        $this
            ->addMediaConversion('large')
            ->fit(Fit::Max, 1600, 1600)
            ->format('webp')
            ->quality(80)
            ->performOnCollections('library')
            ->nonQueued();
    }
}