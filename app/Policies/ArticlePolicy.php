<?php

namespace App\Policies;

use App\Models\Article;
use App\Models\User;

class ArticlePolicy
{
    public function view(User $user, Article $article): bool
    {
        return $user->can('articles.view-any')
            || ($user->can('articles.view-own') && $this->owns($user, $article));
    }

    public function update(User $user, Article $article): bool
    {
        return $user->can('articles.update-any')
            || ($user->can('articles.update-own-draft')
                && $this->owns($user, $article)
                && $article->status === 'draft');
    }

    public function delete(User $user, Article $article): bool
    {
        return $user->can('articles.delete-any')
            || ($user->can('articles.delete-own-draft')
                && $this->owns($user, $article)
                && $article->status === 'draft');
    }

    public function approve(User $user, Article $article): bool
    {
        return $user->can('articles.approve');
    }

    public function reject(User $user, Article $article): bool
    {
        return $user->can('articles.reject');
    }

    public function publish(User $user, Article $article): bool
    {
        return $user->can('articles.publish');
    }

    private function owns(User $user, Article $article): bool
    {
        return $user->getKey() !== null
            && $article->author_id !== null
            && (string) $article->author_id === (string) $user->getKey();
    }
}
