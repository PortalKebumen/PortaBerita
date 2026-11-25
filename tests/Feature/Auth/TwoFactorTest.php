<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\ProtectAgainstSpam;
use App\Livewire\Admin\TwoFactorManager;
use App\Livewire\Admin\UserManager;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ProtectAgainstSpam::class);
        $this->seed(RolePermissionSeeder::class);
    }

    private function user(string $role = 'Penulis', bool $twoFactor = true): User
    {
        $factory = User::factory();
        $user = ($twoFactor ? $factory : $factory->withoutTwoFactor())->create(['password' => 'password']);
        $user->assignRole($role);

        return $user;
    }

    private function login(User $user): void
    {
        $this->post(route('login'), ['email' => $user->email, 'password' => 'password']);
    }

    public function test_account_without_two_factor_is_forced_to_security_page_and_can_enable_it(): void
    {
        $user = $this->user('Editor', twoFactor: false);

        $this->actingAs($user)->get(route('admin.dashboard'))->assertRedirectToRoute('admin.keamanan');
        $this->get(route('admin.artikel.index'))->assertRedirectToRoute('admin.keamanan');
        $this->get(route('admin.profil'))->assertOk(); // profil tetap bisa (ganti kata sandi sementara)

        $component = Livewire::actingAs($user)->test(TwoFactorManager::class)->assertSee('Aktifkan verifikasi dua langkah');
        $secret = session('two_factor.pending_secret');
        $this->assertNotEmpty($secret);

        $component->set('code', '000000')->call('confirm')->assertHasErrors('code');
        $this->assertFalse($user->fresh()->hasTwoFactorEnabled());

        $component->set('code', $this->twoFactorCode($secret))->call('confirm')->assertHasNoErrors();
        $this->assertCount(8, $component->get('recoveryCodes'));
        $this->assertTrue($user->fresh()->hasTwoFactorEnabled());

        $this->actingAs($user->fresh())->get(route('admin.dashboard'))->assertOk();
    }

    public function test_login_requires_valid_code_and_rejects_reused_code(): void
    {
        $user = $this->user();

        $this->login($user);
        $this->assertGuest();
        $this->get(route('two-factor.challenge'))->assertOk()->assertSee('Verifikasi Dua Langkah');

        $this->post(route('two-factor.challenge'), ['code' => '111111'])->assertSessionHasErrors('code');
        $this->assertGuest();

        $code = $this->twoFactorCode();
        $this->post(route('two-factor.challenge'), ['code' => $code])->assertRedirectToRoute('admin.dashboard');
        $this->assertAuthenticatedAs($user);

        // Kode yang sama tidak bisa dipakai login lagi
        $this->post(route('logout'));
        $this->login($user);
        $this->post(route('two-factor.challenge'), ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_recovery_code_works_once(): void
    {
        $user = $this->user();

        $this->login($user);
        $this->post(route('two-factor.challenge'), ['code' => 'aaaaa-11111'])->assertRedirectToRoute('admin.dashboard');
        $this->assertSame(['BBBBB-22222'], $user->fresh()->two_factor_recovery_codes);

        $this->post(route('logout'));
        $this->login($user);
        $this->post(route('two-factor.challenge'), ['code' => 'AAAAA-11111'])->assertSessionHasErrors('code');
    }

    public function test_challenge_without_pending_login_or_after_expiry_goes_back_to_login(): void
    {
        $this->get(route('two-factor.challenge'))->assertRedirectToRoute('login');

        $user = $this->user();
        $this->login($user);
        $this->travel(6)->minutes();
        $this->post(route('two-factor.challenge'), ['code' => $this->twoFactorCode()])->assertRedirectToRoute('login');
        $this->assertGuest();
    }

    public function test_super_admin_can_reset_two_factor_and_others_cannot(): void
    {
        $writer = $this->user();
        $admin = $this->user('Super Admin');

        Livewire::actingAs($admin)->test(UserManager::class)->call('resetTwoFactor', $writer->id);
        $this->assertFalse($writer->fresh()->hasTwoFactorEnabled());

        $editor = $this->user('Editor');
        $editor->givePermissionTo('users.view', 'users.update');
        Livewire::actingAs($editor)->test(UserManager::class)->call('resetTwoFactor', $admin->id)->assertForbidden();
        $this->assertTrue($admin->fresh()->hasTwoFactorEnabled());
    }

    public function test_emergency_command_resets_two_factor(): void
    {
        $admin = $this->user('Super Admin');

        $this->artisan('user:reset-2fa', ['email' => $admin->email])->assertSuccessful();
        $this->assertFalse($admin->fresh()->hasTwoFactorEnabled());
        $this->artisan('user:reset-2fa', ['email' => 'tidak-ada@contoh.test'])->assertFailed();
    }
}
