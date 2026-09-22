<?php

namespace Tests\Feature\Models;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ArticleTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_minimal_article_can_be_saved_and_resolves_its_author_and_category(): void
    {
        $author = User::factory()->create();
        $category = Category::create(['name' => 'Kebumen', 'slug' => 'kebumen']);

        $article = Article::create([
            'title' => 'Berita Kebumen',
            'slug' => 'berita-kebumen',
            'excerpt' => 'Ringkasan berita',
            'content' => 'Isi berita Kebumen.',
            'status' => 'draft',
            'author_id' => $author->id,
            'category_id' => $category->id,
        ])->fresh();

        $this->assertDatabaseHas('articles', [
            'id' => $article->id,
            'title' => 'Berita Kebumen',
            'slug' => 'berita-kebumen',
            'excerpt' => 'Ringkasan berita',
            'content' => 'Isi berita Kebumen.',
            'status' => 'draft',
            'author_id' => $author->id,
            'category_id' => $category->id,
        ]);
        $this->assertTrue($article->author->is($author));
        $this->assertTrue($article->category->is($category));
    }
}
