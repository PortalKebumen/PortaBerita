<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\Tag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class CategoryTagManager extends Component
{
    use WithPagination;

    // Tab aktif: 'kategori' atau 'tag' (Dilarang query string URL agar URL tetap bersih)
    public string $tab = 'kategori';

    // Search bar reaktif (Livewire debounce, tanpa query string URL)
    public string $search = '';

    // Filter Kategori: all, main (kategori utama), sub (sub-kategori)
    public string $categoryTypeFilter = 'all';

    // State Modal Kategori
    public bool $showCategoryModal = false;
    public ?int $editingCategoryId = null;
    public string $categoryName = '';
    public ?string $categoryParentId = '';

    // State Modal Tag
    public bool $showTagModal = false;
    public ?int $editingTagId = null;
    public string $tagName = '';

    // State Modal Delete
    public bool $showDeleteModal = false;
    public string $deleteType = ''; // 'category' | 'tag'
    public ?int $deletingId = null;
    public string $deletingName = '';

    // Reset pagination ketika tab, search, atau filter berubah
    public function updatedTab(): void
    {
        $this->search = '';
        $this->resetPage('cat_page');
        $this->resetPage('tag_page');
    }

    public function updatedSearch(): void
    {
        $this->resetPage('cat_page');
        $this->resetPage('tag_page');
    }

    public function updatedCategoryTypeFilter(): void
    {
        $this->resetPage('cat_page');
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
        $this->updatedTab();
    }

    /**
     * Daftar Kategori Utama untuk opsi Parent di Form Kategori.
     */
    #[Computed]
    public function parentCategories()
    {
        return Category::whereNull('parent_id')
            ->when($this->editingCategoryId, fn ($q) => $q->where('id', '!=', $this->editingCategoryId))
            ->orderBy('name')
            ->get();
    }

    /**
     * Dataset Kategori dengan paginasi dan filter search.
     */
    #[Computed]
    public function categories(): LengthAwarePaginator
    {
        $query = Category::with('parent')
            ->withCount(['articles', 'children']);

        if (! empty(trim($this->search))) {
            $term = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('slug', 'like', $term);
            });
        }

        if ($this->categoryTypeFilter === 'main') {
            $query->whereNull('parent_id');
        } elseif ($this->categoryTypeFilter === 'sub') {
            $query->whereNotNull('parent_id');
        }

        return $query->orderByRaw('CASE WHEN parent_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('name')
            ->paginate(15, ['*'], 'cat_page');
    }

    /**
     * Dataset Tag dengan paginasi dan filter search.
     */
    #[Computed]
    public function tags(): LengthAwarePaginator
    {
        $query = Tag::withCount('articles');

        if (! empty(trim($this->search))) {
            $term = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('slug', 'like', $term);
            });
        }

        return $query->latest()
            ->paginate(20, ['*'], 'tag_page');
    }

    // ==================== KATEGORI CRUD ====================

    public function openAddCategory(): void
    {
        $this->editingCategoryId = null;
        $this->categoryName = '';
        $this->categoryParentId = '';
        $this->resetErrorBag();
        $this->showCategoryModal = true;
    }

    public function openEditCategory(int $id): void
    {
        $category = Category::withCount('children')->findOrFail($id);
        $this->editingCategoryId = $category->id;
        $this->categoryName = $category->name;
        $this->categoryParentId = $category->parent_id ? (string) $category->parent_id : '';
        $this->resetErrorBag();
        $this->showCategoryModal = true;
    }

    public function saveCategory(): void
    {
        $parentIdValue = ! empty($this->categoryParentId) ? (int) $this->categoryParentId : null;

        $validated = $this->validate([
            'categoryName' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->ignore($this->editingCategoryId),
            ],
            'categoryParentId' => [
                'nullable',
                function ($attribute, $value, $fail) use ($parentIdValue) {
                    if (! $parentIdValue) {
                        return;
                    }

                    if ($this->editingCategoryId && $parentIdValue === $this->editingCategoryId) {
                        $fail('Kategori tidak boleh menjadi induk bagi dirinya sendiri.');
                        return;
                    }

                    $parent = Category::find($parentIdValue);
                    if (! $parent) {
                        $fail('Kategori induk yang dipilih tidak valid.');
                        return;
                    }

                    if (! is_null($parent->parent_id)) {
                        $fail('Sub-kategori tidak dapat dijadikan induk (maksimal 1 tingkat hierarki).');
                        return;
                    }

                    if ($this->editingCategoryId) {
                        $category = Category::withCount('children')->find($this->editingCategoryId);
                        if ($category && $category->children_count > 0) {
                            $fail('Kategori utama yang sudah memiliki sub-kategori tidak dapat dijadikan sub-kategori.');
                            return;
                        }
                    }
                },
            ],
        ], [
            'categoryName.required' => 'Nama kategori wajib diisi.',
            'categoryName.unique' => 'Nama kategori sudah digunakan.',
        ]);

        if ($this->editingCategoryId) {
            $category = Category::findOrFail($this->editingCategoryId);
            $category->update([
                'name' => $validated['categoryName'],
                'parent_id' => $parentIdValue,
            ]);

            $this->showCategoryModal = false;
            unset($this->categories);
            $this->dispatch('flash-message', type: 'success', text: 'Kategori berhasil diupdate');
        } else {
            Category::create([
                'name' => $validated['categoryName'],
                'parent_id' => $parentIdValue,
            ]);

            $this->showCategoryModal = false;
            unset($this->categories);
            $this->dispatch('flash-message', type: 'success', text: 'Kategori berhasil disimpan');
        }
    }

    // ==================== TAG CRUD ====================

    public function openAddTag(): void
    {
        $this->editingTagId = null;
        $this->tagName = '';
        $this->resetErrorBag();
        $this->showTagModal = true;
    }

    public function openEditTag(int $id): void
    {
        $tag = Tag::findOrFail($id);
        $this->editingTagId = $tag->id;
        $this->tagName = $tag->name;
        $this->resetErrorBag();
        $this->showTagModal = true;
    }

    public function saveTag(): void
    {
        $validated = $this->validate([
            'tagName' => [
                'required',
                'string',
                'max:255',
                Rule::unique('tags', 'name')->ignore($this->editingTagId),
            ],
        ], [
            'tagName.required' => 'Nama tag wajib diisi.',
            'tagName.unique' => 'Nama tag sudah terdaftar.',
        ]);

        if ($this->editingTagId) {
            $tag = Tag::findOrFail($this->editingTagId);
            $tag->update(['name' => $validated['tagName']]);

            $this->showTagModal = false;
            unset($this->tags);
            $this->dispatch('flash-message', type: 'success', text: 'Tag berhasil diupdate');
        } else {
            Tag::create(['name' => $validated['tagName']]);

            $this->showTagModal = false;
            unset($this->tags);
            $this->dispatch('flash-message', type: 'success', text: 'Tag berhasil disimpan');
        }
    }

    // ==================== DELETE MODAL ====================

    public function confirmDeleteCategory(int $id): void
    {
        $category = Category::withCount('articles')->findOrFail($id);

        if ($category->articles_count > 0) {
            $this->dispatch('flash-message', type: 'error', text: "Kategori \"{$category->name}\" tidak dapat dihapus karena masih memiliki artikel terkait.");
            return;
        }

        $this->deleteType = 'category';
        $this->deletingId = $category->id;
        $this->deletingName = $category->name;
        $this->showDeleteModal = true;
    }

    public function confirmDeleteTag(int $id): void
    {
        $tag = Tag::findOrFail($id);

        $this->deleteType = 'tag';
        $this->deletingId = $tag->id;
        $this->deletingName = $tag->name;
        $this->showDeleteModal = true;
    }

    public function deleteConfirmed(): void
    {
        if ($this->deleteType === 'category') {
            $category = Category::withCount('articles')->findOrFail($this->deletingId);

            if ($category->articles_count > 0) {
                $this->dispatch('flash-message', type: 'error', text: 'Kategori tidak dapat dihapus karena masih memiliki artikel.');
                $this->showDeleteModal = false;
                return;
            }

            $category->delete();
            unset($this->categories);
            $this->dispatch('flash-message', type: 'success', text: 'Kategori berhasil dihapus');
        } elseif ($this->deleteType === 'tag') {
            $tag = Tag::findOrFail($this->deletingId);
            $tag->articles()->detach();
            $tag->delete();
            unset($this->tags);
            $this->dispatch('flash-message', type: 'success', text: 'Tag berhasil dihapus');
        }

        $this->showDeleteModal = false;
        $this->deletingId = null;
        $this->deletingName = '';
        $this->deleteType = '';
    }

    public function closeModals(): void
    {
        $this->showCategoryModal = false;
        $this->showTagModal = false;
        $this->showDeleteModal = false;
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.admin.category-tag-manager');
    }
}
