{{--
    Satu baris item menu di MenuManager.
    Variabel: $item (MenuItem), $isChild, $isFirst, $isLast, $canIndent, $parentHidden, $types
--}}
@php
    $typeBadge = ['page' => 'badge-info', 'category' => 'badge-accent', 'url' => 'badge-success', 'heading' => 'badge-neutral'][$item->type] ?? 'badge-neutral';

    $target = match ($item->type) {
        'page' => $item->page ? $item->page->title.' — /halaman/'.$item->page->slug : null,
        'category' => $item->category ? ($item->category->parent ? $item->category->parent->name.' › ' : '').$item->category->name : null,
        'url' => $item->url,
        default => 'Tanpa tautan',
    };

    $warning = null;
    if (! $item->isVisible()) {
        $warning = match ($item->type) {
            'page' => $item->page ? 'Halaman masih draft — tidak tampil' : 'Halaman sudah dihapus — tidak tampil',
            'category' => 'Kategori sudah dihapus — tidak tampil',
            'url' => 'URL kosong — tidak tampil',
            default => 'Tidak tampil',
        };
    } elseif ($parentHidden) {
        $warning = 'Induknya tersembunyi — tidak tampil';
    }

    $label = $item->displayLabel() ?: '(tanpa label)';
@endphp

<div wire:key="menu-item-{{ $item->id }}"
    class="flex flex-wrap sm:flex-nowrap items-center gap-3 rounded-lg border px-3.5 py-3 {{ $isChild ? 'border-[#E4E8EF] bg-[#F7F8FA]' : 'border-[#CDD3DF] bg-white' }} {{ $warning ? 'opacity-80' : '' }}">
    @can('menus.update')
        <span wire:sort:handle class="-ml-1 flex h-8 w-5 shrink-0 cursor-grab items-center justify-center rounded text-[#A3AAB9] hover:bg-[#EEF1F5] hover:text-[#3B4152] active:cursor-grabbing" title="Seret untuk memindahkan">
            <svg width="12" height="16" viewBox="0 0 12 16" fill="currentColor" aria-hidden="true"><circle cx="3" cy="3" r="1.5"/><circle cx="9" cy="3" r="1.5"/><circle cx="3" cy="8" r="1.5"/><circle cx="9" cy="8" r="1.5"/><circle cx="3" cy="13" r="1.5"/><circle cx="9" cy="13" r="1.5"/></svg>
        </span>
    @endcan
    @if ($isChild)
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="#848CA3" stroke-width="2" class="shrink-0 -ml-1">
            <path d="M5 4v9a3 3 0 003 3h11M15 12l4 4-4 4"/>
        </svg>
    @endif

    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2">
            <span class="font-semibold text-[13.5px] text-[#1B2033] truncate">{{ $label }}</span>
            <span class="{{ $typeBadge }}">{{ $types[$item->type] ?? $item->type }}</span>
            @if ($item->new_tab)
                <span class="text-[11px] text-[#848CA3]" title="Dibuka di tab baru">↗ tab baru</span>
            @endif
        </div>
        <div class="text-[12px] text-[#848CA3] mt-0.5 truncate {{ $item->type === 'url' ? 'font-mono' : '' }}">
            {{ $target ?? '—' }}
        </div>
        @if ($warning)
            <div class="mt-1 inline-flex items-center gap-1 text-[11.5px] font-semibold text-warning">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4M12 17h.01M10.3 3.9L1.8 18a2 2 0 001.7 3h17a2 2 0 001.7-3L13.7 3.9a2 2 0 00-3.4 0z"/></svg>
                {{ $warning }}
            </div>
        @endif
    </div>

    @can('menus.update')
        {{-- Di HP tombol aksi turun ke baris sendiri agar nama item tidak terpotong --}}
        <div class="flex items-center gap-0.5 shrink-0 max-sm:basis-full max-sm:justify-end max-sm:border-t max-sm:border-[#EEF1F5] max-sm:pt-2">
            <button type="button" wire:click="moveUp({{ $item->id }})" @disabled($isFirst)
                class="btn-icon h-8 w-8 text-[#6C7387] hover:text-brand-600 hover:bg-brand-50 disabled:opacity-30 disabled:pointer-events-none" title="Naikkan">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
            </button>
            <button type="button" wire:click="moveDown({{ $item->id }})" @disabled($isLast)
                class="btn-icon h-8 w-8 text-[#6C7387] hover:text-brand-600 hover:bg-brand-50 disabled:opacity-30 disabled:pointer-events-none" title="Turunkan">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M19 12l-7 7-7-7"/></svg>
            </button>
            @if ($isChild)
                <button type="button" wire:click="outdent({{ $item->id }})"
                    class="btn-icon h-8 w-8 text-[#6C7387] hover:text-brand-600 hover:bg-brand-50" title="Jadikan item level atas">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h12M7 8l-4 4 4 4M21 5v14"/></svg>
                </button>
            @else
                <button type="button" wire:click="indent({{ $item->id }})" @disabled(! $canIndent)
                    class="btn-icon h-8 w-8 text-[#6C7387] hover:text-brand-600 hover:bg-brand-50 disabled:opacity-30 disabled:pointer-events-none"
                    title="{{ $canIndent ? 'Jadikan sub-item dari item di atasnya' : 'Tidak dapat dijadikan sub-item' }}">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12H9M17 8l4 4-4 4M3 5v14"/></svg>
                </button>
            @endif
            <button type="button" wire:click="editItem({{ $item->id }})"
                class="btn-icon h-8 w-8 text-[#6C7387] hover:text-brand-600 hover:bg-brand-50" title="Edit Item">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
            </button>
            <button type="button" wire:click="confirmDelete({{ $item->id }})"
                class="btn-icon h-8 w-8 text-danger hover:bg-danger-bg" title="Hapus Item">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
            </button>
        </div>
    @endcan
</div>
