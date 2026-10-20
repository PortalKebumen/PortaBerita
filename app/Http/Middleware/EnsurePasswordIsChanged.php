<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pengguna yang masuk dengan kata sandi sementara (dikirim admin) diarahkan ke Profil Saya
 * sampai kata sandinya diganti. Hanya dipasang di grup route admin.
 */
class EnsurePasswordIsChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->must_change_password && ! $request->routeIs('admin.profil')) {
            return redirect()->route('admin.profil')
                ->with('warning', 'Ganti kata sandi sementara Anda terlebih dahulu.');
        }

        return $next($request);
    }
}
