<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TagController extends Controller
{
    public function index()
    // menampilkan daftar tag beserta hitungan jumlah artikel terkait
    {
        $tags = Tag::withCount('articles')->latest()->paginate(15);
        return view('admin.tags.index', compact('tags'));
    }

    public function store(Request $request)
    // menambahkan tag baru ke database
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:tags,name'],
        ]);

        // Tag::create($validated); <- kurang efisien
        Tag::firstOrCreate($validated);
        return redirect()->back()->with('success', 'Tag berhasil dibuat');
    }

    public function update(Request $request, Tag $tag)
    // mengupdate tag yang ada di database
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('tags', 'name')->ignore($tag->id)],
        ]);

        $tag->update($validated);
        return redirect()->back()->with('success', 'Tag berhasil diupdate');
    }

    public function destroy(Tag $tag)
    // menghapus tag yang ada di database
    {
        try {
            // lepas asosiasi pivot ke article sebelum menghapus tag
            $tag->articles()->detach();
            $tag->delete();
            
            return redirect()->back()->with('success', 'Tag berhasil dihapus');
        } catch (\Exception $e){
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menghapus tag: ' .
            $e->getMessage());
        }
    }
}