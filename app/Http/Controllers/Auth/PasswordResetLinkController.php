<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->session()->forget('password_retry_at');
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Akun nonaktif tidak dikirimi tautan reset, tetapi jawabannya tetap sama (lihat di bawah)
        $inactive = User::where('email', $request->input('email'))->where('is_active', false)->exists();
        $status = $inactive ? Password::INVALID_USER : Password::sendResetLink(
            $request->only('email')
        );

        // Jawaban & jeda kirim ulang dibuat sama untuk email terdaftar maupun tidak, sehingga form ini
        // tidak bisa dipakai menebak email mana yang punya akun (account enumeration).
        $config = config('auth.passwords.'.config('auth.defaults.passwords'));
        $retryAt = now()->addSeconds($config['throttle'])->timestamp;

        if (in_array($status, [Password::RESET_LINK_SENT, Password::RESET_THROTTLED], true)) {
            $user = Password::broker()->getUser($request->only('email'));
            $createdAt = $user ? DB::connection($config['connection'] ?? null)
                ->table($config['table'])
                ->where('email', $user->getEmailForPasswordReset())
                ->value('created_at') : null;

            if ($createdAt !== null) {
                $retryAt = Carbon::parse($createdAt)->addSeconds($config['throttle'])->timestamp;
            }
        }

        $request->session()->put('password_retry_at', $retryAt);

        return back()
            ->withInput($request->only('email'))
            ->with('status', 'Jika email tersebut terdaftar, tautan untuk mengatur ulang password telah dikirim. Periksa kotak masuk atau folder spam.');
    }
}
