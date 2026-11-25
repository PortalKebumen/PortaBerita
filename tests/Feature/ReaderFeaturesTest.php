<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\SiteSetting;
use App\Models\Tag;
use App\Models\User;
use App\Support\ArticleContent;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ReaderFeaturesTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function article(string $slug, ?Category $category = null, array $extra = []): Article
    {
        return Article::create(array_merge([
            'title' => 'Berita ' . $slug,
            'slug' => $slug,
            'excerpt' => 'Ringkasan ' . $slug,
            'content' => '<p>Isi berita.</p>',
            'status' => 'published',
            'published_at' => now()->subHour(),
            'author_id' => User::factory()->create()->id,
            'category_id' => ($category ?? Category::firstOrCreate(['slug' => 'berita-kebumen'], ['name' => 'Berita Kebumen']))->id,
        ], $extra));
    }

    public function test_read_also_is_inserted_after_third_top_level_paragraph_only_in_long_articles(): void
    {
        $long = '<p>1</p><blockquote><p>kutipan</p></blockquote><p>2</p><p>3</p><p>4</p><p>5</p>';
        $this->assertSame('<p>1</p><blockquote><p>kutipan</p></blockquote><p>2</p><p>3</p>[X]<p>4</p><p>5</p>',
            ArticleContent::insertAfterParagraph($long, '[X]'));

        $short = '<p>1</p><p>2</p><p>3</p>';
        $this->assertSame($short, ArticleContent::insertAfterParagraph($short, '[X]'));
    }

    public function test_article_page_shows_read_also_tags_and_follow_buttons(): void
    {
        $category = Category::create(['name' => 'Transportasi']);
        $this->article('terkait-satu', $category);
        $article = $this->article('utama', $category, [
            'content' => '<p>Satu.</p><p>Dua.</p><p>Tiga.</p><p>Empat.</p><p>Lima.</p>',
        ]);
        $tag = Tag::create(['name' => 'Prameks']);
        $article->tags()->attach($tag);
        SiteSetting::setMany([
            'whatsapp_channel' => 'https://whatsapp.com/channel/abc',
            'google_news_url' => 'https://news.google.com/publications/xyz',
        ]);

        $this->get('/artikel/utama')->assertOk()
            ->assertSeeInOrder(['Tiga.', 'Baca juga', 'Berita terkait-satu', 'Empat.'])
            ->assertSee('href="' . route('public.tag', 'prameks') . '"', false)
            ->assertSee('#Prameks')
            ->assertSee('https://whatsapp.com/channel/abc')
            ->assertSee('Google News');
    }

    public function test_tag_page_lists_tagged_articles_and_thin_tag_is_noindex(): void
    {
        $tag = Tag::create(['name' => 'Prameks']);
        $this->article('prameks-satu')->tags()->attach($tag);

        $this->get('/tag/prameks')->assertOk()
            ->assertSee('#Prameks')
            ->assertSee('Berita prameks-satu')
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);

        $this->article('prameks-dua')->tags()->attach($tag);
        $this->get('/tag/prameks')->assertOk()->assertDontSee('content="noindex', false);
        $this->get('/tag/tidak-ada')->assertNotFound();
    }

    public function test_daily_index_lists_articles_of_selected_date(): void
    {
        $this->article('hari-ini');
        $this->article('kemarin', null, ['published_at' => now()->subDay()->setTime(9, 15)]);

        // Widget terpopuler/footer juga menampilkan judul artikel, jadi yang dicek isi daftar indeksnya
        $slugs = fn ($articles) => $articles->pluck('slug')->all();
        $this->get('/indeks')->assertOk()
            ->assertViewHas('articles', fn ($a) => $slugs($a) === ['hari-ini']);
        $this->get('/indeks?tanggal=' . now()->subDay()->toDateString())->assertOk()
            ->assertViewHas('articles', fn ($a) => $slugs($a) === ['kemarin'])
            ->assertSee('09:15');
        $this->get('/indeks?tanggal=bukan-tanggal')->assertOk(); // tanggal tidak valid = hari ini
        $this->get('/indeks?tanggal=' . now()->subYear()->toDateString())->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_rss_feed_is_valid_and_skips_noindex_articles(): void
    {
        $this->article('tampil', null, ['title' => 'Jadwal Prameks & KRL']);
        $this->article('sembunyi')->seoMeta()->create(['noindex' => true]);

        $response = $this->get('/feed')->assertOk()->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8');
        $rss = simplexml_load_string($response->getContent());

        $this->assertNotFalse($rss);
        $this->assertCount(1, $rss->channel->item);
        $this->assertSame('Jadwal Prameks & KRL', (string) $rss->channel->item[0]->title);
        $this->assertSame(url('/artikel/tampil'), (string) $rss->channel->item[0]->link);

        $this->get('/')->assertSee('type="application/rss+xml"', false);
    }
}
