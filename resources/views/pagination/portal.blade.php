<div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 border-t border-[#CDD3DF]">
    <span class="text-[12.5px] text-[#6C7387]">
        Menampilkan {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} dari {{ number_format($paginator->total(), 0, ',', '.') }} artikel
    </span>
    @if ($paginator->hasPages())
        <div class="flex items-center gap-1.5">
            @if ($paginator->onFirstPage())
                <button type="button" class="btn-icon bg-[#F1F3F7] disabled:opacity-40" disabled aria-label="Sebelumnya">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"></path></svg>
                </button>
            @else
                <button type="button" wire:click="previousPage('page')" class="btn-icon bg-[#F1F3F7]" aria-label="Sebelumnya">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"></path></svg>
                </button>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-1 text-[#848CA3]">{{ $element }}</span>
                @elseif (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <button type="button" class="w-9 h-9 rounded-lg bg-brand-600 text-white text-[13px] font-semibold">{{ $page }}</button>
                        @else
                            <button type="button" wire:click="gotoPage({{ $page }}, 'page')" class="w-9 h-9 rounded-lg hover:bg-[#F1F3F7] text-[13px] font-semibold">{{ $page }}</button>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <button type="button" wire:click="nextPage('page')" class="btn-icon bg-[#F1F3F7]" aria-label="Berikutnya">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"></path></svg>
                </button>
            @else
                <button type="button" class="btn-icon bg-[#F1F3F7] disabled:opacity-40" disabled aria-label="Berikutnya">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"></path></svg>
                </button>
            @endif
        </div>
    @endif
</div>