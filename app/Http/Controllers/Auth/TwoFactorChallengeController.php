<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

/**
 * Langkah kedua login: setelah email & kata sandi benar, pengguna yang sudah mengaktifkan 2FA
 * memasukkan kode 6 angka dari aplikasi authenticator (atau satu kode cadangan).
 * Data login sementara di sesi berlaku 5 menit.
 */
class TwoFactorChallengeController extends Controller
{
    public const SESSION_KEY = 'login.two_factor';

    private const MAX_ATTEMPTS = 5;

    public function create(Request $request): View|RedirectResponse
    {
        return $this->pendingUser($request) ? view('auth.two-factor-challenge') : redirect()->route('login');
    }

    public function store(Request $request, TwoFactor $twoFactor): RedirectResponse
    {
        $user = $this->pendingUser($request);
        if (! $user) {
            return redirect()->route('login')->withErrors(['email' => 'Sesi login berakhir. Silakan masuk lagi.']);
        }

        $request->validate(['code' => ['required', 'string', 'max:20']]);

        $throttleKey = '2fa-challenge:' . $user->id;
        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $request->session()->forget(self::SESSION_KEY);

            return redirect()->route('login')->withErrors([
                'email' => 'Terlalu banyak kode salah. Silakan masuk lagi dalam ' . ceil(RateLimiter::availableIn($throttleKey) / 60) . ' menit.',
            ]);
        }

        $code = (string) $request->input('code');
        $valid = $twoFactor->verify($user->two_factor_secret, $code, $user->id)
            || $twoFactor->useRecoveryCode($user, $code);

        if (! $valid) {
            RateLimiter::hit($throttleKey, 600);

            return back()->withErrors(['code' => 'Kode tidak cocok. Periksa kode terbaru di aplikasi authenticator.']);
        }

        RateLimiter::clear($throttleKey);
        $remember = (bool) $request->session()->get(self::SESSION_KEY . '.remember');
        $request->session()->forget(self::SESSION_KEY);

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect()->intended(route('admin.dashboard', absolute: false));
    }

    private function pendingUser(Request $request): ?User
    {
        $pending = $request->session()->get(self::SESSION_KEY);
        if (! $pending || now()->timestamp > ($pending['expires_at'] ?? 0)) {
            return null;
        }

        $user = User::find($pending['user_id'] ?? null);

        return $user && $user->is_active && $user->hasTwoFactorEnabled() ? $user : null;
    }
}
