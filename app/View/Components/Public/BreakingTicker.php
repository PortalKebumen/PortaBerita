<?php

namespace App\View\Components\Public;

use App\Models\Article;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\Component;

class BreakingTicker extends Component
{
    public Collection $items;

    public function __construct()
    {
        // Simpan array biasa di cache, jangan model Eloquent.
        $rows = Cache::remember('public.breaking_ticker.v2', 60, function () {
            return Article::query()
                ->where('status', 'published')
                ->where('is_breaking', true)
                ->where('published_at', '>=', now()->subHours(48))
                ->latest('published_at')
                ->limit(8)
                ->get(['id', 'title', 'slug', 'published_at'])
                ->map(fn (Article $a) => [
                    'id' => $a->id,
                    'title' => $a->title,
                    'slug' => $a->slug,
                    'url' => route('public.artikel', $a->slug),
                    'published_at' => $a->published_at?->toIso8601String(),
                ])
                ->all();
        });

        $this->items = collect($rows)->map(fn (array $row) => (object) $row);
    }

    public function render(): View
    {
        return view('components.public.breaking-ticker');
    }
}