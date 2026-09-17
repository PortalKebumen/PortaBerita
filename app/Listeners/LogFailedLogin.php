<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;

class LogFailedLogin
{
    public function handle(Failed $event): void
    {
        activity('auth')
            ->event('failed')
            ->withProperties([
                'email' => $event->credentials['email'] ?? null,
                'ip' => request()->ip(),
            ])
            ->log('Percobaan login gagal');
    }
}