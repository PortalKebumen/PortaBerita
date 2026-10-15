<?php

namespace App\Livewire\Admin;

use App\Models\Article;
use App\Services\ArticleHistory;
use App\Support\TextDiff;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Panel "Riwayat Versi" di halaman edit artikel: daftar versi, perbandingan per versi,
 * dan pemulihan isi versi lama (khusus Redaktur ke atas).
 */
class ArticleHistoryPanel extends Component
{
    public Article $article;

    public int $limit = 10;

    public ?int $selectedId = null;

    /** null, 'after' (pulihkan ke versi ini) atau 'before' (batalkan perubahan ini) */
    public ?string $confirmingRestore = null;

    public function mount(Article $article): void
    {
        $this->authorize('viewRevisions', $article);
        $this->article = $article;
    }

    #[On('article-saved')]
    public function refreshVersions(): void
    {
        unset($this->versions);
    }

    #[Computed]
    public function versions(): Collection
    {
        return app(ArticleHistory::class)->versions($this->article);
    }

    #[Computed]
    public function selected(): ?array
    {
        return $this->selectedId
            ? $this->versions->first(fn ($v) => $v['activity']->id === $this->selectedId)
            : null;
    }

    /**
     * Baris perbandingan per kolom untuk versi terpilih. Isi artikel memakai diff paragraf/kata,
     * kolom lain cukup nilai sebelum → sesudah.
     *
     * @return list<array{key: string, label: string, diff?: list<array>, before?: string, after?: string}>
     */
    #[Computed]
    public function comparison(): array
    {
        if (! $this->selected) {
            return [];
        }

        $history = app(ArticleHistory::class);

        return collect($this->selected['changed'])->map(function (string $key) use ($history) {
            $before = $this->selected['before'][$key] ?? null;
            $after = $this->selected['after'][$key] ?? null;
            $row = ['key' => $key, 'label' => ArticleHistory::FIELDS[$key]];

            if (in_array($key, ['content', 'excerpt', 'title', 'seo.meta_title', 'seo.meta_description'], true)) {
                $row['diff'] = $key === 'content'
                    ? TextDiff::compare(TextDiff::paragraphs($before), TextDiff::paragraphs($after))
                    : [['type' => 'change', 'html' => TextDiff::words((string) $before, (string) $after)]];
            } else {
                $row['before'] = $history->display($key, $before);
                $row['after'] = $history->display($key, $after);
            }

            return $row;
        })->all();
    }

    /**
     * Mode pemulihan yang tersedia untuk versi terpilih: hanya yang isinya berbeda dari kondisi sekarang.
     *
     * @return list<string> 'after' dan/atau 'before'
     */
    #[Computed]
    public function restoreModes(): array
    {
        $current = $this->versions->first();

        if (! $this->selected || ! $current || ! auth()->user()->can('restoreVersion', $this->article)) {
            return [];
        }

        $differs = fn (array $state) => collect(ArticleHistory::RESTORABLE)->contains(
            fn ($key) => filled($state[$key] ?? null) && ($state[$key] ?? null) != ($current['after'][$key] ?? null)
        );

        return array_values(array_filter([
            $differs($this->selected['after']) ? 'after' : null,
            $this->selected['activity']->event !== 'created' && $differs($this->selected['before']) ? 'before' : null,
        ]));
    }

    public function showVersion(int $id): void
    {
        $this->selectedId = $id;
        $this->confirmingRestore = null;
    }

    public function closeVersion(): void
    {
        $this->selectedId = null;
        $this->confirmingRestore = null;
    }

    public function showMore(): void
    {
        $this->limit += 10;
    }

    public function restore(): void
    {
        $this->authorize('restoreVersion', $this->article);
        $this->authorize('update', $this->article);

        if (! $this->selectedId || ! in_array($this->confirmingRestore, ['after', 'before'], true)) {
            return;
        }

        $restored = app(ArticleHistory::class)->restore($this->article, $this->selectedId, $this->confirmingRestore === 'before');

        if ($restored === []) {
            $this->closeVersion();
            $this->dispatch('flash-message', type: 'info', text: 'Isi artikel sudah sama dengan versi tersebut.');

            return;
        }

        // Muat ulang halaman agar form & editor menampilkan isi yang dipulihkan
        session()->flash('success', 'Artikel dipulihkan: ' . collect($restored)->map(fn ($k) => ArticleHistory::FIELDS[$k])->implode(', ') . '.');
        $this->redirectRoute('admin.artikel.edit', $this->article);
    }

    /** Label & warna lencana untuk satu versi. */
    public function badge(array $version): array
    {
        $activity = $version['activity'];

        return match (true) {
            $activity->properties->has('restored_from') => ['Dipulihkan', 'badge-info'],
            $activity->event === 'created' => ['Dibuat', 'badge-success'],
            $activity->event === 'deleted' => ['Dihapus', 'badge-danger'],
            $version['changed'] === [] && $activity->event !== 'updated' => ['Alur Kerja', 'badge-neutral'],
            in_array('status', $version['changed'], true) && count($version['changed']) <= 3 => ['Status', 'badge-warning'],
            default => ['Diubah', 'badge-warning'],
        };
    }

    public function render()
    {
        return view('livewire.admin.article-history-panel');
    }
}
