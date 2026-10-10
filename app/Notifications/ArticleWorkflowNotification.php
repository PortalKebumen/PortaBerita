<?php

namespace App\Notifications;

use App\Models\Article;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ArticleWorkflowNotification extends Notification
{
    public function __construct(
        public Article $article,
        public string $event,
        public ?User $actor = null,
        public ?string $note = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $title = $this->article->title;
        $by = $this->actor?->name ?? 'Sistem';
        $isAuthor = $notifiable->id === $this->article->author_id;
        $url = route('admin.artikel.edit', $this->article);

        $mail = (new MailMessage)
            ->greeting('Halo, ' . $notifiable->name . '!')
            ->salutation('Salam, ' . config('app.name'));

        switch ($this->event) {
            case 'submitted':
            case 'resubmitted':
                $again = $this->event === 'resubmitted';
                $mail->subject('[' . config('app.name') . '] Artikel menunggu review: ' . $title)
                    ->line("{$by} " . ($again ? 'mengirim ulang artikel setelah revisi' : 'mengirim artikel') . " untuk direview:")
                    ->line('"' . $title . '"')
                    ->line('Kategori: ' . ($this->article->category?->name ?? '-'))
                    ->action('Review Artikel', $url);
                break;

            case 'approved':
                $mail->subject('[' . config('app.name') . '] Artikel disetujui: ' . $title);
                if ($isAuthor) {
                    $mail->line("Artikel Anda \"{$title}\" telah disetujui oleh {$by}.")
                        ->line('Artikel akan diterbitkan atau dijadwalkan oleh redaktur.')
                        ->action('Lihat Artikel', $url);
                } else {
                    $mail->line("Artikel \"{$title}\" telah disetujui oleh {$by} dan siap diterbitkan.")
                        ->action('Terbitkan / Jadwalkan', $url);
                }
                break;

            case 'rejected':
                $mail->error()
                    ->subject('[' . config('app.name') . '] Artikel perlu revisi: ' . $title)
                    ->line("Artikel Anda \"{$title}\" dikembalikan oleh {$by} dan perlu direvisi.")
                    ->line('Catatan revisi:')
                    ->line('> ' . ($this->note ?: '-'))
                    ->action('Perbaiki Artikel', $url);
                break;

            case 'published':
                $mail->success()
                    ->subject('[' . config('app.name') . '] Artikel diterbitkan: ' . $title)
                    ->line("Artikel \"{$title}\" telah diterbitkan oleh {$by}.")
                    ->action('Lihat Artikel', $url);
                break;

            case 'published_auto':
                $mail->success()
                    ->subject('[' . config('app.name') . '] Artikel terbit otomatis: ' . $title)
                    ->line("Artikel \"{$title}\" telah diterbitkan otomatis sesuai jadwal.")
                    ->action('Lihat Artikel', $url);
                break;

            case 'scheduled':
                $mail->subject('[' . config('app.name') . '] Artikel dijadwalkan: ' . $title)
                    ->line("Artikel \"{$title}\" dijadwalkan terbit oleh {$by}.")
                    ->line('Waktu terbit: ' . $this->note . ' WIB')
                    ->action('Lihat Artikel', $url);
                break;

            case 'archived':
                $mail->subject('[' . config('app.name') . '] Artikel diarsipkan: ' . $title)
                    ->line("Artikel \"{$title}\" telah diarsipkan oleh {$by}.")
                    ->action('Lihat Artikel', $url);
                break;

            default:
                $mail->subject('[' . config('app.name') . '] Pembaruan artikel: ' . $title)
                    ->line("Ada pembaruan pada artikel \"{$title}\".")
                    ->action('Buka Artikel', $url);
        }

        return $mail;
    }
}