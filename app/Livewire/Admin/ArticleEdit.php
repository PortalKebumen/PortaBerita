<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\HandlesArticleMedia;
use App\Models\Article;
use App\Models\ArticleStatus;
use App\Models\Category;
use App\Models\SeoMeta;
use Illuminate\Support\Str;
use Livewire\Component;

class ArticleEdit extends Component
{
    use HandlesArticleMedia;

    public Article $article;

    public string $title = '';
    public string $slug = '';
    public ?string $excerpt = '';
    public string $content = '';
    public ?int $category_id = null;
    public bool $is_breaking = false;
    public bool $is_advertorial = false;
    public ?string $scheduled_at = null;
    public string $status = '';

    public string $meta_title = '';
    public string $meta_description = '';
    public bool $noindex = false;
    public bool $nofollow = false;

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'slug' => 'required|string|unique:articles,slug,' . $this->article->id . '|regex:/^[a-z0-9\-]+$/',
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
            'content.min' => 'Isi artikel minimal 50 karakter.',
        ];
    }

    #[\Livewire\Attributes\Computed]
    public function categories()
    {
        return Category::query()->orderBy('name')->get(['id', 'name']);
    }

    #[\Livewire\Attributes\Computed]
    public function revisions()
    {
        return $this->article->revisions()->with('editor:id,name')->get();
    }

    public function mount(Article $article): void
    {
        $this->authorize('update', $article);

        $this->article = $article;
        $this->title = $article->title;
        $this->slug = $article->slug;
        $this->excerpt = $article->excerpt;
        $this->content = $article->content ?? '';
        $this->category_id = $article->category_id;
        $this->is_breaking = (bool) $article->is_breaking;
        $this->is_advertorial = (bool) $article->is_advertorial;
        $this->scheduled_at = $article->scheduled_at?->format('Y-m-d H:i');
        $this->status = $article->status->value;

        $featured = $article->getFirstMedia('featured');
        $this->featured_image_id = $featured?->id;
        $this->featured_image_url = $featured?->getUrl('medium');

        $og = $article->getFirstMedia('og');
        $this->og_image_id = $og?->id;
        $this->og_image_url = $og?->getUrl('medium');

        if ($article->seoMeta) {
            $this->meta_title = $article->seoMeta->meta_title ?? '';
            $this->meta_description = $article->seoMeta->meta_description ?? '';
            $this->noindex = (bool) $article->seoMeta->noindex;
            $this->nofollow = (bool) $article->seoMeta->nofollow;
        }
    }

    public function updatedTitle(): void
    {
        $this->slug = Str::slug($this->title);
    }

    public function save()
    {
        $this->authorize('update', $this->article);
        $this->validate();

        $this->article->update([
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => $this->content,
            'category_id' => $this->category_id,
            'is_breaking' => $this->is_breaking,
            'is_advertorial' => $this->is_advertorial,
            'scheduled_at' => $this->scheduled_at
                ? \Carbon\Carbon::createFromFormat('Y-m-d H:i', $this->scheduled_at)
                : null,
        ]);

        $this->syncArticleMedia($this->article);

        $seoMeta = $this->article->seoMeta ?: new SeoMeta(['article_id' => $this->article->id]);

        $seoMeta->fill([
            'article_id' => $this->article->id,
            'meta_title' => $this->meta_title ?: null,
            'meta_description' => $this->meta_description ?: null,
            'og_image' => $this->og_image_id ? $this->article->getFirstMedia('og')?->getUrl() : null,
            'noindex' => $this->noindex,
            'nofollow' => $this->nofollow,
        ]);

        $seoMeta->save();

        return redirect()
            ->route('admin.artikel.index')
            ->with('success', 'Artikel berhasil diperbarui.');
    }

    public function render()
    {
        return view('livewire.admin.article-edit', [
            'categories' => $this->categories,
            'revisions' => $this->revisions,
        ]);
    }
}