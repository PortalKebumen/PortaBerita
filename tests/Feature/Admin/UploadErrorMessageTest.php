<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\MediaLibraryManager;
use App\Livewire\MediaPicker;
use App\Models\User;
use App\Support\UploadLimit;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UploadErrorMessageTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function admin(): User
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        return $user;
    }

    public function test_upload_limit_is_the_smaller_of_app_and_php_limits(): void
    {
        $this->assertLessThanOrEqual(UploadLimit::APP_MAX_KB * 1024, UploadLimit::maxBytes());
        $this->assertSame('5 MB', UploadLimit::label(5 * 1024 * 1024));
        $this->assertSame('1,5 MB', UploadLimit::label((int) (1.5 * 1024 * 1024)));
    }

    public function test_interrupted_upload_explains_cause_instead_of_generic_message(): void
    {
        Livewire::actingAs($this->admin())
            ->test(MediaLibraryManager::class)
            ->call('_uploadErrored', 'newFiles.1', null, false)
            ->assertHasErrors('newFiles.1')
            ->assertSee('Unggahan terputus sebelum selesai')
            ->assertDontSee('newFile gagal diunggah');
    }

    public function test_upload_rejected_by_server_mentions_size_limit(): void
    {
        $errors = json_encode(['errors' => ['files.0' => ['files.0 gagal diunggah.']]]);

        Livewire::actingAs($this->admin())
            ->test(MediaLibraryManager::class)
            ->call('_uploadErrored', 'newFiles.1', $errors, false)
            ->assertHasErrors('newFiles.1')
            ->assertSee('Server menolak berkas')
            ->assertSee(UploadLimit::label());
    }

    public function test_pressing_upload_without_a_finished_file_gives_clear_instruction(): void
    {
        Livewire::actingAs($this->admin())
            ->test(MediaPicker::class)
            ->call('openFor', 'test')
            ->call('uploadAndSelect')
            ->assertHasErrors(['newFile' => 'required'])
            ->assertSee('Belum ada berkas yang berhasil diunggah');
    }
}
