<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\ProtectAgainstSpam;
use App\Livewire\Admin\UserManager;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class InactiveUserTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Honeypot/jebakan waktu form login & reset tidak relevan untuk uji status akun
        $this->withoutMiddleware(ProtectAgainstSpam::class);
    }

    public function test_inactive_user_cannot_login_even_with_correct_password(): void
    {
        User::factory()->create([
            'email' => 'nonaktif@portalkebumen.test',
            'password' => 'password',
            'is_active' => false,
        ]);

        $response = $this->post(route('login'), [
            'email' => 'nonaktif@portalkebumen.test',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors(['email' => trans('auth.inactive')]);
        $this->assertGuest();
    }

    public function test_user_deactivated_during_session_is_logged_out_on_next_request(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $this->actingAs($user);

        $user->forceFill(['is_active' => false])->save();

        $response = $this->get('/admin');

        $response->assertRedirectToRoute('login');
        $response->assertSessionHasErrors(['email' => trans('auth.inactive')]);
        $this->assertGuest();
    }

    public function test_deactivated_user_livewire_request_is_rejected(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->actingAs($user)
            ->getJson('/admin', ['X-Livewire' => 'true'])
            ->assertStatus(419);

        $this->assertGuest();
    }

    public function test_inactive_user_does_not_receive_password_reset_link(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'nonaktif@portalkebumen.test', 'is_active' => false]);

        $this->post(route('password.email'), ['email' => 'nonaktif@portalkebumen.test'])
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('password_reset_tokens', ['email' => $user->email]);
    }

    public function test_admin_cannot_deactivate_own_account(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create(['is_active' => true]);
        $admin->assignRole('Super Admin');

        Livewire::actingAs($admin)
            ->test(UserManager::class)
            ->call('openEdit', $admin->id)
            ->set('isActive', false)
            ->call('save')
            ->assertHasErrors(['isActive']);

        $this->assertTrue($admin->fresh()->is_active);
    }
}
