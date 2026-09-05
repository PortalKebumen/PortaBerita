<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index()
    {
        // ambil kategori utama beserta sub kategori children dan hitungan jumlah article
        $categories = Category::whereNull('parent_id')
        ->with(['children' => fn($q) => $q->withCount('articles')])
        ->withCount('articles')
        ->latest()
        ->paginate(10);

        // ambil daftar kategori utama untuk pilihan dropdown parent di form
        $parentCategories = Category::whereNull('parent_id')->get();
        return view('admin.categories.index', compact('categories','parentCategories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:categories,name'],
            'parent_id' => ['nullable', 'exits:categories,id'],
        ]);

        // Category::create($validated); <-- kurang efisien
        Category::firstOrCreate($validated);
        return redirect()->back()->with('success', 'Kategori berhasil dibuat');
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('categories', 'name')->ignore($category->id)],
            'parent_id' => ['nullable', 'exits:categories,id'],
        ]);

        // mencegah kategori menjadikan dirinya sendiri sebagai parent
        if ($validated['parent_id'] == $category->id){
            return redirect()->back()->with('error', 'kategori tidak boleh menjadikan dirinya sendiri sebagai parent');
        }

        $category->update($validated);
        return redirect()->back()->with('success', 'Kategori berhasil diupdate');
    }

    public function destroy(Category $category)
    {
        // mencegah hapus jika kategori masih memiliki article
        if ($category->articles()->exits()){
            return redirect()->back()->with('error','kategori tidak dapat dihapus karena masih memiliki article');
        }

        $category->delete();
        return redirect()->back()->with('success', 'Kategori berhasil dihapus');
    }
}
