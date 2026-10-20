<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\MediaLibraryManager;
use App\Models\LibraryMedia;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class MediaMultipleUploadTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function admin(): User
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        return $user;
    }

    public function test_multiple_files_are_saved_with_their_own_alt_and_caption(): void
    {
        Storage::fake('public');

        Livewire::actingAs($this->admin())
            ->test(MediaLibraryManager::class)
            ->call('openUploadModal')
            ->set('newFiles.11', UploadedFile::fake()->image('pantai.jpg', 800, 450))
            ->set('newFiles.12', UploadedFile::fake()->image('alun-alun.jpg', 800, 450))
            ->set('newFiles.13', UploadedFile::fake()->image('batal.jpg', 400, 300))
            ->set('newMeta.11.alt', 'Pantai Menganti')
            ->set('newMeta.11.caption', 'Wisatawan di Pantai Menganti.')
            ->set('newMeta.12.caption', 'Alun-alun Kebumen.')
            ->call('removeNewFile', 13)
            ->call('saveUpload')
            ->assertHasNoErrors()
            ->assertSet('showUploadModal', false)
            ->assertDispatched('flash-message', type: 'success', text: '2 media berhasil diunggah.');

        $media = LibraryMedia::with('media')->get()->flatMap->media->keyBy('file_name');
        $this->assertSame(['alun-alun.jpg', 'pantai.jpg'], $media->keys()->sort()->values()->all());
        $this->assertSame('Pantai Menganti', $media['pantai.jpg']->getCustomProperty('alt_text'));
        $this->assertSame('Wisatawan di Pantai Menganti.', $media['pantai.jpg']->getCustomProperty('caption'));
        $this->assertSame('Alun-alun Kebumen.', $media['alun-alun.jpg']->getCustomProperty('caption'));
    }

    public function test_saving_without_any_ready_file_shows_instruction(): void
    {
        Livewire::actingAs($this->admin())
            ->test(MediaLibraryManager::class)
            ->call('openUploadModal')
            ->call('saveUpload')
            ->assertHasErrors(['newFiles' => 'required']);
    }

    public function test_oversized_file_is_dropped_from_list_with_its_name_in_the_message(): void
    {
        Livewire::actingAs($this->admin())
            ->test(MediaLibraryManager::class)
            ->call('openUploadModal')
            // 6 MB: di atas batas aplikasi 5 MB, di bawah batas unggah sementara Livewire 12 MB
            ->set('newFiles.5', UploadedFile::fake()->create('raksasa.jpg', 6 * 1024, 'image/jpeg'))
            ->assertHasErrors('newFiles.5')
            ->assertSet('newFiles', [])
            ->assertSee('raksasa.jpg');
    }
}
