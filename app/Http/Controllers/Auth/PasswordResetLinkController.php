<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

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

        try {
            $status = $inactive ? Password::INVALID_USER : Password::sendResetLink(
                $request->only('email')
            );
        } catch (TransportExceptionInterface $e) {
            // Server email menolak/tidak terjangkau: catat penyebabnya untuk admin, beri tahu pengguna
            Log::error('Email reset kata sandi gagal dikirim', ['email' => $request->input('email'), 'error' => $e->getMessage()]);

            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'Email belum dapat dikirim karena gangguan pada server email. Coba lagi beberapa saat atau hubungi administrator.']);
        }

        // Jejak untuk admin (storage/logs): layar pengguna sengaja selalu sama, jadi log inilah
        // satu-satunya cara mengetahui apakah email benar-benar dikirim
        Log::info('Permintaan reset kata sandi', [
            'email' => $request->input('email'),
            'hasil' => match (true) {
                $inactive => 'akun nonaktif, email tidak dikirim',
                $status === Password::RESET_LINK_SENT => 'email reset terkirim ke server SMTP',
                $status === Password::RESET_THROTTLED => 'ditahan: permintaan terlalu cepat',
                $status === Password::INVALID_USER => 'email tidak terdaftar, email tidak dikirim',
                default => $status,
            },
        ]);

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
