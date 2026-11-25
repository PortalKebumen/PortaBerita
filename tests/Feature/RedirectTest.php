<?php

namespace Tests\Feature;

use App\Livewire\Admin\RedirectManager;
use App\Models\Article;
use App\Models\Category;
use App\Models\Redirect;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RedirectTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function article(string $slug, string $status = 'published'): Article
    {
        return Article::create([
            'title' => 'Judul ' . $slug,
            'slug' => $slug,
            'excerpt' => 'Ringkasan',
            'content' => 'Isi berita.',
            'status' => $status,
            'published_at' => $status === 'published' ? now()->subHour() : null,
            'author_id' => User::factory()->create()->id,
            'category_id' => Category::create(['name' => 'Berita ' . $slug])->id,
        ]);
    }

    public function test_changing_slug_of_published_article_redirects_old_and_chained_urls(): void
    {
        $article = $this->article('judul-awal');

        $article->update(['slug' => 'judul-kedua']);
        $article->update(['slug' => 'judul-ketiga']);

        $this->get('/artikel/judul-awal')->assertRedirect('/artikel/judul-ketiga')->assertStatus(301);
        $this->get('/artikel/judul-kedua')->assertRedirect('/artikel/judul-ketiga')->assertStatus(301);
        $this->get('/artikel/judul-ketiga')->assertOk();
        $this->assertSame(1, Redirect::where('old_url', '/artikel/judul-awal')->value('hits'));
    }

    public function test_restoring_old_slug_does_not_create_redirect_loop(): void
    {
        $article = $this->article('judul-awal');

        $article->update(['slug' => 'judul-baru']);
        $article->update(['slug' => 'judul-awal']);

        $this->get('/artikel/judul-awal')->assertOk();
        $this->get('/artikel/judul-baru')->assertRedirect('/artikel/judul-awal');
    }

    public function test_draft_slug_change_creates_no_redirect_and_unknown_url_is_404(): void
    {
        $this->article('draf-lama', 'draft')->update(['slug' => 'draf-baru']);

        $this->assertSame(0, Redirect::count());
        $this->get('/artikel/tidak-ada')->assertNotFound();
        $this->get('/alamat/tidak-ada')->assertNotFound();
    }

    public function test_admin_adds_manual_redirect_for_any_old_path(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        Livewire::actingAs($admin)->test(RedirectManager::class)
            ->set('oldUrl', 'https://portalkebumen.com/berita/lama/?utm_source=wa')
            ->set('newUrl', rtrim(config('app.url'), '/') . '/artikel/baru')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('redirects', ['old_url' => '/berita/lama', 'new_url' => '/artikel/baru']);
        $this->get('/berita/lama')->assertRedirect('/artikel/baru')->assertStatus(301);

        Livewire::actingAs($admin)->test(RedirectManager::class)
            ->set('oldUrl', '/admin/artikel')->set('newUrl', '/')->call('save')
            ->assertHasErrors('oldUrl');
    }
}
