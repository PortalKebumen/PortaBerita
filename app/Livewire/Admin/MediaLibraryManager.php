<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ExplainsUploadErrors;
use App\Models\LibraryMedia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaLibraryManager extends Component
{
    use ExplainsUploadErrors, WithFileUploads {
        ExplainsUploadErrors::_uploadErrored insteadof WithFileUploads;
    }
    use WithPagination;

    public string $search = '';

    public string $type = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    /** Maksimal berkas dalam satu kali unggah */
    public const MAX_FILES = 20;

    public bool $showUploadModal = false;

    /** Berkas yang sudah selesai diunggah sementara, key = nomor urut dari browser */
    public array $newFiles = [];

    /** Alt text & keterangan per berkas: [nomor => ['alt' => '', 'caption' => '']] */
    public array $newMeta = [];

    public bool $showEditModal = false;
    public ?int $editingMediaId = null;
    public string $editFileName = '';
    public string $editSizeLabel = '';
    public string $editAltText = '';
    public string $editCaption = '';

    public bool $showDeleteModal = false;
    public ?int $deletingMediaId = null;
    public string $deletingFileName = '';

    public bool $editIsImage = false;
    public ?string $editThumbUrl = null;

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

    /** Dipanggil tiap satu berkas selesai diunggah sementara (newFiles.N): cek ukurannya langsung. */
    public function updatedNewFiles($value, $key): void
    {
        if (! is_numeric($key)) {
            return;
        }

        $this->newMeta[$key] ??= ['alt' => '', 'caption' => ''];

        try {
            $this->validateOnly("newFiles.{$key}", ["newFiles.{$key}" => $this->uploadRule()], $this->uploadMessagesFor("newFiles.{$key}"));
        } catch (ValidationException $e) {
            // Berkas tidak lolos dibuang dari daftar; pesan errornya tetap tampil
            unset($this->newFiles[$key], $this->newMeta[$key]);

            throw $e;
        }
    }

    public function removeNewFile(int $key): void
    {
        unset($this->newFiles[$key], $this->newMeta[$key]);
        $this->resetErrorBag("newFiles.{$key}");
    }

    /** Pesan validasi dengan nama berkas, agar jelas berkas mana yang bermasalah. */
    protected function uploadMessagesFor(string $field): array
    {
        $file = data_get($this, $field);
        $name = is_object($file) && method_exists($file, 'getClientOriginalName') ? '"'.$file->getClientOriginalName().'": ' : '';

        return array_map(fn ($message) => $name.$message, $this->uploadValidationMessages($field));
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

        $this->reset(['newFiles', 'newMeta']);
        $this->resetErrorBag();
        $this->showUploadModal = true;
    }

    public function closeUploadModal(): void
    {
        $this->showUploadModal = false;
        $this->reset(['newFiles', 'newMeta']);
        $this->resetErrorBag();
    }

    public function saveUpload(): void
    {
        $this->authorize('media.upload');

        $this->newFiles = array_filter($this->newFiles);

        $rules = [
            'newFiles' => ['required', 'array', 'max:'.self::MAX_FILES],
            'newMeta.*.alt' => 'nullable|string|max:255',
            'newMeta.*.caption' => 'nullable|string|max:255',
        ];
        $messages = [
            'newFiles.required' => 'Belum ada berkas yang berhasil diunggah. Pilih berkas, tunggu sampai semua bertanda "Siap", lalu klik Unggah.',
            'newFiles.max' => 'Maksimal '.self::MAX_FILES.' berkas sekali unggah.',
            'newMeta.*.alt.max' => 'Teks alternatif maksimal 255 karakter.',
            'newMeta.*.caption.max' => 'Keterangan maksimal 255 karakter.',
        ];
        foreach (array_keys($this->newFiles) as $key) {
            $rules["newFiles.{$key}"] = $this->uploadRule();
            $messages += $this->uploadMessagesFor("newFiles.{$key}");
        }
        $this->validate($rules, $messages);

        $saved = 0;
        $failed = [];
        foreach ($this->newFiles as $key => $file) {
            $alt = trim((string) data_get($this->newMeta, "{$key}.alt", ''));
            $caption = trim((string) data_get($this->newMeta, "{$key}.caption", ''));

            try {
                $this->storeUpload($file, $alt, $caption);
                $saved++;
            } catch (\Throwable $e) {
                report($e);
                $failed[] = $file->getClientOriginalName();
            }
        }

        $this->closeUploadModal();
        unset($this->mediaItems);

        if ($failed) {
            $this->dispatch('flash-message', type: 'error', text: "{$saved} media diunggah, ".count($failed).' gagal: '.implode(', ', $failed).'.');

            return;
        }

        $this->dispatch('flash-message', type: 'success', text: $saved === 1 ? 'Media berhasil diunggah.' : "{$saved} media berhasil diunggah.");
    }

    protected function storeUpload($file, string $alt, string $caption): void
    {
        $libraryMedia = LibraryMedia::create([
            'uploaded_by' => Auth::id(),
        ]);

        $media = $libraryMedia
            ->addMedia($file->getRealPath())
            ->usingFileName($file->getClientOriginalName())
            ->withCustomProperties([
                'alt_text' => $alt,
                'caption' => $caption,
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
                'alt_text' => $alt,
                'caption' => $caption,
            ],
        ];
        $activity->save();
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
        $this->editIsImage = str_starts_with($media->mime_type, 'image/');
        $this->editThumbUrl = $this->editIsImage
            ? ($media->hasGeneratedConversion('small') ? $media->getUrl('small') : $media->getUrl())
            : null;
        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->reset(['editingMediaId', 'editFileName', 'editSizeLabel', 'editAltText', 'editCaption', 'editIsImage', 'editThumbUrl']);
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