<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;

class ActivityLogViewer extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public ?int $userId = null;

    #[Url(history: true)]
    public ?string $actionFilter = null;

    #[Url(history: true)]
    public ?string $date = null;

    public ?int $selectedLogId = null;

    public bool $showPurgeModal = false;

    public string $purgeOlderThan = '3months';

    public function updated($property): void
    {
        if (in_array($property, ['search', 'userId', 'actionFilter', 'date'], true)) {
            $this->resetPage();
        }
    }

    protected function canViewAny(): bool
    {
        return Gate::allows('activity-log.view-any');
    }

    #[Computed]
    public function logs()
    {
        return Activity::query()
            ->with('causer')
            ->when(! $this->canViewAny(), function ($q) {
                $q->where('causer_id', Auth::id())
                    ->where('causer_type', Auth::user()::class);
            })
            ->when($this->userId, fn ($q) => $q->where('causer_id', $this->userId))
            ->when($this->actionFilter, fn ($q) => $q->where('event', $this->actionFilter))
            ->when($this->date, fn ($q) => $q->whereDate('created_at', $this->date))
            ->when($this->search, function ($q) {
                $term = $this->search;
                $matchingEvents = $this->matchingEventsForSearch($term);

                $q->where(function ($sub) use ($term, $matchingEvents) {
                    $sub->where('description', 'like', "%{$term}%")
                        ->orWhere('properties->ip', 'like', "%{$term}%")
                        ->orWhereHas('causer', fn ($c) => $c->where('name', 'like', "%{$term}%"))
                        ->orWhereHasMorph('subject', '*', function ($morphQuery, $type) use ($term) {
                            $column = match ($type) {
                                \App\Models\Article::class => 'title',
                                \App\Models\Category::class, \App\Models\Tag::class, \App\Models\User::class => 'name',
                                default => null,
                            };

                            $column
                                ? $morphQuery->where($column, 'like', "%{$term}%")
                                : $morphQuery->whereRaw('1 = 0');
                        });

                    if (! empty($matchingEvents)) {
                        $sub->orWhereIn('event', $matchingEvents);
                    }
                });
            })
            ->latest('created_at')
            ->paginate(15);
    }

    #[Computed]
    public function userOptions()
    {
        return $this->canViewAny()
            ? User::orderBy('name')->get(['id', 'name'])
            : collect();
    }

    #[Computed]
    public function actionOptions()
    {
        return Activity::query()->whereNotNull('event')->distinct()->orderBy('event')->pluck('event');
    }

    #[Computed]
    public function selectedLog(): ?Activity
    {
        return $this->selectedLogId
            ? Activity::with(['causer', 'subject'])->find($this->selectedLogId)
            : null;
    }

    public function showDetail(int $id): void
    {
        $this->selectedLogId = $id;
    }

    public function closeDetail(): void
    {
        $this->selectedLogId = null;
    }

    public function openPurgeModal(): void
    {
        $this->authorize('activity-log.purge');
        $this->showPurgeModal = true;
    }

    public function closePurgeModal(): void
    {
        $this->showPurgeModal = false;
    }

    public function purgeOldLogs(): void
    {
        $this->authorize('activity-log.purge');

        $cutoff = match ($this->purgeOlderThan) {
            '1week' => now()->subWeek(),
            '1month' => now()->subMonth(),
            '3months' => now()->subMonths(3),
            '6months' => now()->subMonths(6),
            '1year' => now()->subYear(),
            default => now()->subMonths(3),
        };

        Activity::where('created_at', '<', $cutoff)->delete();

        $this->showPurgeModal = false;
        $this->resetPage();

        $this->dispatch('flash-message', type: 'success', text: 'Log lama berhasil dibersihkan.');
    }

    protected function eventLabels(): array
    {
        return [
            'created' => 'Membuat',
            'updated' => 'Mengubah',
            'deleted' => 'Menghapus',
            'login' => 'Login',
            'logout' => 'Logout',
            'failed' => 'Login Gagal',
        ];
    }

    public function actionMeta(?string $event): array
    {
        $classes = [
            'created' => 'badge-neutral',
            'updated' => 'badge-warning',
            'deleted' => 'badge-danger',
            'login' => 'badge-success',
            'logout' => 'badge-info',
            'failed' => 'badge-danger',
        ];

        if ($event === null) {
            return ['Lainnya', 'badge-neutral'];
        }

        return [
            $this->eventLabels()[$event] ?? ucfirst($event),
            $classes[$event] ?? 'badge-accent',
        ];
    }

    protected function matchingEventsForSearch(string $term): array
    {
        $term = mb_strtolower($term);

        return collect($this->eventLabels())
            ->filter(fn ($label) => str_contains(mb_strtolower($label), $term))
            ->keys()
            ->all();
    }

    public function subjectLabel(Activity $log): string
    {
        if (! $log->subject_type) {
            return $log->log_name === 'auth' ? 'Autentikasi' : '—';
        }

        $labels = [
            'Article' => 'Artikel',
            'Category' => 'Kategori',
            'Tag' => 'Tag',
            'User' => 'Pengguna',
            'LibraryMedia' => 'Media',
        ];
        $modelLabel = $labels[class_basename($log->subject_type)] ?? class_basename($log->subject_type);

        $name = $log->subject?->title
            ?? $log->subject?->name
            ?? $log->properties['file_name']
            ?? null;

        if (! $log->subject) {
            return "{$modelLabel} · #{$log->subject_id} (dihapus)";
        }

        return $name ? "{$modelLabel} · {$name}" : "{$modelLabel} · #{$log->subject_id}";
    }

    public function render()
    {
        return view('livewire.admin.activity-log-viewer');
    }
}