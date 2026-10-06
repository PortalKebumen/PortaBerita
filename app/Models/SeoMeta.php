<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['article_id', 'meta_title', 'meta_description', 'canonical_url', 'og_image', 'noindex', 'nofollow'])]
class SeoMeta extends Model
{
    protected $table = 'seo_meta';

    protected $casts = [
        'noindex' => 'boolean',
        'nofollow' => 'boolean',
    ];

    /** @return BelongsTo<Article, $this> */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }
}
