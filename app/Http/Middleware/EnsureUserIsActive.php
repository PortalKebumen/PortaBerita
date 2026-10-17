<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pengguna yang dinonaktifkan saat sedang login (atau masuk lewat cookie "ingat saya") langsung
 * dikeluarkan pada permintaan berikutnya, termasuk permintaan Livewire.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('web')->user();

        if ($user && ! $user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Permintaan Livewire/AJAX: 419 membuat Livewire meminta muat ulang, lalu GET berikutnya ke halaman login
            if ($request->hasHeader('X-Livewire') || $request->expectsJson()) {
                abort(419, trans('auth.inactive'));
            }

            return redirect()->route('login')->withErrors(['email' => trans('auth.inactive')]);
        }

        return $next($request);
    }
}
