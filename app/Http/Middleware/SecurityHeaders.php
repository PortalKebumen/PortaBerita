<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Header keamanan HTTP untuk semua halaman: cegah clickjacking (situs dibingkai situs lain),
 * MIME sniffing, kebocoran URL lewat Referer, akses fitur perangkat, dan paksa HTTPS (HSTS).
 * CSP dibatasi ke direktif yang tidak mengganggu skrip editor/Livewire.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $headers = $response->headers;

        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'SAMEORIGIN');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=()');
        $headers->set('Content-Security-Policy', "frame-ancestors 'self'; base-uri 'self'; object-src 'none'; form-action 'self'");

        // HSTS hanya bila sudah HTTPS, agar tidak mengunci situs yang belum punya sertifikat
        if ($request->isSecure() && ! app()->isLocal()) {
            $headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Server testing/staging tidak boleh masuk hasil pencarian (SEO_INDEXING=false)
        if (! config('system.indexing')) {
            $headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        // Jangan umumkan versi PHP (header ini dikirim PHP sendiri, bukan oleh Laravel)
        $headers->remove('X-Powered-By');
        if (! headers_sent()) {
            header_remove('X-Powered-By');
        }

        return $response;
    }
}
