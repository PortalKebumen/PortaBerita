<?php

namespace App\Livewire\Admin;

use App\Models\Article;
use App\Models\ArticleStatus;
use App\Models\Category;
use Livewire\Component;
use Livewire\WithPagination;

class ArticleList extends Component
{
    use WithPagination;

    public string $search = '';
    public ?string $status = null;
    public ?int $category_id = null;
    public int $perPage = 15;
    
    // Delete modal state
    public bool $showDeleteModal = false;
    public ?int $deleteArticleId = null;
    public string $deleteTitle = '';

    // Publish modal state
    public bool $showPublishModal = false;
    public ?int $publishArticleId = null;
    public string $publishTitle = '';

    // Approve modal state
    public bool $showApproveModal = false;
    public ?int $approveArticleId = null;
    public string $approveTitle = '';

    #[\Livewire\Attributes\Computed]
    public function articles()
    {
        $query = Article::query()
            ->with(['author:id,name', 'category:id,name'])
            ->withCount('revisions');

        // FR-USR-04: Penulis hanya lihat artikelnya sendiri
        if (!auth()->user()->can('articles.view-any')) {
            $query->where('author_id', auth()->id());
        }

        return $query
            ->when($this->search, fn($q) => $q->where('title', 'like', "%{$this->search}%"))
            ->when($this->status, fn($q) => $q->where('status', $this->status))
            ->when($this->category_id, fn($q) => $q->where('category_id', $this->category_id))
            ->latest()
            ->paginate($this->perPage);
    }

    #[\Livewire\Attributes\Computed]
    public function categories()
    {
        return Category::query()->get(['id', 'name']);
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function updatingCategoryId()
    {
        $this->resetPage();
    }

    public function view(Article $article)
    {
        return redirect()->route('admin.artikel.edit', $article);
    }

    public function delete($articleId)
    {
        $article = Article::findOrFail($articleId);
        $this->authorize('delete', $article);
        
        $article->delete();
        
        // Force refresh the articles list
        unset($this->articles);
        
        session()->flash('success', 'Artikel berhasil dihapus.');
    }

    public function openDeleteModal($articleId, $title)
    {
        $this->deleteArticleId = $articleId;
        $this->deleteTitle = $title;
        $this->showDeleteModal = true;
    }

    public function closeDeleteModal()
    {
        $this->showDeleteModal = false;
        $this->deleteArticleId = null;
        $this->deleteTitle = '';
    }

    public function confirmDelete()
    {
        if ($this->deleteArticleId) {
            $this->delete($this->deleteArticleId);
            $this->closeDeleteModal();
        }
    }

    public function openPublishModal($articleId, $title)
    {
        $this->publishArticleId = $articleId;
        $this->publishTitle = $title;
        $this->showPublishModal = true;
    }

    public function closePublishModal()
    {
        $this->showPublishModal = false;
        $this->publishArticleId = null;
        $this->publishTitle = '';
    }

    public function confirmPublish()
    {
        if ($this->publishArticleId) {
            $article = Article::findOrFail($this->publishArticleId);
            $this->authorize('publish', $article);

            $article->update([
                'status' => ArticleStatus::Published->value,
                'published_at' => \Carbon\Carbon::now(),
                'scheduled_at' => null,
            ]);

            activity()
                ->performedOn($article)
                ->causedBy(auth()->user())
                ->log('Artikel dipublikasikan');

            unset($this->articles);
            $this->closePublishModal();
            $this->dispatch('flash-message', type: 'success', text: 'Artikel berhasil dipublikasikan.');
            session()->flash('success', 'Artikel berhasil dipublikasikan.');
        }
    }

    public function openApproveModal($articleId, $title)
    {
        $this->approveArticleId = $articleId;
        $this->approveTitle = $title;
        $this->showApproveModal = true;
    }

    public function closeApproveModal()
    {
        $this->showApproveModal = false;
        $this->approveArticleId = null;
        $this->approveTitle = '';
    }

    public function confirmApprove()
    {
        if ($this->approveArticleId) {
            $article = Article::findOrFail($this->approveArticleId);
            $this->authorize('approve', $article);

            $article->update([
                'status' => ArticleStatus::Approved->value,
            ]);

            activity()
                ->performedOn($article)
                ->causedBy(auth()->user())
                ->log('Artikel disetujui');

            unset($this->articles);
            $this->closeApproveModal();
            $this->dispatch('flash-message', type: 'success', text: 'Artikel berhasil disetujui.');
            session()->flash('success', 'Artikel berhasil disetujui.');
        }
    }

    public function bulkApprove(array $ids)
    {
        $articles = Article::whereIn('id', $ids)->get();

        foreach ($articles as $article) {
            $this->authorize('approve', $article);
            $article->update(['status' => ArticleStatus::Approved->value]);

            activity('artikel')
                ->causedBy(auth()->user())
                ->performedOn($article)
                ->log('Artikel disetujui (bulk)');
        }

        session()->flash('success', 'Artikel berhasil disetujui.');
    }

    public function bulkPublish(array $ids)
    {
        $articles = Article::whereIn('id', $ids)->get();

        foreach ($articles as $article) {
            $this->authorize('publish', $article);
            $article->update([
                'status' => ArticleStatus::Published->value,
                'published_at' => \Carbon\Carbon::now(),
            ]);

            activity('artikel')
                ->causedBy(auth()->user())
                ->performedOn($article)
                ->log('Artikel dipublikasikan (bulk)');
        }

        session()->flash('success', 'Artikel berhasil dipublikasikan.');
    }

    public function bulkArchive(array $ids)
    {
        $articles = Article::whereIn('id', $ids)->get();

        foreach ($articles as $article) {
            $this->authorize('archive', $article);
            $article->update([
                'status' => ArticleStatus::Archived->value,
                'archived_at' => \Carbon\Carbon::now(),
            ]);

            activity('artikel')
                ->causedBy(auth()->user())
                ->performedOn($article)
                ->log('Artikel diarsipkan (bulk)');
        }

        session()->flash('success', 'Artikel berhasil diarsipkan.');
    }

    public function render()
    {
        return view('livewire.admin.article-list', [
            'articles' => $this->articles,
            'categories' => $this->categories,
        ]);
    }
}
