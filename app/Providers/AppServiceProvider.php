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
}