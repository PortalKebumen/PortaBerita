<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Anti-spam tanpa CAPTCHA untuk form publik (login, lupa/reset password). Pasangan dari <x-honeypot />:
 *  - honeypot: kolom tersembunyi yang hanya diisi bot pengisi form otomatis;
 *  - jebakan waktu: form yang dikirim < MIN_SECONDS setelah halaman dibuka dianggap otomatis.
 * Permintaan yang tertangkap dikembalikan ke form dengan pesan umum dan dicatat di log.
 */
class ProtectAgainstSpam
{
    public const HONEYPOT_FIELD = 'website';

    public const TIME_FIELD = '_hp_t';

    public const MIN_SECONDS = 2;

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('POST')) {
            return $next($request);
        }

        $reason = match (true) {
            filled($request->input(self::HONEYPOT_FIELD)) => 'honeypot terisi',
            ($elapsed = $this->elapsedSeconds($request)) === null => 'token waktu tidak valid',
            $elapsed < self::MIN_SECONDS => "dikirim {$elapsed} detik setelah dibuka",
            default => null,
        };

        if ($reason === null) {
            return $next($request);
        }

        Log::warning('Form publik ditolak oleh anti-spam', [
            'path' => $request->path(),
            'ip' => $request->ip(),
            'reason' => $reason,
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 200),
        ]);

        return back()
            ->withInput($request->except(['password', 'password_confirmation', self::HONEYPOT_FIELD, self::TIME_FIELD]))
            ->withErrors(['email' => 'Permintaan tidak dapat diproses karena terdeteksi seperti pengiriman otomatis. Tunggu beberapa detik, lalu coba lagi.']);
    }

    private function elapsedSeconds(Request $request): ?int
    {
        try {
            $openedAt = decrypt((string) $request->input(self::TIME_FIELD));
        } catch (DecryptException) {
            return null;
        }

        return is_int($openedAt) ? now()->getTimestamp() - $openedAt : null;
    }
}
