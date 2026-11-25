<?php

namespace Tests\Feature;

use App\Jobs\GenerateSitemap;
use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SeoStructuredDataTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function article(string $slug, Category $category, $publishedAt = null): Article
    {
        return Article::create([
            'title' => 'Jadwal Prameks & Kereta ' . $slug,
            'slug' => $slug,
            'excerpt' => 'Ringkasan',
            'content' => 'Isi berita.',
            'status' => 'published',
            'published_at' => $publishedAt ?? now()->subHour(),
            'author_id' => User::factory()->create()->id,
            'category_id' => $category->id,
        ]);
    }

    public function test_article_and_category_pages_have_breadcrumb_list_schema(): void
    {
        $parent = Category::create(['name' => 'Berita Kebumen']);
        $sub = Category::create(['name' => 'Peristiwa', 'parent_id' => $parent->id]);
        $article = $this->article('prameks', $sub);

        $html = $this->get('/artikel/prameks')->assertOk()->getContent();
        $this->assertStringContainsString('"@type": "BreadcrumbList"', $html);
        $this->assertStringContainsString('"item": "' . route('public.kategori.sub', ['berita-kebumen', 'peristiwa']) . '"', $html);
        $this->assertStringContainsString('"position": 4', $html);
        $this->assertStringContainsString('"item": "' . $article->publicUrl() . '"', $html);

        $this->get('/kategori/berita-kebumen/peristiwa')->assertOk()
            ->assertSee('"@type": "BreadcrumbList"', false)
            ->assertSee('"position": 3', false);
    }

    public function test_news_sitemap_lists_only_articles_from_last_two_days_without_noindex(): void
    {
        $category = Category::create(['name' => 'Berita Kebumen']);
        $this->article('baru', $category);
        $this->article('lama', $category, now()->subDays(3));
        $this->article('tersembunyi', $category)->seoMeta()->create(['noindex' => true]);

        $response = $this->get('/sitemap-news.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $xml = $response->getContent();

        $this->assertNotFalse(simplexml_load_string($xml), 'XML harus valid');
        $this->assertStringContainsString('<loc>' . url('/artikel/baru') . '</loc>', $xml);
        $this->assertStringContainsString('<news:language>id</news:language>', $xml);
        $this->assertStringContainsString('<news:title>Jadwal Prameks &amp; Kereta baru</news:title>', $xml);
        $this->assertStringNotContainsString('/artikel/lama', $xml);
        $this->assertStringNotContainsString('/artikel/tersembunyi', $xml);
    }

    public function test_sitemap_index_includes_news_sitemap(): void
    {
        $dir = storage_path('framework/testing/sitemap-' . uniqid());
        File::ensureDirectoryExists($dir);
        $this->app->usePublicPath($dir);

        (new GenerateSitemap)->handle();
        $index = File::get($dir . '/sitemap.xml');
        File::deleteDirectory($dir);

        $this->assertStringContainsString(route('sitemap.news'), $index);
    }
}
