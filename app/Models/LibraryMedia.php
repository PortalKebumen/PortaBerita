<?php

namespace App\Models;

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
        $this
            ->addMediaConversion('small')
            ->width(150)
            ->height(150)
            ->sharpen(10)
            ->performOnCollections('library');

        $this
            ->addMediaConversion('medium')
            ->width(400)
            ->height(400)
            ->performOnCollections('library');

        $this
            ->addMediaConversion('large')
            ->width(1200)
            ->height(1200)
            ->performOnCollections('library');
    }
}