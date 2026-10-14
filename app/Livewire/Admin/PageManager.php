<?php

namespace App\Livewire\Admin;

use App\Models\MenuItem;
use App\Models\Page;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Kelola halaman statis (seperti "Pages" di WordPress).
 *
 * Mode daftar & formulir dibedakan lewat query string (?mode=create / ?mode=edit&id=X).
 * Perpindahan ke formulir memakai pemuatan halaman penuh supaya editor TinyMCE
 * (<x-editor>) terinisialisasi dengan benar pada DOMContentLoaded.
 */
class PageManager extends Component
{
    use WithPagination;

    #[Url(except: 'list')]
    public string $mode = 'list';

    #[Url(as: 'id', except: null)]
    public ?int $editingId = null;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: 'all')]
    public string $statusFilter = 'all';

    // ==================== FORM ====================
    public string $title = '';
    public string $slug = '';
    public bool $slugTouched = false;
    public string $content = '';
    public string $status = 'draft';
    public string $meta_title = '';
    public string $meta_description = '';
    /** @var array<int, string> */
    public array $menuLocations = [];

    // ==================== HAPUS ====================
    public bool $showDeleteModal = false;
    public ?int $deletingId = null;
    public string $deletingName = '';
    /** @var array<int, string> */
    public array $deletingLocations = [];

    public function mount(): void
    {
        if ($this->mode === 'create') {
            $this->authorize('pages.create');
            $this->editingId = null;
        } elseif ($this->mode === 'edit' && $this->editingId) {
            $this->authorize('pages.update');
            $this->loadPage($this->editingId);
        } else {
            $this->mode = 'list';
            $this->editingId = null;
        }

        if (! array_key_exists($this->statusFilter, Page::STATUSES)) {
            $this->statusFilter = 'all';
        }
    }

    private function loadPage(int $id): void
    {
        $page = Page::with('menuItems')->findOrFail($id);

        $this->editingId = $page->id;
        $this->title = $page->title;
        $this->slug = $page->slug;
        $this->slugTouched = true;
        $this->content = (string) $page->content;
        $this->status = $page->status;
        $this->meta_title = (string) $page->meta_title;
        $this->meta_description = (string) $page->meta_description;
        $this->menuLocations = $page->menuItems->pluck('location')->unique()->values()->all();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedTitle(): void
    {
        if (! $this->slugTouched) {
            $this->slug = Str::slug($this->title);
        }
    }

    public function updatedSlug(): void
    {
        $this->slugTouched = $this->slug !== '';
    }

    #[Computed]
    public function pages(): LengthAwarePaginator
    {
        return Page::query()
            ->with(['editor:id,name', 'menuItems:id,page_id,location'])
            ->when(trim($this->search) !== '', function ($q) {
                $term = '%'.trim($this->search).'%';
                $q->where(fn ($q) => $q->where('title', 'like', $term)->orWhere('slug', 'like', $term));
            })
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->latest('updated_at')
            ->paginate(15);
    }

    #[Computed]
    public function editingPage(): ?Page
    {
        return $this->editingId ? Page::with('editor:id,name')->find($this->editingId) : null;
    }

    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/', Rule::unique('pages', 'slug')->ignore($this->editingId)],
            'content' => ['nullable', 'string'],
            'status' => ['required', Rule::in(array_keys(Page::STATUSES))],
            'meta_title' => ['nullable', 'string', 'max:60'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'menuLocations' => ['array'],
            'menuLocations.*' => [Rule::in(array_keys(MenuItem::LOCATIONS))],
        ];
    }

    protected function messages(): array
    {
        return [
            'title.required' => 'Judul halaman wajib diisi.',
            'slug.required' => 'Slug wajib diisi.',
            'slug.regex' => 'Slug hanya boleh berisi huruf kecil, angka, dan tanda hubung.',
            'slug.unique' => 'Slug sudah dipakai halaman lain.',
            'meta_title.max' => 'Meta title maksimal 60 karakter.',
            'meta_description.max' => 'Meta description maksimal 160 karakter.',
        ];
    }

    public function save()
    {
        $isNew = ! $this->editingId;
        $this->authorize($isNew ? 'pages.create' : 'pages.update');

        if ($this->slug === '' && $this->title !== '') {
            $this->slug = Str::slug($this->title);
        }

        try {
            $validated = $this->validate();
        } catch (ValidationException $e) {
            $this->dispatch('flash-message', type: 'error', text: 'Halaman belum bisa disimpan. Periksa isian yang ditandai merah.');
            throw $e;
        }

        $page = DB::transaction(function () use ($validated) {
            $page = $this->editingId ? Page::findOrFail($this->editingId) : new Page;

            $page->fill([
                'title' => $validated['title'],
                'slug' => $validated['slug'],
                'content' => $validated['content'] ?: null,
                'status' => $validated['status'],
                'meta_title' => $validated['meta_title'] ?: null,
                'meta_description' => $validated['meta_description'] ?: null,
                'updated_by' => Auth::id(),
            ])->save();

            if (Gate::check('menus.update')) {
                $this->syncMenuLocations($page, $validated['menuLocations'] ?? []);
            }

            return $page;
        });

        if ($isNew) {
            session()->flash('success', 'Halaman berhasil dibuat.');

            return $this->redirect(route('admin.halaman.index', ['mode' => 'edit', 'id' => $page->id]));
        }

        $this->loadPage($page->id);
        unset($this->editingPage);
        $this->dispatch('flash-message', type: 'success', text: 'Halaman berhasil diperbarui.');
    }

    /**
     * Tambahkan halaman sebagai item level atas di lokasi yang dicentang (bila belum ada),
     * dan hapus item halaman ini dari lokasi yang tidak dicentang.
     *
     * @param  array<int, string>  $locations
     */
    private function syncMenuLocations(Page $page, array $locations): void
    {
        foreach (array_keys(MenuItem::LOCATIONS) as $location) {
            $existing = MenuItem::where('page_id', $page->id)->where('location', $location)->get();

            if (in_array($location, $locations, true)) {
                if ($existing->isEmpty()) {
                    MenuItem::create([
                        'location' => $location,
                        'parent_id' => null,
                        'type' => 'page',
                        'page_id' => $page->id,
                        'sort_order' => (int) MenuItem::where('location', $location)->whereNull('parent_id')->max('sort_order') + 1,
                    ]);
                }
            } else {
                $this->deleteMenuItems($existing->pluck('id')->all());
            }
        }
    }

    /** @param  array<int, int>  $ids */
    private function deleteMenuItems(array $ids): void
    {
        if (! $ids) {
            return;
        }

        MenuItem::whereIn('parent_id', $ids)->get()->each->delete();
        MenuItem::whereIn('id', $ids)->get()->each->delete();
    }

    // ==================== HAPUS ====================

    public function confirmDelete(int $id): void
    {
        $this->authorize('pages.delete');

        $page = Page::with('menuItems')->findOrFail($id);

        $this->deletingId = $page->id;
        $this->deletingName = $page->title;
        $this->deletingLocations = $page->menuItems->pluck('location')->unique()
            ->map(fn ($loc) => MenuItem::LOCATIONS[$loc] ?? $loc)->values()->all();
        $this->showDeleteModal = true;
    }

    public function deleteConfirmed()
    {
        $this->authorize('pages.delete');

        $page = Page::findOrFail($this->deletingId);

        DB::transaction(function () use ($page) {
            $this->deleteMenuItems($page->menuItems()->pluck('id')->all());
            $page->delete();
        });

        $wasEditing = $this->editingId === $page->id;
        $this->closeModals();

        if ($wasEditing) {
            session()->flash('success', 'Halaman berhasil dihapus.');

            return $this->redirect(route('admin.halaman.index'));
        }

        unset($this->pages);
        $this->dispatch('flash-message', type: 'success', text: 'Halaman berhasil dihapus.');
    }

    public function closeModals(): void
    {
        $this->showDeleteModal = false;
        $this->deletingId = null;
        $this->deletingName = '';
        $this->deletingLocations = [];
    }

    public function render()
    {
        return view('livewire.admin.page-manager', [
            'statuses' => Page::STATUSES,
            'locations' => MenuItem::LOCATIONS,
        ]);
    }
}
