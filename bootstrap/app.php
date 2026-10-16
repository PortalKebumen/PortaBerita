<?php

use App\Jobs\GenerateSitemap;
use App\Jobs\PublishScheduledArticles;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Jobs\NotifyAdStatus;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Http\Request;

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
        // Header keamanan HTTP di semua halaman web
        $middleware->web(append: [\App\Http\Middleware\SecurityHeaders::class]);

        // Anti-spam tanpa CAPTCHA (honeypot + jebakan waktu) untuk form publik; pasangan <x-honeypot />
        $middleware->alias(['antispam' => \App\Http\Middleware\ProtectAgainstSpam::class]);

        // Di balik reverse proxy / Cloudflare: isi TRUSTED_PROXIES agar IP pembaca asli terbaca (rate limit,
        // statistik lokasi, activity log). "cloudflare" = rentang IP resmi Cloudflare (bootstrap/cloudflare-ips.php),
        // atau daftar IP proxy dipisah koma, atau *. Kosong = tidak memercayai proxy mana pun.
        if ($proxies = env('TRUSTED_PROXIES')) {
            $middleware->trustProxies(at: match ($proxies) {
                '*' => '*',
                'cloudflare' => require __DIR__ . '/cloudflare-ips.php',
                default => array_map('trim', explode(',', $proxies)),
            });
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
