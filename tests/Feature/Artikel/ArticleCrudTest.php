<?php

namespace Tests\Feature\Artikel;

use App\Livewire\Admin\ArticleCreate;
use App\Livewire\Admin\ArticleEdit;
use App\Models\Article;
use App\Models\ArticleStatus;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
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

    protected function makeArticle(array $overrides = []): Article
    {
        return Article::create(array_merge([
            'title' => 'Test Article',
            'slug' => 'test-article',
            'excerpt' => 'Test excerpt',
            'content' => str_repeat('Content. ', 10),
            'status' => ArticleStatus::Draft->value,
            'author_id' => $this->penulis->id,
            'category_id' => $this->category->id,
        ], $overrides));
    }

    public function test_penulis_can_create_article_as_draft(): void
    {
        $this->actingAs($this->penulis);

        Livewire::test(ArticleCreate::class)
            ->set('title', 'Artikel Test Penulis')
            ->set('slug', 'artikel-test-penulis')
            ->set('excerpt', 'Ringkasan artikel test')
            ->set('content', str_repeat('Konten test artikel. ', 10))
            ->set('category_id', $this->category->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('articles', [
            'title' => 'Artikel Test Penulis',
            'status' => ArticleStatus::Draft->value,
            'author_id' => $this->penulis->id,
        ]);
    }

    public function test_penulis_cannot_set_editor_only_flags_on_create(): void
    {
        $this->actingAs($this->penulis);

        Livewire::test(ArticleCreate::class)
            ->set('title', 'Artikel Flag Test')
            ->set('slug', 'artikel-flag-test')
            ->set('excerpt', 'Ringkasan')
            ->set('content', str_repeat('Konten test artikel. ', 10))
            ->set('category_id', $this->category->id)
            ->set('is_breaking', true)
            ->set('is_advertorial', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('articles', [
            'title' => 'Artikel Flag Test',
            'is_breaking' => false,
            'is_advertorial' => false,
        ]);
    }

    public function test_penulis_can_view_own_article(): void
    {
        $article = $this->makeArticle();

        $response = $this->actingAs($this->penulis)
            ->get(route('admin.artikel.edit', $article));

        $response->assertOk();
        $response->assertViewHas('article', $article);
    }

    public function test_penulis_can_update_own_draft(): void
    {
        $article = $this->makeArticle([
            'title' => 'Original Title',
            'slug' => 'original-title',
        ]);

        $this->actingAs($this->penulis);

        Livewire::test(ArticleEdit::class, ['article' => $article])
            ->set('title', 'Updated Title')
            ->set('excerpt', 'Updated excerpt')
            ->set('content', str_repeat('Updated content. ', 10))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'title' => 'Updated Title',
            'slug' => 'updated-title',
        ]);
    }

    public function test_slug_stays_locked_when_published_article_title_changes(): void
    {
        $article = $this->makeArticle([
            'title' => 'Judul Lama',
            'slug' => 'judul-lama',
            'status' => ArticleStatus::Published->value,
        ]);
        $article->forceFill(['published_at' => now()->subDay()])->save();

        $this->actingAs($this->editor);

        Livewire::test(ArticleEdit::class, ['article' => $article])
            ->set('title', 'Judul Baru')
            ->call('save')
            ->assertHasNoErrors();

        $article->refresh();
        $this->assertSame('Judul Baru', $article->title);
        $this->assertSame('judul-lama', $article->slug);
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