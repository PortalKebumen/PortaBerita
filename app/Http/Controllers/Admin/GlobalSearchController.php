<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));

        if (mb_strlen($q) < 2) {
            return response()->json(['articles' => []]);
        }

        $user = $request->user();

        $query = Article::query()->with('author:id,name');

        if (! $user->can('articles.view-any')) {
            $query->where('author_id', $user->getKey());
        }

        // Escape karakter khusus LIKE
        $like = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $q) . '%';

        $articles = $query
            ->where('title', 'like', $like)
            ->latest()
            ->limit(6)
            ->get(['id', 'title', 'slug', 'status', 'author_id'])
            ->map(fn (Article $a) => [
                'title' => $a->title,
                'hint' => ucfirst($a->status->value) . ($a->author ? ' · ' . $a->author->name : ''),
                'url' => $user->can('update', $a)
                    ? route('admin.artikel.edit', $a)
                    : route('admin.artikel.index', ['q' => $a->title]),
            ]);

        return response()->json(['articles' => $articles]);
    }
}