<?php

namespace Tests\Feature;

use App\Jobs\GenerateSitemap;
use App\Models\Article;
use App\Models\Category;
use App\Models\Page;
use App\Models\User;
use App\Services\Analytics\HostingNetwork;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PublicTrafficQualityTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const BROWSER = 'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Mobile Safari/537.36';

    private function publishedArticle(Category $category, string $slug = 'berita-kebumen'): Article
    {
        return Article::create([
            'title' => 'Berita ' . $slug,
            'slug' => $slug,
            'excerpt' => 'Ringkasan',
            'content' => 'Isi berita.',
            'status' => 'published',
            'published_at' => now()->subHour(),
            'author_id' => User::factory()->create()->id,
            'category_id' => $category->id,
        ]);
    }

    public function test_sitemap_skips_empty_categories_and_lists_published_pages(): void
    {
        $dir = storage_path('framework/testing/sitemap-' . uniqid());
        File::ensureDirectoryExists($dir);
        $this->app->usePublicPath($dir);

        $parent = Category::create(['name' => 'Ekonomi']);
        $sub = Category::create(['name' => 'Pasar Harga', 'parent_id' => $parent->id]);
        Category::create(['name' => 'Geopark']);
        $this->publishedArticle($sub);
        Page::create(['title' => 'Tentang Kami', 'slug' => 'tentang-kami', 'content' => 'Profil', 'status' => 'published']);
        Page::create(['title' => 'Draf', 'slug' => 'draf', 'content' => 'Belum terbit', 'status' => 'draft']);

        (new GenerateSitemap)->handle();
        $xml = File::get($dir . '/sitemap-pages.xml');
        File::deleteDirectory($dir);

        $this->assertStringContainsString('/kategori/ekonomi<', $xml, 'Kategori induk yang artikelnya ada di sub-kategori tetap masuk');
        $this->assertStringContainsString('/kategori/pasar-harga<', $xml);
        $this->assertStringNotContainsString('/kategori/geopark', $xml);
        $this->assertStringContainsString('/halaman/tentang-kami<', $xml);
        $this->assertStringNotContainsString('/halaman/draf', $xml);
    }

    public function test_empty_category_page_is_noindex_and_hidden_from_category_menu(): void
    {
        $filled = Category::create(['name' => 'Berita Kebumen']);
        Category::create(['name' => 'Geopark']);
        $this->publishedArticle($filled);

        $this->get('/kategori/geopark')->assertOk()->assertSee('<meta name="robots" content="noindex, nofollow">', false);
        $this->get('/kategori/berita-kebumen')->assertOk()->assertDontSee('content="noindex', false);

        $labels = array_column(\App\Http\Controllers\Public\PublicController::kategoriNav(), 'label');
        $this->assertSame(['Berita Kebumen'], $labels);
    }

    public function test_article_view_counted_once_per_visitor_per_day_without_relying_on_session(): void
    {
        $article = $this->publishedArticle(Category::create(['name' => 'Berita Kebumen']));

        // Dua kunjungan tanpa cookie (sesi baru tiap kali) dari IP yang sama
        $this->withHeaders(['User-Agent' => self::BROWSER])->get('/artikel/' . $article->slug)->assertOk();
        $this->flushSession();
        $this->withHeaders(['User-Agent' => self::BROWSER])->get('/artikel/' . $article->slug)->assertOk();

        $this->assertSame(1, (int) $article->fresh()->views_count);
    }

    public function test_article_view_from_datacenter_network_is_not_counted(): void
    {
        $this->mock(HostingNetwork::class, fn ($mock) => $mock->shouldReceive('contains')->andReturn(true));
        $article = $this->publishedArticle(Category::create(['name' => 'Berita Kebumen']));

        $this->withHeaders(['User-Agent' => self::BROWSER])->get('/artikel/' . $article->slug)->assertOk();

        $this->assertSame(0, (int) $article->fresh()->views_count);
    }

    public function test_hosting_network_recognizes_cloud_asn_but_not_indonesian_isp(): void
    {
        if (! is_file(config('analytics.geoip.asn_path'))) {
            $this->markTestSkipped('Database DB-IP ASN Lite belum diunduh (php artisan geoip:update).');
        }

        $network = new HostingNetwork;
        $this->assertTrue($network->contains('35.191.0.1'));   // Google Cloud
        $this->assertTrue($network->contains('3.5.140.2'));    // AWS
        $this->assertFalse($network->contains('114.124.0.1')); // Telkomsel
        $this->assertFalse($network->contains('104.28.0.1'));  // Cloudflare (iCloud Private Relay/WARP)
    }
}
