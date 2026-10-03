<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\ArticleStatus;
use App\Models\User;

class ArticlePolicy
{
    public function view(User $user, Article $article): bool
    {
        return $user->can('articles.view-any')
            || ($user->can('articles.view-own') && $this->owns($user, $article));
    }

    public function create(User $user): bool
    {
        return $user->can('articles.create');
    }

    public function update(User $user, Article $article): bool
    {
        // FR-USR-05: Redaktur dapat mengedit semua artikel
        if ($user->can('articles.update-any')) {
            return true;
        }

        // FR-USR-04: Penulis hanya dapat mengedit artikelnya sendiri selama masih Draft/Rejected
        return $user->can('articles.update-own-draft')
            && $this->owns($user, $article)
            && in_array($article->status, [ArticleStatus::Draft, ArticleStatus::Rejected]);
    }

    public function delete(User $user, Article $article): bool
    {
        return $user->can('articles.delete-any')
            || ($user->can('articles.delete-own-draft')
                && $this->owns($user, $article)
                && $article->status === ArticleStatus::Draft);
    }

    public function submit(User $user, Article $article): bool
    {
        // FR-ART-02: Penulis mengajukan artikel dari Draft atau resubmit setelah Rejected
        return $this->owns($user, $article)
            && $user->can('articles.submit')
            && in_array($article->status, [ArticleStatus::Draft, ArticleStatus::Rejected]);
    }

    public function approve(User $user, Article $article): bool
    {
        // FR-USR-05: Hanya Redaktur yang dapat menyetujui, dan hanya artikel Submitted
        return $user->can('articles.approve') 
            && $article->status === ArticleStatus::Submitted;
    }

    public function reject(User $user, Article $article): bool
    {
        // FR-ART-03: Redaktur dapat menolak artikel Submitted atau Approved (sebelum publish)
        return $user->can('articles.reject')
            && in_array($article->status, [ArticleStatus::Submitted, ArticleStatus::Approved]);
    }

    public function publish(User $user, Article $article): bool
    {
        // FR-USR-05 & FR-ART-02: Hanya artikel Approved yang bisa dipublikasikan
        return $user->can('articles.publish') 
            && $article->status === ArticleStatus::Approved;
    }

    public function schedule(User $user, Article $article): bool
    {
        // FR-ART-04: Hanya artikel Approved yang bisa dijadwalkan
        return $user->can('articles.schedule') 
            && $article->status === ArticleStatus::Approved;
    }

    public function archive(User $user, Article $article): bool
    {
        // FR-ART-08: Hanya artikel Published yang bisa diarsipkan
        return $user->can('articles.archive') 
            && $article->status === ArticleStatus::Published;
    }

    public function viewRevisions(User $user, Article $article): bool
    {
        return $user->can('articles.view-revisions')
            || ($this->owns($user, $article) && $user->can('articles.view-own-revisions'));
    }

    private function owns(User $user, Article $article): bool
    {
        return $user->getKey() !== null
            && $article->author_id !== null
            && (string) $article->author_id === (string) $user->getKey();
    }
}
