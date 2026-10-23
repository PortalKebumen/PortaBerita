<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\SiteSettingsManager;
use App\Models\SiteSetting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdsenseSettingTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function admin(): User
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        return $user;
    }

    public function test_pasted_meta_tag_is_saved_as_publisher_id_and_rendered_on_public_pages(): void
    {
        Livewire::actingAs($this->admin())
            ->test(SiteSettingsManager::class)
            ->set('adsensePublisherId', '<meta name="google-adsense-account" content="ca-pub-3832797228688473">')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('ca-pub-3832797228688473', SiteSetting::get('adsense_publisher_id'));

        $this->get('/')->assertOk()
            ->assertSee('<meta name="google-adsense-account" content="ca-pub-3832797228688473">', false)
            ->assertDontSee('adsbygoogle.js', false);

        $this->get('/ads.txt')->assertOk()
            ->assertSeeText('google.com, pub-3832797228688473, DIRECT, f08c47fec0942fa0');
    }

    public function test_auto_ads_script_only_when_enabled(): void
    {
        Livewire::actingAs($this->admin())
            ->test(SiteSettingsManager::class)
            ->set('adsensePublisherId', 'pub-3832797228688473')
            ->set('adsenseAutoAds', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->get('/')->assertSee('pagead/js/adsbygoogle.js?client=ca-pub-3832797228688473', false);
    }

    public function test_invalid_publisher_id_is_rejected_and_ads_txt_missing_when_empty(): void
    {
        Livewire::actingAs($this->admin())
            ->test(SiteSettingsManager::class)
            ->set('adsensePublisherId', 'bukan-kode')
            ->call('save')
            ->assertHasErrors('adsensePublisherId');

        $this->get('/ads.txt')->assertNotFound();
    }
}
