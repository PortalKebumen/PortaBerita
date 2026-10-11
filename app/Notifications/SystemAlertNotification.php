<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SystemAlertNotification extends Notification
{
    public function __construct(
        public string $key,
        public string $title,
        public string $message,
        public ?string $url = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->error()
            ->subject('[' . config('app.name') . '] ' . $this->title)
            ->greeting('Halo, ' . $notifiable->name . '!')
            ->line($this->message);

        if ($this->url) {
            $mail->action('Buka', $this->url);
        }

        return $mail->salutation('Salam, ' . config('app.name'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'event' => 'system_alert',
            'kind' => 'error',
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->url,
            'key' => $this->key,
        ];
    }
}