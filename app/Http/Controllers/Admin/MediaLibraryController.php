<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LibraryMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MediaLibraryController extends Controller
{
  
    public function index(Request $request)
    {
        $search = $request->query('search');
        $type = $request->query('type'); // 'image', 'document', atau null (semua)
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $mediaItems = LibraryMedia::query()
            ->whereHas('media', function ($query) use ($search, $type, $dateFrom, $dateTo) {
                if ($search) {
                    $query->where('file_name', 'like', "%{$search}%");
                }
                if ($type === 'image') {
                    $query->where('mime_type', 'like', 'image/%');
                } elseif ($type === 'document') {
                    $query->where('mime_type', 'not like', 'image/%');
                }
                if ($dateFrom) {
                    $query->whereDate('created_at', '>=', $dateFrom);
                }
                if ($dateTo) {
                    $query->whereDate('created_at', '<=', $dateTo);
                }
            })
            ->with('media')
            ->latest()
            ->paginate(20);

        return view('admin.media-library.index', compact('mediaItems'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // maks 10MB, sesuaikan kebutuhan
            'alt_text' => 'nullable|string|max:255',
            'caption' => 'nullable|string|max:255',
        ]);

        $libraryMedia = LibraryMedia::create();

        $media = $libraryMedia
            ->addMediaFromRequest('file')
            ->withCustomProperties([
                'alt_text' => $request->input('alt_text', ''),
                'caption' => $request->input('caption', ''),
            ])
            ->toMediaCollection('library');

        return back()->with('success', 'Media berhasil diunggah.');
    }

    public function update(Request $request, $mediaId)
    {
        $request->validate([
            'alt_text' => 'nullable|string|max:255',
            'caption' => 'nullable|string|max:255',
        ]);

        $media = \Spatie\MediaLibrary\MediaCollections\Models\Media::findOrFail($mediaId);
        $media->setCustomProperty('alt_text', $request->input('alt_text', ''));
        $media->setCustomProperty('caption', $request->input('caption', ''));
        $media->save();

        return back()->with('success', 'Metadata media berhasil diperbarui.');
    }

    public function destroy($mediaId)
    {
        $media = \Spatie\MediaLibrary\MediaCollections\Models\Media::findOrFail($mediaId);

        $media->delete();

        return back()->with('success', 'Media berhasil dihapus.');
    }
}