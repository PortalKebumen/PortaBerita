<?php

namespace Tests\Feature\Artikel;

use App\Models\Article;
use App\Models\ArticleStatus;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleCrudTest extends TestCase
{
    use RefreshDatabase;

    protected User $penulis;
    protected User $editor;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);

        $this->penulis = User::factory()->create(['name' => 'Penulis Test']);
        $this->penulis->assignRole('Penulis');

        $this->editor = User::factory()->create(['name' => 'Editor Test']);
        $this->editor->assignRole('Editor');

        $this->category = Category::create(['name' => 'Berita Kebumen', 'slug' => 'berita-kebumen']);
    }

    public function test_penulis_can_create_article_as_draft(): void
    {
        $response = $this->actingAs($this->penulis)
            ->post(route('admin.artikel.store'), [
                'title' => 'Artikel Test Penulis',
                'slug' => 'artikel-test-penulis',
                'excerpt' => 'Ringkasan artikel test',
                'content' => str_repeat('Konten test artikel. ', 10),
                'category_id' => $this->category->id,
                'is_breaking' => false,
                'is_advertorial' => false,
            ]);

        $response->assertRedirect(route('admin.artikel.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('articles', [
            'title' => 'Artikel Test Penulis',
            'status' => ArticleStatus::Draft->value,
            'author_id' => $this->penulis->id,
        ]);
    }

    public function test_penulis_can_view_own_article(): void
    {
        $article = Article::create([
            'title' => 'Test Article',
            'slug' => 'test-article',
            'excerpt' => 'Test excerpt',
            'content' => str_repeat('Content. ', 10),
            'status' => ArticleStatus::Draft->value,
            'author_id' => $this->penulis->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->penulis)
            ->get(route('admin.artikel.edit', $article));

        $response->assertOk();
        $response->assertViewHas('article', $article);
    }

    public function test_penulis_can_update_own_draft(): void
    {
        $article = Article::create([
            'title' => 'Original Title',
            'slug' => 'original-title',
            'excerpt' => 'Original excerpt',
            'content' => str_repeat('Original content. ', 10),
            'status' => ArticleStatus::Draft->value,
            'author_id' => $this->penulis->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->penulis)
            ->put(route('admin.artikel.update', $article), [
                'title' => 'Updated Title',
                'slug' => 'updated-title',
                'excerpt' => 'Updated excerpt',
                'content' => str_repeat('Updated content. ', 10),
                'category_id' => $this->category->id,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_penulis_can_delete_own_draft(): void
    {
        $article = Article::create([
            'title' => 'Draft to Delete',
            'slug' => 'draft-to-delete',
            'excerpt' => 'Test excerpt',
            'content' => str_repeat('Content. ', 10),
            'status' => ArticleStatus::Draft->value,
            'author_id' => $this->penulis->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->penulis)
            ->delete(route('admin.artikel.destroy', $article));

        $response->assertRedirect();
        $this->assertSoftDeleted('articles', ['id' => $article->id]);
    }

    public function test_penulis_cannot_delete_submitted_article(): void
    {
        $article = Article::create([
            'title' => 'Submitted Article',
            'slug' => 'submitted-article',
            'excerpt' => 'Test excerpt',
            'content' => str_repeat('Content. ', 10),
            'status' => ArticleStatus::Submitted->value,
            'author_id' => $this->penulis->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->penulis)
            ->delete(route('admin.artikel.destroy', $article));

        $response->assertForbidden();
        $this->assertNotSoftDeleted('articles', ['id' => $article->id]);
    }

    public function test_article_status_badge_maps_correctly(): void
    {
        $this->assertSame('neutral', ArticleStatus::Draft->badge());
        $this->assertSame('warning', ArticleStatus::Submitted->badge());
        $this->assertSame('info', ArticleStatus::Approved->badge());
        $this->assertSame('danger', ArticleStatus::Rejected->badge());
        $this->assertSame('success', ArticleStatus::Published->badge());
        $this->assertSame('neutral', ArticleStatus::Archived->badge());

        foreach (ArticleStatus::cases() as $status) {
            $this->assertNotSame('primary', $status->badge());
        }
    }
}
