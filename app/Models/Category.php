<?php

namespace App\Models;

use App\Traits\LogsModelActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
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
}