<?php

namespace App\Services;

use App\Models\Article;
use App\Models\User;
use App\Notifications\ArticleWorkflowNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class ArticleNotifier
{
    public static function notify(Article $article, string $event, ?User $actor = null, ?string $note = null): void
    {
        try {
            $article->loadMissing(['author', 'category']);

            $recipients = self::recipients($article, $event, $actor);

            if ($recipients->isEmpty()) {
                return;
            }

            Notification::send($recipients, new ArticleWorkflowNotification($article, $event, $actor, $note));
        } catch (\Throwable $e) {
            report($e); // email gagal tidak boleh menggagalkan aksi workflow
        }
    }

    private static function recipients(Article $article, string $event, ?User $actor): Collection
    {
        $author = $article->author;

        $list = match ($event) {
            'submitted', 'resubmitted' => User::permission('articles.approve')->get(),
            'approved' => collect([$author])->merge(User::permission('articles.publish')->get()),
            default => collect([$author]),
        };

        return $list
            ->filter()
            ->unique('id')
            ->reject(fn ($u) => $actor && $u->id === $actor->id)
            ->filter(fn ($u) => filled($u->email))
            ->values();
    }
}