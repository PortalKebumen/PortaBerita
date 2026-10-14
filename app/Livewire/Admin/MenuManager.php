<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Kelola susunan menu per lokasi (seperti "Menus" di WordPress).
 * Maksimal satu tingkat sub-item.
 */
class MenuManager extends Component
{
    #[Url(except: 'navbar')]
    public string $location = 'navbar';

    // ==================== PANEL TAMBAH ITEM ====================
    /** @var array<int, string> */
    public array $selectedPages = [];
    /** @var array<int, string> */
    public array $selectedCategories = [];
    public string $customUrl = '';
    public string $customLabel = '';
    public bool $customNewTab = false;
    public string $headingLabel = '';

    // ==================== MODAL EDIT ====================
    public bool $showEditModal = false;
    public ?int $editingItemId = null;
    public string $editType = '';
    public string $editLabel = '';
    public string $editUrl = '';
    public bool $editNewTab = false;
    public string $editTargetName = '';

    // ==================== MODAL HAPUS ====================
    public bool $showDeleteModal = false;
    public ?int $deletingItemId = null;
    public string $deletingLabel = '';
    public int $deletingChildrenCount = 0;

    public function mount(): void
    {
        $this->authorize('menus.view');
        $this->normalizeLocation();
    }

    private function normalizeLocation(): void
    {
        if (! array_key_exists($this->location, MenuItem::LOCATIONS)) {
            $this->location = 'navbar';
        }
    }

    public function setLocation(string $location): void
    {
        $this->location = $location;
        $this->normalizeLocation();
        $this->closeModals();
        unset($this->items);
    }

    public function updatedLocation(): void
    {
        $this->normalizeLocation();
        unset($this->items);
    }

    // ==================== DATA ====================

    #[Computed]
    public function pages(): Collection
    {
        return Page::orderBy('title')->get(['id', 'title', 'status']);
    }

