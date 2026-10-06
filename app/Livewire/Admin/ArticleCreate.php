<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\HandlesArticleMedia;
use App\Models\Article;
use App\Models\ArticleStatus;
use App\Models\Category;
use App\Models\SeoMeta;
use Illuminate\Support\Str;
use Livewire\Component;

class ArticleCreate extends Component
{
    use HandlesArticleMedia;

    public ?Article $article = null;
    public string $title = '';
    public string $slug = '';
    public ?string $excerpt = '';
    public string $content = '';
    public ?int $category_id = null;
    public bool $is_breaking = false;
    public bool $is_advertorial = false;
    public ?string $scheduled_at = null;

    public string $meta_title = '';
    public string $meta_description = '';
    public bool $noindex = false;
    public bool $nofollow = false;

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'slug' => 'required|string|unique:articles,slug|regex:/^[a-z0-9\-]+$/',
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string|min:50',
            'category_id' => 'required|exists:categories,id',
            'is_breaking' => 'nullable|boolean',
            'is_advertorial' => 'nullable|boolean',
            'scheduled_at' => 'nullable|date_format:Y-m-d H:i|after:now',
            'meta_title' => 'nullable|string|max:60',
            'meta_description' => 'nullable|string|max:160',
            'featured_image_id' => 'nullable|exists:media,id',
            'og_image_id' => 'nullable|exists:media,id',
            'noindex' => 'nullable|boolean',
            'nofollow' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'category_id.required' => 'Kategori wajib dipilih.',
            'category_id.exists' => 'Kategori yang dipilih tidak valid.',
            'content.min' => 'Isi artikel minimal 50 karakter.',
        ];
    }

    #[\Livewire\Attributes\Computed]
    public function categories()
    {
        return Category::query()->orderBy('name')->get(['id', 'name']);
    }

    public function updatedTitle(): void
    {
        $this->slug = Str::slug($this->title);
    }

    public function save()
    {
        $this->authorize('create', Article::class);
        $this->validate();

        $article = Article::create([
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => $this->content,
            'status' => ArticleStatus::Draft,
            'author_id' => auth()->id(),
            'category_id' => $this->category_id,
            'is_breaking' => $this->is_breaking,
            'is_advertorial' => $this->is_advertorial,
            'scheduled_at' => $this->scheduled_at
                ? \Carbon\Carbon::createFromFormat('Y-m-d H:i', $this->scheduled_at)
                : null,
        ]);

        $this->syncArticleMedia($article);

        SeoMeta::create([
            'article_id' => $article->id,
            'meta_title' => $this->meta_title ?: null,
            'meta_description' => $this->meta_description ?: null,
            'og_image' => $this->og_image_id ? $article->getFirstMedia('og')?->getUrl() : null,
            'noindex' => $this->noindex,
            'nofollow' => $this->nofollow,
        ]);

        return redirect()
            ->route('admin.artikel.index')
            ->with('success', 'Artikel berhasil dibuat.');
    }

    public function render()
    {
        return view('livewire.admin.article-create', [
            'categories' => $this->categories,
        ]);
    }
}