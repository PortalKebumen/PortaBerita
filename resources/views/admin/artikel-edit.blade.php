<x-layouts.admin>
    <livewire:admin.article-edit :article="$article" />

    @can('viewRevisions', $article)
        <div class="mt-6">
            <livewire:admin.article-history-panel :article="$article" />
        </div>
    @endcan
</x-layouts.admin>
