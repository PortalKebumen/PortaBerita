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
            'Terlalu banyak percobaan login yang gagal.',
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

    public function test_fifth_failed_login_shows_countdown_and_login_recovers_after_wait(): void
    {
        $this->freezeTime();
        User::factory()->create(['email' => 'countdown@example.test', 'password' => 'password']);
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $response = $this->from(route('login'))->post(route('login'), [
                'email' => 'countdown@example.test', 'password' => 'wrong',
            ]);
        }
        $response->assertSessionHas('login_retry_at', now()->timestamp + 60);
        $this->get(route('login'))->assertSee('data-auth-countdown="60"', false);
        $this->travel(20)->seconds();
        $this->get(route('login'))->assertSee('data-auth-countdown="40"', false);
        $this->travel(41)->seconds();
        $this->post(route('login'), ['email' => 'countdown@example.test', 'password' => 'password'])
            ->assertRedirectToRoute('admin.dashboard');
        $this->assertAuthenticated();
    }

    public function test_reset_link_countdown_uses_remaining_time_and_allows_resending_after_wait(): void
    {
        $this->freezeTime();
        Mail::fake();
        User::factory()->create(['email' => 'reset-countdown@example.test']);
        $data = ['email' => 'reset-countdown@example.test'];
        $this->post(route('password.email'), $data)
            ->assertSessionHas('password_retry_at', now()->timestamp + 60);
        $this->get(route('password.request'))->assertSee('data-auth-countdown="60"', false);
        $this->travel(20)->seconds();
        $this->post(route('password.email'), $data)
            ->assertSessionHasErrors(['email' => trans(Password::RESET_THROTTLED)])
            ->assertSessionHas('password_retry_at', now()->timestamp + 40);
        $this->get(route('password.request'))->assertSee('data-auth-countdown="40"', false);
        Mail::assertSentCount(1);
        $this->travel(41)->seconds();
        $this->post(route('password.email'), $data)
            ->assertSessionHas('status', trans(Password::RESET_LINK_SENT));
        Mail::assertSentCount(2);
    }

    public function test_unknown_reset_email_shows_red_warning_without_countdown(): void
    {
        Mail::fake();
        $this->from(route('password.request'))->post(route('password.email'), ['email' => 'missing@example.test'])
            ->assertSessionHasErrors(['email' => trans(Password::INVALID_USER)]);
        $this->view('auth.forgot-password', ['errors' => session('errors')])
            ->assertSee(trans(Password::INVALID_USER))
            ->assertSee('style="color: #dc2626;" role="alert"', false)
            ->assertDontSee('data-auth-countdown=');
        Mail::assertNothingSent();
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
