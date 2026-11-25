<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Verifikasi dua langkah wajib untuk semua akun: pengguna yang belum mengaktifkannya diarahkan ke
 * halaman Keamanan Akun sampai selesai. Profil Saya tetap bisa dibuka (untuk mengganti kata sandi
 * sementara lebih dulu). Hanya dipasang di grup route admin.
 */
class EnsureTwoFactorEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->hasTwoFactorEnabled() && ! $request->routeIs('admin.keamanan', 'admin.profil')) {
            return redirect()->route('admin.keamanan')
                ->with('warning', 'Aktifkan verifikasi dua langkah terlebih dahulu untuk melanjutkan.');
        }

        return $next($request);
    }
}
