<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleStatus;
use App\Models\SiteSetting;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

/**
 * Sitemap Google News (/sitemap-news.xml): artikel terbit 2 hari terakhir (batas aturan Google News,
 * maksimal 1.000 URL). Dibuat langsung per permintaan dengan cache 5 menit, jadi artikel baru cepat
 * tercantum tanpa menunggu jadwal sitemap biasa (30 menit).
 */
class NewsSitemapController extends Controller
{
    public function __invoke(): Response
    {
        $xml = Cache::remember('sitemap.news.v1', 300, function () {
            $siteName = SiteSetting::get('site_name', config('app.name'));

            $articles = Article::query()
                ->where('status', ArticleStatus::Published->value)
                ->whereNotNull('published_at')
                ->whereBetween('published_at', [now()->subDays(2), now()])
                ->whereDoesntHave('seoMeta', fn ($q) => $q->where('noindex', true))
                ->latest('published_at')
                ->limit(1000)
                ->get(['id', 'title', 'slug', 'published_at']);

            $e = fn (string $value) => htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');

            $urls = $articles->map(fn (Article $article) => '<url>'
                . '<loc>' . $e($article->publicUrl()) . '</loc>'
                . '<news:news>'
                . '<news:publication><news:name>' . $e($siteName) . '</news:name><news:language>id</news:language></news:publication>'
                . '<news:publication_date>' . $article->published_at->toAtomString() . '</news:publication_date>'
                . '<news:title>' . $e($article->title) . '</news:title>'
                . '</news:news>'
                . '</url>')->implode("\n");

            return '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
                . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">' . "\n"
                . $urls . ($urls === '' ? '' : "\n")
                . '</urlset>' . "\n";
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
