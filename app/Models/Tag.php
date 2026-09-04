<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Tag extends Model
{
    protected $fillable = [
        'name', 'slug'
    ];

    protected static function booted(): void
    {
        static::saving(function (Tag $tag) {
            if (empty($tag->slug)) {
                $tag->slug = Str::slug($tag->name);
            }
        });
    }

    // relasi many-to-many ke article lewat table pivot tag_article
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'tag_article');
    }
}
