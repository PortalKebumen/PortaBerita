<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleStatus;
use App\Models\SiteSetting;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Feed RSS 2.0 (/feed): 30 artikel terbit terbaru, tanpa artikel noindex. Cache 5 menit.
 */
class FeedController extends Controller
{
    public function __invoke(): Response
    {
        $xml = Cache::remember('feed.rss.v1', 300, function () {
            $site = SiteSetting::values();
            $siteName = $site['site_name'] ?: config('app.name');

            $articles = Article::query()
                ->where('status', ArticleStatus::Published->value)
                ->whereNotNull('published_at')
                ->where('published_at', '<=', now())
                ->whereDoesntHave('seoMeta', fn ($q) => $q->where('noindex', true))
                ->with(['author:id,name', 'category:id,name', 'media'])
                ->latest('published_at')
                ->limit(30)
                ->get();

            $e = fn (?string $value) => htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');

            $items = $articles->map(function (Article $article) use ($e) {
                $description = $article->excerpt
                    ?: Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags((string) $article->content))), 300);
                $media = $article->getFirstMedia('featured');
                $image = $media ? ($media->hasGeneratedConversion('large') ? $media->getUrl('large') : $media->getUrl()) : null;

                return '<item>'
                    . '<title>' . $e($article->title) . '</title>'
                    . '<link>' . $e($article->publicUrl()) . '</link>'
                    . '<guid isPermaLink="false">portalkebumen-article-' . $article->id . '</guid>'
                    . '<pubDate>' . $article->published_at->toRssString() . '</pubDate>'
                    . ($article->author ? '<dc:creator>' . $e($article->author->name) . '</dc:creator>' : '')
                    . ($article->category ? '<category>' . $e($article->category->name) . '</category>' : '')
                    . '<description>' . $e($description) . '</description>'
                    . ($image ? '<media:content url="' . $e($image) . '" medium="image"/>' : '')
                    . '</item>';
            })->implode("\n");

            return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
                . '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:media="http://search.yahoo.com/mrss/">' . "\n"
                . '<channel>'
                . '<title>' . $e($siteName) . '</title>'
                . '<link>' . $e(url('/')) . '</link>'
                . '<atom:link href="' . $e(route('feed')) . '" rel="self" type="application/rss+xml"/>'
                . '<description>' . $e($site['meta_description'] ?? 'Berita terkini seputar Kabupaten Kebumen') . '</description>'
                . '<language>id</language>'
                . '<lastBuildDate>' . now()->toRssString() . '</lastBuildDate>' . "\n"
                . $items . "\n"
                . '</channel></rss>' . "\n";
        });

        return response($xml, 200, ['Content-Type' => 'application/rss+xml; charset=UTF-8']);
    }
}
