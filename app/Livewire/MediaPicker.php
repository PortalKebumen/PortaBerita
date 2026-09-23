<?php

namespace App\Livewire;

use App\Models\LibraryMedia;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaPicker extends Component
{
    use WithFileUploads;

    public bool $isOpen = false;
    public string $target = '';
    public string $activeTab = 'upload'; // 'upload' atau 'library'

    public bool $canUpload = false;

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
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $this->canUpload = (bool) $user?->can('media.upload');
        $canViewAny = (bool) $user?->can('media.view-any');
        $canViewOwn = (bool) $user?->can('media.view-own');

        abort_unless($this->canUpload || $canViewAny || $canViewOwn, 403);

        $this->target = $target;
        $this->isOpen = true;
        $this->activeTab = $this->canUpload ? 'upload' : 'library';
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

    protected function canUseMedia(Media $media): bool
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->can('media.view-any')) {
            return true;
        }

        if (! $user->can('media.view-own')) {
            return false;
        }

        $libraryMedia = $media->model_type === LibraryMedia::class ? $media->model : null;

        return $libraryMedia && $libraryMedia->uploaded_by === $user->getKey();
    }

    public function selectMedia(int $mediaId): void
    {
        $media = Media::find($mediaId);

        if (! $media || ! $this->canUseMedia($media)) {
            return;
        }

        $this->selectedMediaId = $mediaId;
    }

    public function confirmSelection(): void
    {
        if (! $this->selectedMediaId) {
            return;
        }

        $media = Media::findOrFail($this->selectedMediaId);

        if (! $this->canUseMedia($media)) {
            $this->selectedMediaId = null;
            $this->dispatch('flash-message', type: 'error', text: 'Anda tidak punya izin menggunakan gambar ini.');
            return;
        }

        $this->dispatch('media-picker-selected', target: $this->target, media: [
            'id' => $media->id,
            'url' => $media->getUrl('medium'),
            'alt_text' => $media->getCustomProperty('alt_text', ''),
        ]);

        $this->close();
    }

    public function uploadAndSelect(): void
    {
        $this->authorize('media.upload');

        $this->validate([
            'newFile' => 'required|file|max:5120',
        ]);

        $libraryMedia = LibraryMedia::create(['uploaded_by' => Auth::id()]);

        $media = $libraryMedia
            ->addMedia($this->newFile->getRealPath())
            ->usingFileName($this->newFile->getClientOriginalName())
            ->withCustomProperties([
                'alt_text' => $this->newAltText,
                'caption' => $this->newCaption,
            ])
            ->toMediaCollection('library');

        $activity = activity('media')
            ->causedBy(Auth::user())
            ->performedOn($libraryMedia)
            ->event('created')
            ->withProperties(['file_name' => $media->file_name, 'size' => $media->size])
            ->log("Media \"{$media->file_name}\" diunggah melalui editor artikel");

        /** @var \Spatie\Activitylog\Models\Activity $activity */
        $activity->attribute_changes = [
            'attributes' => ['file_name' => $media->file_name, 'alt_text' => $this->newAltText, 'caption' => $this->newCaption],
        ];
        $activity->save();

        $this->dispatch('media-picker-selected', target: $this->target, media: [
            'id' => $media->id,
            'url' => $media->getUrl('medium'),
            'alt_text' => $media->getCustomProperty('alt_text', ''),
        ]);

        $this->close();
    }

    public function render()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $query = LibraryMedia::query()
            ->whereHas('media', function ($query) {
                $query->where('mime_type', 'like', 'image/%');
                if ($this->search) {
                    $query->where('file_name', 'like', "%{$this->search}%");
                }
            })
            ->with('media')
            ->latest();

        if (! $user->can('media.view-any')) {
            $query->where('uploaded_by', $user->getKey());
        }

        return view('livewire.media-picker', [
            'mediaItems' => $query->paginate(12),
        ]);
    }
}