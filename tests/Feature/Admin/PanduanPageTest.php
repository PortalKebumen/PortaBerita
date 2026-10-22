<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PanduanPageTest extends TestCase
{
    use LazilyRefreshDatabase;

    public static function roles(): array
    {
        return [
            'reporter' => ['Penulis', ['id="menulis"', 'id="media"'], ['id="review"', 'id="iklan"', 'id="pengguna"', 'id="pengaturan"']],
            'redaktur' => ['Editor', ['id="menulis"', 'id="review"', 'id="kategori"', 'id="analitik"'], ['id="iklan"', 'id="pengguna"', 'id="pengaturan"']],
            'ads manager' => ['Ads Manager', ['id="iklan"', 'id="analitik"'], ['id="menulis"', 'id="review"', 'id="pengguna"']],
            'super admin' => ['Super Admin', ['id="menulis"', 'id="review"', 'id="iklan"', 'id="pengguna"', 'id="pengaturan"'], []],
        ];
    }

    #[DataProvider('roles')]
    public function test_guide_shows_only_sections_for_the_users_role(string $role, array $visible, array $hidden): void
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($user)->get(route('admin.panduan'))->assertOk()
            ->assertSee('Panduan Pengguna')
            ->assertSee('id="masuk"', false)
            ->assertSee('id="masalah"', false);

        foreach ($visible as $marker) {
            $response->assertSee($marker, false);
        }
        foreach ($hidden as $marker) {
            $response->assertDontSee($marker, false);
        }
    }

    public function test_every_screenshot_referenced_by_the_guide_exists(): void
    {
        preg_match_all('/<x-panduan\.shot src="([a-z0-9-]+)"/', file_get_contents(resource_path('views/admin/panduan.blade.php')), $m);

        $this->assertNotEmpty($m[1]);
        foreach (array_unique($m[1]) as $name) {
            $this->assertFileExists(public_path("images/panduan/{$name}.webp"));
        }
    }

    public function test_guests_cannot_open_the_guide(): void
    {
        $this->get('/admin/panduan')->assertNotFound();
    }
}
