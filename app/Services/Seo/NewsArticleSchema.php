<?php

namespace App\Services\Seo;

use App\Models\Article;
use App\Models\SiteSetting;
use Illuminate\Support\Str;

class NewsArticleSchema
{
    public static function make(Article $article): array
    {
        $article->loadMissing(['author:id,name', 'category:id,name', 'seoMeta']);

        $tz = SiteSetting::get('timezone', config('app.timezone'));
        $siteName = SiteSetting::get('site_name', config('app.name'));
        $url = $article->publicUrl();

        $description = $article->seoMeta?->meta_description
            ?: $article->excerpt
            ?: Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $article->content))), 160);

        $images = array_values(array_filter([
            $article->getFirstMediaUrl('featured') ?: null,
            $article->getFirstMediaUrl('og') ?: null,
            SiteSetting::get('og_image_url'),
        ]));

        $sameAs = array_values(array_filter([
            SiteSetting::get('facebook'),
            SiteSetting::get('instagram'),
            SiteSetting::get('x_twitter'),
            SiteSetting::get('youtube'),
        ]));

        $publisher = array_filter([
            '@type' => 'Organization',
            'name' => $siteName,
            'url' => url('/'),
            'logo' => SiteSetting::get('logo_url')
                ? ['@type' => 'ImageObject', 'url' => SiteSetting::get('logo_url')]
                : null,
            'sameAs' => $sameAs ?: null,
        ]);

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'NewsArticle',
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $url],
            'headline' => Str::limit($article->title, 110, ''),
            'description' => $description,
            'image' => $images ?: null,
            'datePublished' => $article->published_at?->copy()->timezone($tz)->toIso8601String(),
            'dateModified' => $article->updated_at?->copy()->timezone($tz)->toIso8601String(),
            'author' => $article->author
                ? ['@type' => 'Person', 'name' => $article->author->name]
                : ['@type' => 'Organization', 'name' => $siteName],
            'publisher' => $publisher,
            'articleSection' => $article->category?->name,
            'inLanguage' => SiteSetting::get('locale', 'id') === 'en' ? 'en-US' : 'id-ID',
            'isAccessibleForFree' => true,
        ];

        return array_filter($schema, fn ($v) => $v !== null && $v !== []);
    }
}