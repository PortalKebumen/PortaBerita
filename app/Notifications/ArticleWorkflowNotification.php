<?php

namespace App\Notifications;

use App\Models\Article;
use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Gate;

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
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $d = $this->describe($notifiable);

        $mail = (new MailMessage)
            ->subject('[' . config('app.name') . '] ' . $d['heading'] . ': ' . $this->article->title)
            ->greeting('Halo, ' . $notifiable->name . '!')
            ->salutation('Salam, ' . config('app.name'));

        if ($d['kind'] === 'error') {
            $mail->error();
        } elseif ($d['kind'] === 'success') {
            $mail->success();
        }

        $mail->line($d['body']);
        foreach ($d['extra'] as $line) {
            $mail->line($line);
        }

        return $mail->action($d['action'], $this->url($notifiable));
    }

    public function toArray(object $notifiable): array
    {
        $d = $this->describe($notifiable);

        return [
            'event' => $this->event,
            'kind' => $d['kind'],
            'title' => $d['heading'],
            'message' => $d['body'],
            'url' => $this->url($notifiable),
            'article_id' => $this->article->id,
        ];
    }

    private function url(object $notifiable): string
    {
        return Gate::forUser($notifiable)->allows('update', $this->article)
            ? route('admin.artikel.edit', $this->article)
            : route('admin.artikel.index');
    }

    private function describe(object $notifiable): array
    {
        $title = $this->article->title;
        $by = $this->actor?->name ?? 'Sistem';
        $isAuthor = $notifiable->id === $this->article->author_id;

        return match ($this->event) {
            'submitted' => [
                'kind' => 'info',
                'heading' => 'Artikel menunggu review',
                'action' => 'Review Artikel',
                'body' => "{$by} mengirim artikel \"{$title}\" untuk direview.",
                'extra' => ['Kategori: ' . ($this->article->category?->name ?? '-')],
            ],
            'resubmitted' => [
                'kind' => 'info',
                'heading' => 'Artikel dikirim ulang',
                'action' => 'Review Artikel',
                'body' => "{$by} mengirim ulang artikel \"{$title}\" setelah revisi.",
                'extra' => ['Kategori: ' . ($this->article->category?->name ?? '-')],
            ],
            'approved' => $isAuthor
                ? [
                    'kind' => 'success',
                    'heading' => 'Artikel disetujui',
                    'action' => 'Lihat Artikel',
                    'body' => "Artikel Anda \"{$title}\" telah disetujui oleh {$by}.",
                    'extra' => ['Artikel akan diterbitkan atau dijadwalkan oleh redaktur.'],
                ]
                : [
                    'kind' => 'info',
                    'heading' => 'Artikel siap diterbitkan',
                    'action' => 'Terbitkan / Jadwalkan',
                    'body' => "Artikel \"{$title}\" telah disetujui oleh {$by} dan siap diterbitkan.",
                    'extra' => [],
                ],
            'rejected' => [
                'kind' => 'error',
                'heading' => 'Artikel perlu revisi',
                'action' => 'Perbaiki Artikel',
                'body' => "Artikel Anda \"{$title}\" dikembalikan oleh {$by} dan perlu direvisi.",
                'extra' => ['Catatan revisi:', '> ' . ($this->note ?: '-')],
            ],
            'published' => [
                'kind' => 'success',
                'heading' => 'Artikel diterbitkan',
                'action' => 'Lihat Artikel',
                'body' => "Artikel \"{$title}\" telah diterbitkan oleh {$by}.",
                'extra' => [],
            ],
            'published_auto' => [
                'kind' => 'success',
                'heading' => 'Artikel terbit otomatis',
                'action' => 'Lihat Artikel',
                'body' => "Artikel \"{$title}\" telah diterbitkan otomatis sesuai jadwal.",
                'extra' => [],
            ],
            'scheduled' => [
                'kind' => 'info',
                'heading' => 'Artikel dijadwalkan',
                'action' => 'Lihat Artikel',
                'body' => "Artikel \"{$title}\" dijadwalkan terbit oleh {$by}.",
                'extra' => ['Waktu terbit: ' . $this->note . ' WIB'],
            ],
            'archived' => [
                'kind' => 'info',
                'heading' => 'Artikel diarsipkan',
                'action' => 'Lihat Artikel',
                'body' => "Artikel \"{$title}\" telah diarsipkan oleh {$by}.",
                'extra' => [],
            ],
            default => [
                'kind' => 'info',
                'heading' => 'Pembaruan artikel',
                'action' => 'Buka Artikel',
                'body' => "Ada pembaruan pada artikel \"{$title}\".",
                'extra' => [],
            ],
        };
    }
}