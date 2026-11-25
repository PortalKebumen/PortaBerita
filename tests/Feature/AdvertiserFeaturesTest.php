<?php

namespace Tests\Feature;

use App\Livewire\Admin\SiteSettingsManager;
use App\Models\AdMetric;
use App\Models\Advertisement;
use App\Models\Article;
use App\Models\Category;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class AdvertiserFeaturesTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function ad(array $extra = []): Advertisement
    {
        return Advertisement::create(array_merge([
            'advertiser_name' => 'Toko Kebumen',
            'target_url' => 'https://example.com',
            'placement' => 'sidebar',
            'start_date' => now()->subDays(2)->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
            'status' => 'active',
        ], $extra));
    }

    private function article(): Article
    {
        return Article::create([
            'title' => 'Berita uji', 'slug' => 'berita-uji', 'content' => '<p>Isi.</p>', 'status' => 'published',
            'published_at' => now()->subHour(), 'author_id' => User::factory()->create()->id,
            'category_id' => Category::create(['name' => 'Berita Kebumen'])->id,
        ]);
    }

    public function test_signed_report_link_shows_totals_and_daily_numbers(): void
    {
        $ad = $this->ad();
        foreach ([['impression', 0], ['impression', 0], ['click', 0], ['impression', 1]] as [$type, $daysAgo]) {
            AdMetric::create(['advertisement_id' => $ad->id, 'type' => $type, 'created_at' => now()->subDays($daysAgo)]);
        }

        $this->get($ad->reportUrl())->assertOk()
            ->assertSee('Toko Kebumen')
            ->assertSee('Sidebar Banner')
            ->assertSeeInOrder(['Impresi', 'Klik', 'CTR', '3', '1', '33,33%'])
            ->assertSee('noindex, nofollow', false);

        // Tanpa tanda tangan / tanda tangan diubah: ditolak
        $this->get(route('ads.report', $ad))->assertForbidden();
        $this->get(str_replace('signature=', 'signature=x', $ad->reportUrl()))->assertForbidden();
    }

    public function test_pasang_iklan_page_shows_media_kit_with_indonesian_views_only(): void
    {
        Page::create(['title' => 'Pasang Iklan', 'slug' => 'pasang-iklan', 'content' => '<p>Hubungi kami.</p>', 'status' => 'published']);
        SiteSetting::setMany(['whatsapp' => '0812-3456-7890']);
        DB::table('article_daily_locations')->insert([
            ['date' => now()->toDateString(), 'country_code' => 'ID', 'country' => 'Indonesia', 'region' => 'Central Java', 'city' => 'Kebumen', 'views' => 300],
            ['date' => now()->toDateString(), 'country_code' => 'ID', 'country' => 'Indonesia', 'region' => 'Jakarta', 'city' => 'Jakarta', 'views' => 100],
            ['date' => now()->toDateString(), 'country_code' => 'US', 'country' => 'United States', 'region' => 'California', 'city' => 'Mountain View', 'views' => 900],
        ]);

        $this->get('/halaman/pasang-iklan')->assertOk()
            ->assertSee('Data pembaca')
            ->assertSeeInOrder(['Tayangan artikel dari Indonesia', '400'])
            ->assertSeeInOrder(['Pembaca dari Jawa Tengah', '75%'])
            ->assertSee('1216×250')
            ->assertSee('https://wa.me/6281234567890', false);

        $this->get('/halaman/tentang-kami')->assertNotFound();
    }

    public function test_adsense_unit_fills_slot_only_when_no_direct_ad(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        Livewire::actingAs($admin)->test(SiteSettingsManager::class)
            ->set('adsensePublisherId', 'ca-pub-3832797228688473')
            ->set('adsenseSlotSidebar', '<ins class="adsbygoogle" data-ad-client="ca-pub-3832797228688473" data-ad-slot="9876543210"></ins>')
            ->call('save')
            ->assertHasNoErrors();
        $this->assertSame('9876543210', SiteSetting::get('adsense_slot_sidebar'));

        $article = $this->article();
        $this->get($article->publicUrl())->assertOk()
            ->assertSee('data-ad-slot="9876543210"', false)
            ->assertSee('pagead/js/adsbygoogle.js', false);

        $this->ad(); // iklan langsung di sidebar: AdSense tidak dipakai di lokasi itu
        $this->get($article->publicUrl())->assertOk()->assertDontSee('data-ad-slot="9876543210"', false);
    }
}
