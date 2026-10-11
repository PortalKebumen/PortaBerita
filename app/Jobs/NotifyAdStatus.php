<?php

namespace App\Jobs;

use App\Models\Advertisement;
use App\Models\User;
use App\Notifications\AdStatusNotification;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class NotifyAdStatus implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $recipients = User::permission('ads.view')->get()->filter(fn ($u) => filled($u->email));

        if ($recipients->isEmpty()) {
            return;
        }

        $today = Carbon::today();

        Advertisement::query()
            ->where('status', 'active')
            ->whereDate('end_date', '>=', $today)
            ->whereDate('end_date', '<=', $today->copy()->addDays(7))
            ->get()
            ->each(fn ($ad) => $this->send($ad, 'expiring', $recipients));

        Advertisement::query()
            ->where('status', 'active')
            ->whereDate('end_date', '<', $today)
            ->whereDate('end_date', '>=', $today->copy()->subDays(7))
            ->get()
            ->each(fn ($ad) => $this->send($ad, 'expired', $recipients));
    }

    private function send(Advertisement $ad, string $state, Collection $recipients): void
    {
        $alreadySent = DatabaseNotification::query()
            ->where('type', AdStatusNotification::class)
            ->where('data->ad_id', $ad->id)
            ->where('data->end_date', $ad->end_date->toDateString())
            ->where('data->state', $state)
            ->exists();

        if ($alreadySent) {
            return;
        }

        try {
            Notification::send($recipients, new AdStatusNotification($ad, $state));
        } catch (\Throwable $e) {
            report($e);
        }
    }
}