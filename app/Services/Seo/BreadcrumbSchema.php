<?php

namespace App\Services\Seo;

use App\Models\Article;
use App\Models\Category;

/**
 * Data terstruktur BreadcrumbList (schema.org): Google menampilkan jalur Beranda › Kategori di hasil
 * pencarian sebagai pengganti URL mentah. Isinya mengikuti breadcrumb yang tampil di halaman.
 */
class BreadcrumbSchema
{
    /** @return array<int, array{name: string, url: string}> */
    public static function forCategory(Category $category): array
    {
        $category->loadMissing('parent');
        $items = [['name' => 'Beranda', 'url' => route('public.beranda')]];

        if ($category->parent) {
            $items[] = ['name' => $category->parent->name, 'url' => route('public.kategori', $category->parent->slug)];
            $items[] = ['name' => $category->name, 'url' => route('public.kategori.sub', [$category->parent->slug, $category->slug])];
        } else {
            $items[] = ['name' => $category->name, 'url' => route('public.kategori', $category->slug)];
        }

        return $items;
    }

    /** @return array<int, array{name: string, url: string}> */
    public static function forArticle(Article $article): array
    {
        $items = $article->category
            ? self::forCategory($article->category)
            : [['name' => 'Beranda', 'url' => route('public.beranda')]];

        $items[] = ['name' => $article->title, 'url' => $article->publicUrl()];

        return $items;
    }

    /** @param  array<int, array{name: string, url: string}>  $items */
    public static function make(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(fn (array $item, int $i) => [
                '@type' => 'ListItem',
                'position' => $i + 1,
                'name' => $item['name'],
                'item' => $item['url'],
            ], $items, array_keys($items)),
        ];
    }
}
