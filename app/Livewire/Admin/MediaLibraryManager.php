<?php

namespace App\Livewire\Admin;

use App\Models\LibraryMedia;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaLibraryManager extends Component
{
    use WithFileUploads;
    use WithPagination;

    public string $search = '';

    public string $type = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public bool $showUploadModal = false;
    public $newFile = null;
    public string $newAltText = '';
    public string $newCaption = '';

    public bool $showEditModal = false;
    public ?int $editingMediaId = null;
    public string $editFileName = '';
    public string $editSizeLabel = '';
    public string $editAltText = '';
    public string $editCaption = '';

    public bool $showDeleteModal = false;
    public ?int $deletingMediaId = null;
    public string $deletingFileName = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingType(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function updatedNewFile(): void
    {
        $this->validateOnly('newFile', [
            'newFile' => 'required|file|max:10240',
        ]);
    }

    #[Computed]
    public function mediaItems()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $query = LibraryMedia::query()
            ->whereHas('media', function ($query) {
                if ($this->search) {
                    $query->where('file_name', 'like', "%{$this->search}%");
                }
                if ($this->type === 'image') {
                    $query->where('mime_type', 'like', 'image/%');
                } elseif ($this->type === 'document') {
                    $query->where('mime_type', 'not like', 'image/%');
                }
                if ($this->dateFrom) {
                    $query->whereDate('created_at', '>=', $this->dateFrom);
                }
                if ($this->dateTo) {
                    $query->whereDate('created_at', '<=', $this->dateTo);
                }
            })
            ->with('media')
            ->latest();

        if (! $user->can('media.view-any')) {
            $query->where('uploaded_by', $user->getKey());
        }

        return $query->paginate(20);
    }

    public function canManage(LibraryMedia $libraryMedia, string $ownAbility, string $anyAbility): bool
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->can($anyAbility)) {
            return true;
        }

        return $user->can($ownAbility) && $libraryMedia->uploaded_by === $user->getKey();
    }

    public function openUploadModal(): void
    {
        $this->authorize('media.upload');

        $this->reset(['newFile', 'newAltText', 'newCaption']);
        $this->showUploadModal = true;
    }

    public function closeUploadModal(): void
    {
        $this->showUploadModal = false;
        $this->reset(['newFile', 'newAltText', 'newCaption']);
    }

    public function saveUpload(): void
    {
        $this->authorize('media.upload');

        $this->validate([
            'newFile' => 'required|file|max:10240',
            'newAltText' => 'nullable|string|max:255',
            'newCaption' => 'nullable|string|max:255',
        ]);

        $libraryMedia = LibraryMedia::create([
            'uploaded_by' => Auth::id(),
        ]);

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
            ->withProperties([
                'file_name' => $media->file_name,
                'size' => $media->size,
            ])
            ->log("Media \"{$media->file_name}\" diunggah");

        /** @var \Spatie\Activitylog\Models\Activity $activity */
        $activity->attribute_changes = [
            'attributes' => [
                'file_name' => $media->file_name,
                'alt_text' => $this->newAltText,
                'caption' => $this->newCaption,
            ],
        ];
        $activity->save();

        $this->closeUploadModal();
        unset($this->mediaItems);
        $this->dispatch('flash-message', type: 'success', text: 'Media berhasil diunggah.');
    }

    public function openEditModal(int $mediaId): void
    {
        $media = Media::findOrFail($mediaId);
        $libraryMedia = $this->resolveOwningLibraryMedia($media);

        if (! $this->canManage($libraryMedia, 'media.update-own', 'media.update-any')) {
            $this->dispatch('flash-message', type: 'error', text: 'Anda tidak punya izin mengubah media ini.');

            return;
        }

        $this->editingMediaId = $media->id;
        $this->editFileName = $media->file_name;
        $this->editSizeLabel = $this->formatSize($media->size);
        $this->editAltText = $media->getCustomProperty('alt_text', '');
        $this->editCaption = $media->getCustomProperty('caption', '');
        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->reset(['editingMediaId', 'editFileName', 'editSizeLabel', 'editAltText', 'editCaption']);
    }

    public function updateMedia(): void
    {
        $this->validate([
            'editAltText' => 'nullable|string|max:255',
            'editCaption' => 'nullable|string|max:255',
        ]);

        $media = Media::findOrFail($this->editingMediaId);
        $libraryMedia = $this->resolveOwningLibraryMedia($media);

        if (! $this->canManage($libraryMedia, 'media.update-own', 'media.update-any')) {
            abort(403);
        }

        $oldAltText = $media->getCustomProperty('alt_text', '');
        $oldCaption = $media->getCustomProperty('caption', '');

        $media->setCustomProperty('alt_text', $this->editAltText);
        $media->setCustomProperty('caption', $this->editCaption);
        $media->save();

        $activity = activity('media')
            ->causedBy(Auth::user())
            ->performedOn($libraryMedia)
            ->event('updated')
            ->withProperties(['file_name' => $media->file_name])
            ->log("Metadata media \"{$media->file_name}\" diperbarui");

        /** @var \Spatie\Activitylog\Models\Activity $activity */
        $activity->attribute_changes = [
            'old' => ['alt_text' => $oldAltText, 'caption' => $oldCaption],
            'attributes' => ['alt_text' => $this->editAltText, 'caption' => $this->editCaption],
        ];
        $activity->save();

        $this->closeEditModal();
        unset($this->mediaItems);
        $this->dispatch('flash-message', type: 'success', text: 'Metadata media berhasil diperbarui.');
    }

    public function openDeleteModal(int $mediaId): void
    {
        $media = Media::findOrFail($mediaId);
        $libraryMedia = $this->resolveOwningLibraryMedia($media);

        if (! $this->canManage($libraryMedia, 'media.delete-own', 'media.delete-any')) {
            $this->dispatch('flash-message', type: 'error', text: 'Anda tidak punya izin menghapus media ini.');

            return;
        }

        $this->deletingMediaId = $media->id;
        $this->deletingFileName = $media->file_name;
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->reset(['deletingMediaId', 'deletingFileName']);
    }

    public function deleteMedia(): void
    {
        $media = Media::findOrFail($this->deletingMediaId);
        $libraryMedia = $this->resolveOwningLibraryMedia($media);

        if (! $this->canManage($libraryMedia, 'media.delete-own', 'media.delete-any')) {
            abort(403);
        }

        $fileName = $media->file_name;

        $media->delete();

        $activity = activity('media')
            ->causedBy(Auth::user())
            ->performedOn($libraryMedia)
            ->event('deleted')
            ->withProperties(['file_name' => $fileName])
            ->log("Media \"{$fileName}\" dihapus");

        /** @var \Spatie\Activitylog\Models\Activity $activity */
        $activity->attribute_changes = [
            'old' => ['file_name' => $fileName],
        ];
        $activity->save();

        $this->closeDeleteModal();
        unset($this->mediaItems);
        $this->dispatch('flash-message', type: 'success', text: 'Media berhasil dihapus.');
    }

    protected function resolveOwningLibraryMedia(Media $media): LibraryMedia
    {
        abort_unless($media->model_type === LibraryMedia::class, 404);

        return $media->model;
    }

    protected function formatSize(int $bytes): string
    {
        $kb = $bytes / 1024;

        return $kb >= 1024
            ? round($kb / 1024, 1).' MB'
            : round($kb).' KB';
    }

    public function render()
    {
        return view('livewire.admin.media-library-manager');
    }
}