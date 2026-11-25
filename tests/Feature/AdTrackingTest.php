<?php

namespace Tests\Feature;

use App\Models\AdMetric;
use App\Models\Advertisement;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AdTrackingTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const BROWSER = 'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Mobile Safari/537.36';

    private function ad(): Advertisement
    {
        return Advertisement::create([
            'advertiser_name' => 'Toko Kebumen',
            'target_url' => 'https://example.com/toko',
            'placement' => 'header',
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
            'status' => 'active',
        ]);
    }

    public function test_click_without_prior_impression_redirects_but_is_not_counted(): void
    {
        $ad = $this->ad();

        $this->withHeaders(['User-Agent' => self::BROWSER])->get("/ads/{$ad->id}/click")
            ->assertRedirect('https://example.com/toko');

        $this->assertSame(0, AdMetric::where('type', 'click')->count());
    }

    public function test_click_after_impression_is_counted_once_per_day(): void
    {
        $ad = $this->ad();
        $browser = $this->withHeaders(['User-Agent' => self::BROWSER]);

        $browser->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->postJson("/ads/{$ad->id}/impression")->assertOk();
        $browser->get("/ads/{$ad->id}/click")->assertRedirect();
        $browser->get("/ads/{$ad->id}/click")->assertRedirect();

        $this->assertSame(1, AdMetric::where('type', 'impression')->count());
        $this->assertSame(1, AdMetric::where('type', 'click')->count());
    }

    public function test_robots_txt_blocks_ad_click_links_in_production_mode(): void
    {
        config(['system.indexing' => true]);

        $this->get('/robots.txt')->assertOk()->assertSee("Disallow: /ads/\n", false)->assertSee('Sitemap: ', false);
    }
}
