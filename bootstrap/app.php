<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\ProtectAgainstSpam;
use App\Http\Middleware\SecurityHeaders;
use App\Jobs\GenerateSitemap;
use App\Jobs\NotifyAdStatus;
use App\Jobs\PublishScheduledArticles;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->job(PublishScheduledArticles::class, connection: 'sync')
            ->everyMinute()
            ->withoutOverlapping();

        // Sitemap XML diperbarui tiap 30 menit
        $schedule->job(GenerateSitemap::class, connection: 'sync')
            ->everyThirtyMinutes()
            ->withoutOverlapping();

        // Notifikasi iklan akan berakhir / sudah berakhir (tiap pagi)
        $schedule->job(NotifyAdStatus::class, connection: 'sync')
            ->dailyAt('08:00')
            ->withoutOverlapping();

        // Database lokasi IP (DB-IP City Lite) dirilis bulanan
        $schedule->command('geoip:update')->monthlyOn(5, '03:00')->withoutOverlapping();

        // Bersihkan notifikasi yang sudah dibaca lebih dari 60 hari
        $schedule->call(fn () => DatabaseNotification::query()
            ->whereNotNull('read_at')
            ->where('read_at', '<', now()->subDays(60))
            ->delete())
            ->name('prune-read-notifications')
            ->daily();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        // Tamu yang membuka halaman admin mendapat 404, bukan dialihkan ke login: pengalihan akan
        // membocorkan alamat login rahasia (config auth.login_path). Pengguna yang sudah masuk lalu
        // membuka halaman login langsung diarahkan ke dashboard.
        $middleware->redirectGuestsTo(fn () => abort(404));
        $middleware->redirectUsersTo('/admin');

        // Header keamanan HTTP di semua halaman web
        $middleware->web(append: [SecurityHeaders::class]);

        // Akun nonaktif langsung dikeluarkan walau sesinya masih berjalan
        $middleware->web(append: [EnsureUserIsActive::class]);

        // Anti-spam tanpa CAPTCHA (honeypot + jebakan waktu) untuk form publik; pasangan <x-honeypot />
        $middleware->alias(['antispam' => ProtectAgainstSpam::class]);

        // Di balik reverse proxy / Cloudflare: isi TRUSTED_PROXIES agar IP pembaca asli terbaca (rate limit,
        // statistik lokasi, activity log). "cloudflare" = rentang IP resmi Cloudflare (bootstrap/cloudflare-ips.php),
        // atau daftar IP proxy dipisah koma, atau *. Kosong = tidak memercayai proxy mana pun.
        if ($proxies = env('TRUSTED_PROXIES')) {
            $middleware->trustProxies(at: match ($proxies) {
                '*' => '*',
                'cloudflare' => require __DIR__.'/cloudflare-ips.php',
                default => array_map('trim', explode(',', $proxies)),
            });
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
