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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(Login::class, LogSuccessfulLogin::class);
        Event::listen(Logout::class, LogSuccessfulLogout::class);
        Event::listen(Failed::class, LogFailedLogin::class);

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