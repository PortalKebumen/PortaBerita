<?php

namespace Tests\Feature\Auth;

use App\Mail\ResetPasswordMail;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class LoginEndToEndTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_login_page_renders_with_forgot_password_link(): void
    {
        $response = $this->get(route('login'));

        $response
            ->assertOk()
            ->assertSee('Masuk ke Admin')
            ->assertSee(route('password.request', absolute: false));
    }

    public function test_user_can_login_with_valid_email_and_password(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@portalkebumen.test',
            'password' => 'password',
        ]);

        $response = $this->post(route('login'), [
            'email' => 'admin@portalkebumen.test',
            'password' => 'password',
        ]);

        $response->assertRedirectToRoute('admin.dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_login_with_invalid_password(): void
    {
        User::factory()->create([
            'email' => 'admin@portalkebumen.test',
            'password' => 'password',
        ]);

        $response = $this->post(route('login'), [
            'email' => 'admin@portalkebumen.test',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors(['email' => trans('auth.failed')]);
        $this->assertGuest();
    }

    public function test_repeated_failed_login_attempts_are_rate_limited(): void
    {
        User::factory()->create([
            'email' => 'admin@portalkebumen.test',
            'password' => 'password',
        ]);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('login'), [
                'email' => 'admin@portalkebumen.test',
                'password' => 'wrong-password',
            ]);
        }

        $response = $this->post(route('login'), [
            'email' => 'admin@portalkebumen.test',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Too many login attempts. Please try again in',
            session('errors')->first('email')
        );
        $this->assertGuest();
    }

    public function test_forgot_password_page_renders(): void
    {
        $response = $this->get(route('password.request'));

        $response
            ->assertOk()
            ->assertSee('Lupa kata sandi')
            ->assertSee(route('password.email', absolute: false));
    }

    public function test_forgot_password_sends_reset_password_mail(): void
    {
        Mail::fake();
        $user = User::factory()->create([
            'email' => 'admin@portalkebumen.test',
        ]);

        $response = $this->post(route('password.email'), [
            'email' => 'admin@portalkebumen.test',
        ]);

        $response->assertSessionHas('status', trans(Password::RESET_LINK_SENT));
        Mail::assertSent(ResetPasswordMail::class, function (ResetPasswordMail $mail) use ($user): bool {
            return $mail->hasTo('admin@portalkebumen.test')
                && $mail->user->is($user);
        });
    }

    public function test_user_can_reset_password_with_valid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@portalkebumen.test',
            'password' => 'old-password',
        ]);
        $token = Password::createToken($user);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'admin@portalkebumen.test',
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ]);

        $response->assertRedirectToRoute('login');
        $this->assertTrue(Hash::check('new-secure-password', $user->refresh()->password));
    }

    public function test_user_cannot_reset_password_with_invalid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@portalkebumen.test',
            'password' => 'old-password',
        ]);

        $response = $this->post(route('password.update'), [
            'token' => 'invalid-token',
            'email' => 'admin@portalkebumen.test',
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ]);

        $response->assertSessionHasErrors(['email' => trans(Password::INVALID_TOKEN)]);
        $this->assertTrue(Hash::check('old-password', $user->refresh()->password));
    }
}
