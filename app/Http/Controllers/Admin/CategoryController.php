<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(): \Illuminate\View\View
    {
        return view('admin.kategori-tag');
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

        Category::firstOrCreate(['name' => $validated['name']], $validated);
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