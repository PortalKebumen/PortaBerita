<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $categorySearch = $request->query('q_cat');
        $tagSearch = $request->query('q_tag');

        // Ambil semua kategori beserta parent & hitungan artikel
        $categoriesQuery = Category::with('parent')->withCount('articles');
        if ($categorySearch) {
            $categoriesQuery->where('name', 'like', "%{$categorySearch}%")
                ->orWhere('slug', 'like', "%{$categorySearch}%");
        }
        $categories = $categoriesQuery->orderByRaw('CASE WHEN parent_id IS NULL THEN 0 ELSE 1 END')
            ->orderBy('name')
            ->paginate(15, ['*'], 'cat_page')
            ->withQueryString();

        // Daftar kategori utama untuk dropdown parent di modal
        $parentCategories = Category::whereNull('parent_id')->orderBy('name')->get();

        // Ambil daftar tags dengan hitungan artikel
        $tagsQuery = Tag::withCount('articles');
        if ($tagSearch) {
            $tagsQuery->where('name', 'like', "%{$tagSearch}%")
                ->orWhere('slug', 'like', "%{$tagSearch}%");
        }
        $tags = $tagsQuery->latest()->paginate(20, ['*'], 'tag_page')->withQueryString();

        return view('admin.kategori-tag', compact('categories', 'parentCategories', 'tags'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
            'parent_id' => [
                'nullable',
                'exists:categories,id',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $parent = Category::find($value);
                        if ($parent && !is_null($parent->parent_id)) {
                            $fail('Sub-kategori tidak dapat dijadikan induk (maksimal 1 tingkat hierarki).');
                        }
                    }
                },
            ],
        ]);

        Category::firstOrCreate($validated);
        return redirect()->back()->with('success', 'Kategori berhasil dibuat');
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($category->id)],
            'parent_id' => [
                'nullable',
                'exists:categories,id',
                function ($attribute, $value, $fail) use ($category) {
                    if ($value) {
                        if ($value == $category->id) {
                            $fail('Kategori tidak boleh menjadi induk bagi dirinya sendiri.');
                            return;
                        }

                        $parent = Category::find($value);
                        if ($parent && !is_null($parent->parent_id)) {
                            $fail('Sub-kategori tidak dapat dijadikan induk (maksimal 1 tingkat hierarki).');
                            return;
                        }

                        // Jika kategori ini sudah memiliki anak (sub-kategori), tidak boleh dijadikan sub-kategori
                        if ($category->children()->exists()) {
                            $fail('Kategori utama yang sudah memiliki sub-kategori tidak dapat dijadikan sub-kategori.');
                            return;
                        }
                    }
                },
            ],
        ]);

        $category->update($validated);
        return redirect()->back()->with('success', 'Kategori berhasil diupdate');
    }

    public function destroy(Category $category)
    {
        // mencegah hapus jika kategori masih memiliki article
        if ($category->articles()->exists()){
            return redirect()->back()->with('error','kategori tidak dapat dihapus karena masih memiliki article');
        }

        $category->delete();
        return redirect()->back()->with('success', 'Kategori berhasil dihapus');
    }
}