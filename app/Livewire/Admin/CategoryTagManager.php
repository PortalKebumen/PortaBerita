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

    public string $tab = 'kategori';
    public string $search = '';
    public string $categoryTypeFilter = 'all';

    public bool $showCategoryModal = false;
    public ?int $editingCategoryId = null;
    public string $categoryName = '';
    public ?string $categoryParentId = '';

    public bool $showTagModal = false;
    public ?int $editingTagId = null;
    public string $tagName = '';

    public bool $showDeleteModal = false;
    public string $deleteType = '';
    public ?int $deletingId = null;
    public string $deletingName = '';

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

    #[Computed]
    public function parentCategories()
    {
        return Category::whereNull('parent_id')
            ->when($this->editingCategoryId, fn ($q) => $q->where('id', '!=', $this->editingCategoryId))
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function categories(): LengthAwarePaginator
    {
        $query = Category::with('parent')->withCount(['articles', 'children']);

        if (! empty(trim($this->search))) {
            $term = '%'.trim($this->search).'%';
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

    #[Computed]
    public function tags(): LengthAwarePaginator
    {
        $query = Tag::withCount('articles');

        if (! empty(trim($this->search))) {
            $term = '%'.trim($this->search).'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('slug', 'like', $term);
            });
        }

        return $query->latest()->paginate(20, ['*'], 'tag_page');
    }

    // ==================== KATEGORI CRUD ====================

    public function openAddCategory(): void
    {
        $this->authorize('categories.create');

        $this->editingCategoryId = null;
        $this->categoryName = '';
        $this->categoryParentId = '';
        $this->resetErrorBag();
        $this->showCategoryModal = true;
    }

    public function openEditCategory(int $id): void
    {
        $this->authorize('categories.update');

        $category = Category::withCount('children')->findOrFail($id);
        $this->editingCategoryId = $category->id;
        $this->categoryName = $category->name;
        $this->categoryParentId = $category->parent_id ? (string) $category->parent_id : '';
        $this->resetErrorBag();
        $this->showCategoryModal = true;
    }

    public function saveCategory(): void
    {
        if ($this->editingCategoryId) {
            $this->authorize('categories.update');
        } else {
            $this->authorize('categories.create');
        }

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
        $this->authorize('tags.create');

        $this->editingTagId = null;
        $this->tagName = '';
        $this->resetErrorBag();
        $this->showTagModal = true;
    }

    public function openEditTag(int $id): void
    {
        $this->authorize('tags.update');

        $tag = Tag::findOrFail($id);
        $this->editingTagId = $tag->id;
        $this->tagName = $tag->name;
        $this->resetErrorBag();
        $this->showTagModal = true;
    }

    public function saveTag(): void
    {
        if ($this->editingTagId) {
            $this->authorize('tags.update');
        } else {
            $this->authorize('tags.create');
        }

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
        $this->authorize('categories.delete');

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
        $this->authorize('tags.delete');

        $tag = Tag::findOrFail($id);

        $this->deleteType = 'tag';
        $this->deletingId = $tag->id;
        $this->deletingName = $tag->name;
        $this->showDeleteModal = true;
    }

    public function deleteConfirmed(): void
    {

        if ($this->deleteType === 'category') {
            $this->authorize('categories.delete');
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
            $this->authorize('tags.delete');
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