<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\ArticleList;
use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ArticleListBulkActionTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function article(User $author, string $status, ?string $title = null): Article
    {
        $category = Category::firstOrCreate(['slug' => 'berita'], ['name' => 'Berita']);

        return Article::create([
            'title' => $title ?? 'Artikel '.uniqid(),
            'content' => '<p>Isi</p>',
            'status' => $status,
            'author_id' => $author->id,
            'category_id' => $category->id,
        ]);
    }

    public function test_bulk_approve_updates_only_selected_submitted_articles(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $editor = User::factory()->create();
        $editor->assignRole('Editor');
        $writer = User::factory()->create();
        $writer->assignRole('Penulis');

        $submitted = $this->article($writer, 'submitted');
        $other = $this->article($writer, 'submitted');
        $draft = $this->article($writer, 'draft');

        Livewire::actingAs($editor)
            ->test(ArticleList::class)
            ->set('selectedIds', [$submitted->id])
            ->call('bulkApprove')
            ->assertHasNoErrors();

        $this->assertSame('approved', $submitted->fresh()->status->value);
        $this->assertSame('submitted', $other->fresh()->status->value);
        $this->assertSame('draft', $draft->fresh()->status->value);
        $this->assertSame([], Livewire::actingAs($editor)->test(ArticleList::class)->get('selectedIds'));
    }

    public function test_bulk_approve_without_selection_reports_error(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $editor = User::factory()->create();
        $editor->assignRole('Editor');

        Livewire::actingAs($editor)
            ->test(ArticleList::class)
            ->call('bulkApprove')
            ->assertSee('Pilih minimal satu artikel');
    }

    public function test_bulk_approve_skips_articles_the_user_cannot_approve(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $writer = User::factory()->create();
        $writer->assignRole('Penulis'); // no articles.approve
        $other = User::factory()->create();

        $article = $this->article($other, 'submitted');

        Livewire::actingAs($writer)
            ->test(ArticleList::class)
            ->set('selectedIds', [$article->id])
            ->call('bulkApprove')
            ->assertSee('Tidak ada artikel yang dapat diproses');

        $this->assertSame('submitted', $article->fresh()->status->value);
    }

    public function test_bulk_publish_and_archive_apply_lifecycle_columns(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $editor = User::factory()->create();
        $editor->assignRole('Editor');
        $writer = User::factory()->create();
        $writer->assignRole('Penulis');

        $approved = $this->article($writer, 'approved');
        $published = $this->article($writer, 'published');

        Livewire::actingAs($editor)
            ->test(ArticleList::class)
            ->set('selectedIds', [$approved->id])
            ->call('bulkPublish');

        $approved->refresh();
        $this->assertSame('published', $approved->status->value);
        $this->assertNotNull($approved->published_at);

        Livewire::actingAs($editor)
            ->test(ArticleList::class)
            ->set('selectedIds', [$published->id])
            ->call('bulkArchive');

        $published->refresh();
        $this->assertSame('archived', $published->status->value);
        $this->assertNotNull($published->archived_at);
    }

    public function test_bulk_delete_removes_selected_articles_for_super_admin(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');
        $writer = User::factory()->create();
        $writer->assignRole('Penulis');

        $article = $this->article($writer, 'published');
        $kept = $this->article($writer, 'published');

        Livewire::actingAs($admin)
            ->test(ArticleList::class)
            ->set('selectedIds', [$article->id])
            ->call('bulkDelete');

        $this->assertDatabaseMissing('articles', ['id' => $article->id]);
        $this->assertDatabaseHas('articles', ['id' => $kept->id]);
    }

    public function test_select_all_toggle_and_page_change_manage_selection(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $editor = User::factory()->create();
        $editor->assignRole('Editor');
        $writer = User::factory()->create();
        $writer->assignRole('Penulis');

        $a = $this->article($writer, 'draft');
        $b = $this->article($writer, 'draft');

        $component = Livewire::actingAs($editor)->test(ArticleList::class)
            ->call('toggleSelectAll');

        $this->assertEqualsCanonicalizing([$a->id, $b->id], $component->get('selectedIds'));

        $component->call('toggleSelectAll');
        $this->assertSame([], $component->get('selectedIds'));

        $component->set('selectedIds', [$a->id])->call('gotoPage', 2);
        $this->assertSame([], $component->get('selectedIds'));
    }

    public function test_view_wires_row_and_header_checkboxes_to_selection_state(): void
    {
        $this->seed(RolePermissionSeeder::class);
        $editor = User::factory()->create();
        $editor->assignRole('Editor');
        $writer = User::factory()->create();
        $writer->assignRole('Penulis');
        $article = $this->article($writer, 'submitted');

        Livewire::actingAs($editor)
            ->test(ArticleList::class)
            ->assertSeeHtml('wire:model.live="selectedIds"')
            ->assertSeeHtml('wire:click="toggleSelectAll"')
            ->set('selectedIds', [$article->id])
            ->assertSeeHtml('wire:click="bulkApprove"')
            ->assertSeeHtml('wire:click="bulkPublish"')
            ->assertSeeHtml('wire:click="bulkArchive"');
    }
}
