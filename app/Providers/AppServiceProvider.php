<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Failed;
use App\Listeners\LogSuccessfulLogin;
use App\Listeners\LogSuccessfulLogout;
use App\Listeners\LogFailedLogin;
use Illuminate\Support\Facades\Event;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Satu pembaca database lokasi IP per request
        $this->app->singleton(\App\Services\Analytics\GeoLocator::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(Login::class, LogSuccessfulLogin::class);
        Event::listen(Logout::class, LogSuccessfulLogout::class);
        Event::listen(Failed::class, LogFailedLogin::class);

        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            if (! $user->hasRole('Super Admin')) {
                return null;
            }

            // Bypass hanya untuk permission yang benar-benar terdaftar. Ability
            // sembarang/typo tidak lolos diam-diam, dan policy berbasis model
            // (status artikel, kepemilikan) tetap berlaku untuk Super Admin.
            return app(\Spatie\Permission\PermissionRegistrar::class)
                ->getPermissions(['name' => $ability])
                ->isNotEmpty() ? true : null;
        });

        $this->configureRateLimiting();

        // Menu Laporan Analitik tampil bila punya salah satu tab: Konten (analytics.view-content) atau Iklan (ads.view-reports)
        \Illuminate\Support\Facades\Gate::define('view-analytics', fn ($user) => $user->canAny(['analytics.view-content', 'ads.view-reports']));

        // PK-39: suntik alamat IP ke SEMUA activity log (otomatis maupun manual),
        // karena spatie/laravel-activitylog v5.1.0 tidak punya hook tapActivity()
        // untuk logging otomatis lewat trait LogsModelActivity.
        Activity::creating(function (Activity $activity) {
            if (! $activity->properties->has('ip')) {
                $activity->properties = $activity->properties->merge([
                    'ip' => request()->ip(),
                ]);
            }
        });
    }

    /**
     * Batas permintaan per IP untuk titik masuk publik. Sengaja longgar: banyak pembaca seluler
     * berbagi satu IP publik (CGNAT), jadi yang tertahan hanya pola otomatis (skrip, scraper, brute force).
     * Melewati batas = halaman 429 "Terlalu banyak permintaan" (resources/views/errors/429.blade.php).
     */
    private function configureRateLimiting(): void
    {
        $byIp = fn (Request $request) => $request->ip();

        // Halaman publik (beranda, artikel, kategori, profil, halaman statis)
        RateLimiter::for('public', fn (Request $request) => Limit::perMinute(240)->by($byIp($request)));

        // Pencarian lebih berat (kueri LIKE ke banyak artikel)
        RateLimiter::for('search', fn (Request $request) => [
            Limit::perMinute(30)->by('search:' . $byIp($request)),
            Limit::perHour(600)->by('search-hour:' . $byIp($request)),
        ]);

        // Login: selain batas 5x gagal per email+IP (LoginRequest), satu IP dibatasi lintas email
        // agar tidak bisa mencoba ribuan akun (credential stuffing)
        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(20)->by('login:' . $byIp($request)),
            Limit::perHour(100)->by('login-hour:' . $byIp($request)),
        ]);

        // Lupa / reset password: membatasi spam email reset & tebakan token
        RateLimiter::for('password-reset', fn (Request $request) => [
            Limit::perMinute(5)->by('pwreset:' . $byIp($request)),
            Limit::perHour(20)->by('pwreset-hour:' . $byIp($request)),
            Limit::perHour(5)->by('pwreset-email:' . Str::lower((string) $request->input('email'))),
        ]);

        // Pelacakan iklan (dipanggil otomatis oleh halaman; klik = pengunjung menekan banner)
        RateLimiter::for('ads-impression', fn (Request $request) => Limit::perMinute(120)->by('adimp:' . $byIp($request)));
        RateLimiter::for('ads-click', fn (Request $request) => Limit::perMinute(20)->by('adclick:' . $byIp($request)));
    }
}