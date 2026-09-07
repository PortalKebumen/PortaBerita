<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;
use Illuminate\Database\Eloquent\Model;

class LogSuccessfulLogout
{
    public function handle(Logout $event): void
    {
        if (! $event->user instanceof Model) {
            return;
        }

        activity('auth')
            ->causedBy($event->user)
            ->log('Logout');
    }
}