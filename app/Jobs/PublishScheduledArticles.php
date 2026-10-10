<?php

namespace App\Jobs;

use App\Models\Article;
use App\Models\ArticleStatus;
use App\Services\ArticleNotifier;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class PublishScheduledArticles implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $articles = Article::query()
            ->where('status', ArticleStatus::Approved->value)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', Carbon::now())
            ->whereNull('published_at')
            ->get();

        foreach ($articles as $article) {
            try {
                $article->update([
                    'status' => ArticleStatus::Published->value,
                    'published_at' => Carbon::now(),
                    'scheduled_at' => null,
                ]);

                activity('artikel')
                    ->performedOn($article)
                    ->withProperties(['reason' => 'Otomatis dipublikasikan dari penjadwalan'])
                    ->log('Artikel dipublikasikan otomatis');
                
                ArticleNotifier::notify($article, 'published_auto');
                Log::info("Artikel '{$article->title}' (ID: {$article->id}) dipublikasikan dari penjadwalan.");
            } catch (\Exception $e) {
                Log::error("Gagal mempublikasikan artikel ID {$article->id}: {$e->getMessage()}");
            }
        }

        if ($articles->count() > 0) {
            Log::info("PublishScheduledArticles selesai: {$articles->count()} artikel dipublikasikan.");
        }
    }
}
