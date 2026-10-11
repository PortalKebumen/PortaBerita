<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\SystemAlertNotification;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;

class SystemNotifier
{
    /** Kirim peringatan ke pengguna yang punya izin tertentu, dengan jeda agar tidak spam. */
    public static function alert(
        string $key,
        string $title,
        string $message,
        string $permission,
        ?string $url = null,
        int $cooldownHours = 6,
    ): void {
        try {
            $recent = DatabaseNotification::query()
                ->where('type', SystemAlertNotification::class)
                ->where('data->key', $key)
                ->where('created_at', '>=', now()->subHours($cooldownHours))
                ->exists();

            if ($recent) {
                return;
            }

            $recipients = User::permission($permission)->get()->filter(fn ($u) => filled($u->email));

            if ($recipients->isEmpty()) {
                return;
            }

            Notification::send($recipients, new SystemAlertNotification($key, $title, $message, $url));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}