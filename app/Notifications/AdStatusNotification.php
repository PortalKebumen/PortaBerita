<?php

namespace App\Notifications;

use App\Models\Advertisement;
use Carbon\Carbon;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdStatusNotification extends Notification
{
    /** @param 'expiring'|'expired' $state */
    public function __construct(public Advertisement $ad, public string $state) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    private function text(): array
    {
        $name = $this->ad->advertiser_name;
        $date = $this->ad->end_date->translatedFormat('d M Y');

        if ($this->state === 'expired') {
            return [
                'kind' => 'error',
                'title' => 'Iklan sudah berakhir',
                'message' => "Iklan {$name} berakhir pada {$date}. Perpanjang atau nonaktifkan iklan ini.",
            ];
        }

        $days = (int) Carbon::today()->diffInDays($this->ad->end_date->copy()->startOfDay(), false);
        $left = $days <= 0 ? 'hari ini' : "{$days} hari lagi";

        return [
            'kind' => 'warning',
            'title' => 'Iklan akan berakhir',
            'message' => "Iklan {$name} berakhir {$left} ({$date}). Hubungi pengiklan untuk perpanjangan.",
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $t = $this->text();

        return (new MailMessage)
            ->subject('[' . config('app.name') . '] ' . $t['title'] . ': ' . $this->ad->advertiser_name)
            ->greeting('Halo, ' . $notifiable->name . '!')
            ->line($t['message'])
            ->line('Penempatan: ' . $this->ad->placement)
            ->action('Kelola Iklan', route('admin.iklan.index'))
            ->salutation('Salam, ' . config('app.name'));
    }

    public function toArray(object $notifiable): array
    {
        $t = $this->text();

        return [
            'event' => 'ad_' . $this->state,
            'kind' => $t['kind'],
            'title' => $t['title'],
            'message' => $t['message'],
            'url' => route('admin.iklan.index'),
            'ad_id' => $this->ad->id,
            'end_date' => $this->ad->end_date->toDateString(),
            'state' => $this->state,
        ];
    }
}