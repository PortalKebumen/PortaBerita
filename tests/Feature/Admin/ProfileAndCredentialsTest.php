<?php

namespace Tests\Feature\Admin;

use App\Http\Middleware\ProtectAgainstSpam;
use App\Livewire\Admin\ProfileManager;
use App\Livewire\Admin\SiteSettingsManager;
use App\Livewire\Admin\UserManager;
use App\Mail\AccountCredentialsMail;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Symfony\Component\Mailer\Exception\TransportException;
use Tests\TestCase;

class ProfileAndCredentialsTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function userWithRole(string $role, array $attributes = []): User
    {
        $user = User::factory()->create($attributes + ['password' => 'rahasia123']);
        $user->assignRole($role);

        return $user;
    }

    // ---------- Profil Saya ----------

    public function test_every_role_can_open_own_profile_page(): void
    {
        foreach (['Super Admin', 'Editor', 'Penulis', 'Ads Manager'] as $role) {
            $this->actingAs($this->userWithRole($role))
                ->get(route('admin.profil'))
                ->assertOk()
                ->assertSee('Informasi Profil')
                ->assertSee('Ganti Kata Sandi');
        }
    }

    public function test_user_can_update_own_name_email_and_bio(): void
    {
        $user = $this->userWithRole('Penulis');

        Livewire::actingAs($user)
            ->test(ProfileManager::class)
            ->set('name', 'Rina Kartika')
            ->set('email', 'Rina@PortalKebumen.com')
            ->set('bio', 'Jurnalis bidang pendidikan.')
            ->call('saveProfile')
            ->assertHasNoErrors();

        $user->refresh();
        $this->assertSame('Rina Kartika', $user->name);
        $this->assertSame('rina@portalkebumen.com', $user->email);
        $this->assertSame('Jurnalis bidang pendidikan.', $user->bio);
    }

    public function test_profile_email_must_be_unique(): void
    {
        $this->userWithRole('Editor', ['email' => 'dipakai@portalkebumen.com']);
        $user = $this->userWithRole('Penulis');

        Livewire::actingAs($user)
            ->test(ProfileManager::class)
            ->set('email', 'dipakai@portalkebumen.com')
            ->call('saveProfile')
            ->assertHasErrors(['email' => 'unique']);
    }

    public function test_password_change_requires_correct_current_password(): void
    {
        $user = $this->userWithRole('Penulis');

        Livewire::actingAs($user)
            ->test(ProfileManager::class)
            ->set('currentPassword', 'salah123')
            ->set('newPassword', 'BaruSekali9')
            ->set('newPassword_confirmation', 'BaruSekali9')
            ->call('savePassword')
            ->assertHasErrors(['currentPassword']);

        Livewire::actingAs($user)
            ->test(ProfileManager::class)
            ->set('currentPassword', 'rahasia123')
            ->set('newPassword', 'BaruSekali9')
            ->set('newPassword_confirmation', 'BaruSekali9')
            ->call('savePassword')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('BaruSekali9', $user->fresh()->password));
    }

    // ---------- Kirim info login ----------

    public function test_admin_sends_credentials_with_new_temporary_password(): void
    {
        Mail::fake();
        $admin = $this->userWithRole('Super Admin');
        $target = $this->userWithRole('Penulis', ['email' => 'penulis@portalkebumen.com']);
        $oldHash = $target->password;

        Livewire::actingAs($admin)
            ->test(UserManager::class)
            ->call('confirmSendCredentials', $target->id)
            ->assertSet('showCredentialsModal', true)
            ->call('sendCredentialsNow');

        $sentPassword = null;
        Mail::assertSent(AccountCredentialsMail::class, function (AccountCredentialsMail $mail) use ($target, &$sentPassword) {
            $sentPassword = $mail->plainPassword;

            return $mail->hasTo('penulis@portalkebumen.com') && $mail->user->is($target);
        });

        $target->refresh();
        $this->assertNotSame($oldHash, $target->password);
        $this->assertTrue(Hash::check($sentPassword, $target->password));
        $this->assertTrue($target->must_change_password);
    }

    public function test_password_is_not_changed_when_email_fails(): void
    {
        Log::spy();
        $admin = $this->userWithRole('Super Admin');
        $target = $this->userWithRole('Penulis');
        $oldHash = $target->password;

        Mail::shouldReceive('to')->andThrow(new TransportException('Connection could not be established with host "mail.example.com"'));

        Livewire::actingAs($admin)
            ->test(UserManager::class)
            ->call('confirmSendCredentials', $target->id)
            ->call('sendCredentialsNow')
            ->assertDispatched('flash-message', type: 'error');

        $this->assertSame($oldHash, $target->fresh()->password);
        $this->assertFalse($target->fresh()->must_change_password);
    }

    public function test_new_user_can_be_created_without_password_and_receives_credentials(): void
    {
        Mail::fake();
        $admin = $this->userWithRole('Super Admin');

        Livewire::actingAs($admin)
            ->test(UserManager::class)
            ->call('openCreate')
            ->set('name', 'Penulis Baru')
            ->set('email', 'baru@portalkebumen.com')
            ->set('role', 'Penulis')
            ->set('password', '')
            ->call('save')
            ->assertHasNoErrors();

        Mail::assertSent(AccountCredentialsMail::class, fn ($mail) => $mail->hasTo('baru@portalkebumen.com') && $mail->isNewAccount);
        $this->assertTrue(User::where('email', 'baru@portalkebumen.com')->first()->must_change_password);
    }

    public function test_user_with_temporary_password_is_forced_to_profile_until_changed(): void
    {
        $user = $this->userWithRole('Editor', ['must_change_password' => true]);

        $this->actingAs($user)->get(route('admin.dashboard'))->assertRedirectToRoute('admin.profil');
        $this->actingAs($user)->get(route('admin.profil'))->assertOk()->assertSee('kata sandi sementara');

        Livewire::actingAs($user)
            ->test(ProfileManager::class)
            ->set('currentPassword', 'rahasia123')
            ->set('newPassword', 'SandiBaru2026')
            ->set('newPassword_confirmation', 'SandiBaru2026')
            ->call('savePassword')
            ->assertHasNoErrors();

        $this->assertFalse($user->fresh()->must_change_password);
        $this->actingAs($user->fresh())->get(route('admin.dashboard'))->assertOk();
    }

    public function test_user_without_permission_cannot_send_credentials(): void
    {
        $writer = $this->userWithRole('Penulis');
        $target = $this->userWithRole('Editor');

        Livewire::actingAs($writer)
            ->test(UserManager::class)
            ->call('confirmSendCredentials', $target->id)
            ->assertForbidden();
    }

    // ---------- Lupa kata sandi & uji email ----------

    public function test_forgot_password_shows_clear_error_when_mail_server_fails(): void
    {
        $this->withoutMiddleware(ProtectAgainstSpam::class);
        $this->userWithRole('Penulis', ['email' => 'ada@portalkebumen.com']);

        Mail::shouldReceive('to')->andThrow(new TransportException('Expected response code "250" but got code "550"'));

        $this->post(route('password.email'), ['email' => 'ada@portalkebumen.com'])
            ->assertSessionHasErrors('email');
    }

    public function test_settings_test_mail_reports_success(): void
    {
        Mail::fake();
        $admin = $this->userWithRole('Super Admin');
        config(['mail.default' => 'smtp']);

        Livewire::actingAs($admin)
            ->test(SiteSettingsManager::class)
            ->set('testMailTo', 'uji@portalkebumen.com')
            ->call('sendTestMail')
            ->assertSet('testMailResult.ok', true);
    }

    public function test_settings_test_mail_warns_when_mailer_is_log(): void
    {
        $admin = $this->userWithRole('Super Admin');
        config(['mail.default' => 'log']);

        Livewire::actingAs($admin)
            ->test(SiteSettingsManager::class)
            ->set('testMailTo', 'uji@portalkebumen.com')
            ->call('sendTestMail')
            ->assertSet('testMailResult.ok', false)
            ->assertSee('MAIL_MAILER');
    }
}