    #[Computed]
    public function categoryGroups(): Collection
    {
        return Category::whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->orderBy('name')])
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);
    }

    /** Item level atas beserta anak-anaknya untuk lokasi terpilih. */
    #[Computed]
    public function items(): Collection
    {
        return MenuItem::query()
            ->where('location', $this->location)
            ->whereNull('parent_id')
            ->with(['page', 'category.parent', 'children.page', 'children.category.parent'])
            ->orderBy('sort_order')->orderBy('id')
            ->get();
    }

    // ==================== TAMBAH ITEM ====================

    private function nextSortOrder(?int $parentId): int
    {
        return (int) MenuItem::where('location', $this->location)
            ->when($parentId, fn ($q) => $q->where('parent_id', $parentId), fn ($q) => $q->whereNull('parent_id'))
            ->max('sort_order') + 1;
    }

    private function createItem(array $attributes): MenuItem
    {
        return MenuItem::create(array_merge([
            'location' => $this->location,
            'parent_id' => null,
            'label' => null,
            'url' => null,
            'page_id' => null,
            'category_id' => null,
            'new_tab' => false,
            'sort_order' => $this->nextSortOrder(null),
        ], $attributes));
    }

    private function added(int $count): void
    {
        unset($this->items);
        $this->dispatch('flash-message', type: 'success', text: $count === 1 ? 'Item berhasil ditambahkan ke menu.' : "{$count} item berhasil ditambahkan ke menu.");
    }

    public function addPages(): void
    {
        $this->authorize('menus.update');

        $ids = array_map('intval', $this->selectedPages);
        $pages = Page::whereIn('id', $ids)->orderBy('title')->get(['id']);

        if ($pages->isEmpty()) {
            $this->dispatch('flash-message', type: 'warning', text: 'Pilih minimal satu halaman.');

            return;
        }

        // Halaman yang sudah ada di lokasi ini dilewati agar tidak dobel
        $pages = $pages->whereNotIn('id', $this->usedIds('page_id'));
        if ($pages->isEmpty()) {
            $this->selectedPages = [];
            $this->dispatch('flash-message', type: 'warning', text: 'Halaman yang dipilih sudah ada di menu ini.');

            return;
        }

        DB::transaction(fn () => $pages->each(fn (Page $p) => $this->createItem(['type' => 'page', 'page_id' => $p->id])));

        $this->selectedPages = [];
        $this->added($pages->count());
    }

    public function addCategories(): void
    {
        $this->authorize('menus.update');

        $ids = array_map('intval', $this->selectedCategories);
        $categories = Category::whereIn('id', $ids)->orderByRaw('CASE WHEN parent_id IS NULL THEN 0 ELSE 1 END')->orderBy('name')->get(['id']);

        if ($categories->isEmpty()) {
            $this->dispatch('flash-message', type: 'warning', text: 'Pilih minimal satu kategori.');

            return;
        }

        $categories = $categories->whereNotIn('id', $this->usedIds('category_id'));
        if ($categories->isEmpty()) {
            $this->selectedCategories = [];
            $this->dispatch('flash-message', type: 'warning', text: 'Kategori yang dipilih sudah ada di menu ini.');

            return;
        }

        DB::transaction(fn () => $categories->each(fn (Category $c) => $this->createItem(['type' => 'category', 'category_id' => $c->id])));

        $this->selectedCategories = [];
        $this->added($categories->count());
    }

    /**
     * Id halaman/kategori yang sudah dipakai di lokasi aktif.
     *
     * @return array<int, int>
     */
    private function usedIds(string $column): array
    {
        return MenuItem::where('location', $this->location)->whereNotNull($column)->pluck($column)->map(fn ($id) => (int) $id)->all();
    }

    private function urlRule(): array
    {
        return ['required', 'string', 'max:255', 'regex:/^(https?:\/\/\S+|\/\S*|#\S*|mailto:\S+|tel:\S+)$/i'];
    }

    public function addCustomLink(): void
    {
        $this->authorize('menus.update');

        $validated = $this->validate([
            'customUrl' => $this->urlRule(),
            'customLabel' => ['required', 'string', 'max:255'],
            'customNewTab' => ['boolean'],
        ], [
            'customUrl.required' => 'URL wajib diisi.',
            'customUrl.regex' => 'URL harus diawali http://, https://, / (tautan internal), #, mailto: atau tel:.',
            'customLabel.required' => 'Teks tautan wajib diisi.',
        ]);

        if (MenuItem::where('location', $this->location)->where('type', 'url')->where('url', trim($validated['customUrl']))->exists()) {
            $this->addError('customUrl', 'Tautan ini sudah ada di menu ini.');

            return;
        }

        $this->createItem([
            'type' => 'url',
            'url' => trim($validated['customUrl']),
            'label' => trim($validated['customLabel']),
            'new_tab' => $validated['customNewTab'],
        ]);

        $this->reset('customUrl', 'customLabel', 'customNewTab');
        $this->added(1);
    }

    public function addHeading(): void
    {
        $this->authorize('menus.update');

        $validated = $this->validate([
            'headingLabel' => ['required', 'string', 'max:255'],
        ], [
            'headingLabel.required' => 'Judul kolom wajib diisi.',
        ]);

        $this->createItem(['type' => 'heading', 'label' => trim($validated['headingLabel'])]);

        $this->reset('headingLabel');
        $this->added(1);
    }

    // ==================== URUTAN & TINGKAT ====================

    private function findItem(int $id): MenuItem
    {
        return MenuItem::where('location', $this->location)->findOrFail($id);
    }

    /** @return Collection<int, MenuItem> */
    private function siblingsOf(MenuItem $item): Collection
    {
        return MenuItem::where('location', $item->location)
            ->when($item->parent_id, fn ($q) => $q->where('parent_id', $item->parent_id), fn ($q) => $q->whereNull('parent_id'))
            ->orderBy('sort_order')->orderBy('id')
            ->get();
    }

    /** Simpan ulang sort_order berurutan (1..n) sesuai urutan koleksi. */
    private function persistOrder(Collection $ordered): void
    {
        $ordered->values()->each(function (MenuItem $sibling, int $index) {
            if ($sibling->sort_order !== $index + 1) {
                $sibling->sort_order = $index + 1;
                $sibling->save();
            }
        });
    }

    /**
     * Drag & drop (wire:sort). $groupId = 'root' untuk level atas, atau id item induk untuk daftar sub-itemnya.
     */
    public function reorder(int|string $id, int $position, ?string $groupId = null): void
    {
        $this->authorize('menus.update');

        $item = $this->findItem((int) $id);
        $newParentId = in_array($groupId, [null, '', 'root'], true) ? null : (int) $groupId;

        if ($newParentId !== null) {
            $parent = $this->findItem($newParentId);

            if ($parent->id === $item->id || $parent->parent_id !== null) {
                unset($this->items);

                return;
            }

            if ($item->children()->exists()) {
                $this->dispatch('flash-message', type: 'warning', text: 'Item yang memiliki sub-item tidak dapat dijadikan sub-item.');
                unset($this->items);

                return;
            }
        }

        $oldParentId = $item->parent_id;

        DB::transaction(function () use ($item, $newParentId, $oldParentId, $position) {
            if ($oldParentId !== $newParentId) {
                $item->parent_id = $newParentId;
                $item->save();
            }

            $siblings = MenuItem::where('location', $this->location)
                ->when($newParentId, fn ($q) => $q->where('parent_id', $newParentId), fn ($q) => $q->whereNull('parent_id'))
                ->where('id', '!=', $item->id)
                ->orderBy('sort_order')->orderBy('id')
                ->get()->all();

            array_splice($siblings, max(0, min($position, count($siblings))), 0, [$item]);
            $this->persistOrder(collect($siblings));

            if ($oldParentId !== $newParentId) {
                $this->persistOrder(MenuItem::where('location', $this->location)
                    ->when($oldParentId, fn ($q) => $q->where('parent_id', $oldParentId), fn ($q) => $q->whereNull('parent_id'))
                    ->orderBy('sort_order')->orderBy('id')
                    ->get());
            }
        });

        unset($this->items);
    }

    public function moveUp(int $id): void
    {
        $this->move($id, -1);
    }

    public function moveDown(int $id): void
    {
        $this->move($id, 1);
    }

    private function move(int $id, int $direction): void
    {
        $this->authorize('menus.update');

        $item = $this->findItem($id);
        $siblings = $this->siblingsOf($item)->values();
        $index = $siblings->search(fn (MenuItem $s) => $s->id === $item->id);
        $target = $index + $direction;

        if ($index === false || $target < 0 || $target >= $siblings->count()) {
            return;
        }

        $ordered = $siblings->all();
        [$ordered[$index], $ordered[$target]] = [$ordered[$target], $ordered[$index]];

        DB::transaction(fn () => $this->persistOrder(collect($ordered)));
        unset($this->items);
    }

    /** Jadikan item sub-item dari saudara sebelumnya (maksimal satu tingkat). */
    public function indent(int $id): void
    {
        $this->authorize('menus.update');

        $item = $this->findItem($id);

        if ($item->parent_id) {
            $this->dispatch('flash-message', type: 'warning', text: 'Menu hanya mendukung satu tingkat sub-item.');

            return;
        }

        if ($item->children()->exists()) {
            $this->dispatch('flash-message', type: 'warning', text: 'Item yang memiliki sub-item tidak dapat dijadikan sub-item.');

            return;
        }

        $siblings = $this->siblingsOf($item)->values();
        $index = $siblings->search(fn (MenuItem $s) => $s->id === $item->id);

        if (! $index) {
            $this->dispatch('flash-message', type: 'warning', text: 'Item pertama tidak dapat dijadikan sub-item.');

            return;
        }

        $parent = $siblings[$index - 1];

        DB::transaction(function () use ($item, $parent) {
            $item->parent_id = $parent->id;
            $item->sort_order = $this->nextSortOrder($parent->id);
            $item->save();

            $this->persistOrder(MenuItem::where('location', $this->location)->whereNull('parent_id')->orderBy('sort_order')->orderBy('id')->get());
        });

        unset($this->items);
    }

    /** Kembalikan sub-item ke level atas, tepat setelah induknya. */
    public function outdent(int $id): void
    {
        $this->authorize('menus.update');

        $item = $this->findItem($id);

        if (! $item->parent_id) {
            return;
        }

        $parentId = $item->parent_id;

        DB::transaction(function () use ($item, $parentId) {
            $item->parent_id = null;
            $item->save();

            $topLevel = MenuItem::where('location', $this->location)
                ->whereNull('parent_id')
                ->where('id', '!=', $item->id)
                ->orderBy('sort_order')->orderBy('id')
                ->get()->values();

            $parentIndex = $topLevel->search(fn (MenuItem $s) => $s->id === $parentId);
            $ordered = $topLevel->all();
            array_splice($ordered, $parentIndex === false ? count($ordered) : $parentIndex + 1, 0, [$item]);

            $this->persistOrder(collect($ordered));
            $this->persistOrder(MenuItem::where('parent_id', $parentId)->orderBy('sort_order')->orderBy('id')->get());
        });

        unset($this->items);
    }

    // ==================== EDIT ====================

    public function editItem(int $id): void
    {
        $this->authorize('menus.update');

        $item = $this->findItem($id);
        $item->load(['page', 'category']);

        $this->editingItemId = $item->id;
        $this->editType = $item->type;
        $this->editLabel = (string) $item->label;
        $this->editUrl = (string) $item->url;
        $this->editNewTab = (bool) $item->new_tab;
        $this->editTargetName = match ($item->type) {
            'page' => (string) ($item->page?->title ?? 'Halaman sudah dihapus'),
            'category' => (string) ($item->category?->name ?? 'Kategori sudah dihapus'),
            default => '',
        };
        $this->resetErrorBag();
        $this->showEditModal = true;
    }

    public function saveItem(): void
    {
        $this->authorize('menus.update');

        $item = $this->findItem((int) $this->editingItemId);
        $needsLabel = in_array($item->type, ['url', 'heading'], true);

        $rules = [
            'editLabel' => [$needsLabel ? 'required' : 'nullable', 'string', 'max:255'],
            'editNewTab' => ['boolean'],
        ];

        if ($item->type === 'url') {
            $rules['editUrl'] = $this->urlRule();
        }

        $validated = $this->validate($rules, [
            'editLabel.required' => $item->type === 'heading' ? 'Judul kolom wajib diisi.' : 'Teks tautan wajib diisi.',
            'editUrl.required' => 'URL wajib diisi.',
            'editUrl.regex' => 'URL harus diawali http://, https://, / (tautan internal), #, mailto: atau tel:.',
        ]);

        $item->label = filled($validated['editLabel']) ? trim($validated['editLabel']) : null;
        $item->new_tab = $item->type === 'heading' ? false : $validated['editNewTab'];

        if ($item->type === 'url') {
            $item->url = trim($validated['editUrl']);
        }

        $item->save();

        $this->closeModals();
        unset($this->items);
        $this->dispatch('flash-message', type: 'success', text: 'Item menu berhasil diperbarui.');
    }

    // ==================== HAPUS ====================

    public function confirmDelete(int $id): void
    {
        $this->authorize('menus.update');

        $item = $this->findItem($id);
        $item->load(['page', 'category']);

        $this->deletingItemId = $item->id;
        $this->deletingLabel = $item->displayLabel() ?: MenuItem::TYPES[$item->type];
        $this->deletingChildrenCount = $item->children()->count();
        $this->showDeleteModal = true;
    }

    public function deleteConfirmed(): void
    {
        $this->authorize('menus.update');

        $item = $this->findItem((int) $this->deletingItemId);

        DB::transaction(function () use ($item) {
            $item->children()->get()->each->delete();
            $item->delete();
            $this->persistOrder($this->siblingsOf($item));
        });

        $this->closeModals();
        unset($this->items);
        $this->dispatch('flash-message', type: 'success', text: 'Item menu berhasil dihapus.');
    }

    public function closeModals(): void
    {
        $this->showEditModal = false;
        $this->editingItemId = null;
        $this->showDeleteModal = false;
        $this->deletingItemId = null;
        $this->deletingLabel = '';
        $this->deletingChildrenCount = 0;
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.admin.menu-manager', [
            'locations' => MenuItem::LOCATIONS,
            'types' => MenuItem::TYPES,
        ]);
    }
}
