<?php

namespace App\Livewire;

use App\Models\LibraryMedia;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\On;

class MediaPicker extends Component
{
    use WithFileUploads;

    public bool $isOpen = false;
    public string $target = '';
    public string $activeTab = 'upload'; // 'upload' atau 'library'

    // Tab Pustaka Media
    public string $search = '';
    public ?int $selectedMediaId = null;

    // Tab Unggah Baru
    public $newFile = null;
    public string $newAltText = '';
    public string $newCaption = '';

    #[On('open-media-picker')]
    public function openFor(string $target): void
    {
        $this->target = $target;
        $this->isOpen = true;
        $this->activeTab = 'upload';
        $this->selectedMediaId = null;
        $this->resetUploadState();
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->resetUploadState();
    }

    protected function resetUploadState(): void
    {
        $this->newFile = null;
        $this->newAltText = '';
        $this->newCaption = '';
    }

    public function selectMedia(int $mediaId): void
    {
        $this->selectedMediaId = $mediaId;
    }

    public function confirmSelection(): void
    {
        if (! $this->selectedMediaId) {
            return;
        }

        $media = \Spatie\MediaLibrary\MediaCollections\Models\Media::findOrFail($this->selectedMediaId);

        $this->dispatch('media-picker-selected', target: $this->target, media: [
            'id' => $media->id,
            'url' => $media->getUrl('medium'),
            'alt_text' => $media->getCustomProperty('alt_text', ''),
        ]);

        $this->close();
    }

    public function uploadAndSelect(): void
    {
        $this->validate([
            'newFile' => 'required|file|max:10240',
        ]);

        $libraryMedia = LibraryMedia::create();

        $media = $libraryMedia
            ->addMedia($this->newFile->getRealPath())
            ->usingFileName($this->newFile->getClientOriginalName())
            ->withCustomProperties([
                'alt_text' => $this->newAltText,
                'caption' => $this->newCaption,
            ])
            ->toMediaCollection('library');

        $this->dispatch('media-picker-selected', target: $this->target, media: [
            'id' => $media->id,
            'url' => $media->getUrl('medium'),
            'alt_text' => $media->getCustomProperty('alt_text', ''),
        ]);

        $this->close();
    }

    public function render()
    {
        $mediaItems = LibraryMedia::query()
            ->whereHas('media', function ($query) {
                if ($this->search) {
                    $query->where('file_name', 'like', "%{$this->search}%")
                        ->where('mime_type', 'like', 'image/%');
                } else {
                    $query->where('mime_type', 'like', 'image/%');
                }
            })
            ->with('media')
            ->latest()
            ->paginate(12);

        return view('livewire.media-picker', compact('mediaItems'));
    }
}