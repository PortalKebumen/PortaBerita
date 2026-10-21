<div wire:poll.45s.visible
    class="relative inline-block"
    x-data="{ open: false }"
    @click.outside="open = false"
    @keydown.escape.window="open = false">

    <button type="button" @click="open = !open" aria-label="Notifikasi"
        class="relative w-9 h-9 rounded-lg bg-[#F1F3F7] flex items-center justify-center hover:bg-[#E4E8EF] transition-colors">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="#171B28" stroke-width="2">
            <path d="M18 8a6 6 0 00-12 0c0 7-3 9-3 9h18s-3-2-3-9"/>
            <path d="M13.7 21a2 2 0 01-3.4 0"/>
        </svg>
        @if ($this->unreadCount > 0)
            <span class="absolute -top-1 -right-1 min-w-[18px] h-[18px] px-1 rounded-full bg-danger text-white text-[10px] font-bold flex items-center justify-center">
                {{ $this->unreadCount > 99 ? '99+' : $this->unreadCount }}
            </span>
        @endif
    </button>

    <div x-show="open" x-transition.opacity style="display: none;"
        class="fixed inset-x-3 top-[68px] sm:absolute sm:inset-x-auto sm:top-auto sm:right-0 sm:mt-2 sm:w-96 rounded-xl bg-white border border-[#E4E8EF] shadow-lg z-30 overflow-hidden">

        <div class="flex items-center justify-between px-4 py-3 border-b border-[#E4E8EF]">
            <div class="text-[13px] font-semibold">
                Notifikasi
                @if ($this->unreadCount > 0)
                    <span class="ml-1 text-[11px] font-bold text-white bg-brand-600 rounded-full px-1.5 py-0.5">{{ $this->unreadCount }}</span>
                @endif
            </div>
            @if ($this->unreadCount > 0)
                <button type="button" wire:click="markAllAsRead" class="text-[12px] font-semibold text-brand-600 hover:underline">
                    Tandai semua dibaca
                </button>
            @endif
        </div>

        <div class="max-h-[min(420px,calc(100dvh-170px))] overflow-y-auto">
            @forelse ($this->items as $n)
                @php
                    $kind = $n->data['kind'] ?? 'info';
                    $tone = match ($kind) {
                        'success' => 'bg-green-100 text-green-600',
                        'warning' => 'bg-amber-100 text-amber-600',
                        'error' => 'bg-red-100 text-red-600',
                        default => 'bg-blue-100 text-blue-600',
                    };
                    $icon = match ($kind) {
                        'success' => 'M20 6L9 17l-5-5',
                        'warning' => 'M12 9v4M12 17h.01',
                        'error' => 'M18 6L6 18M6 6l12 12',
                        default => 'M12 16v-5M12 8h.01',
                    };
                @endphp
                <button type="button" wire:key="notif-{{ $n->id }}" wire:click="openNotification('{{ $n->id }}')"
                    class="w-full text-left flex gap-3 px-4 py-3 border-b border-[#E4E8EF] last:border-0 hover:bg-[#F7F8FA] transition-colors {{ $n->read_at ? '' : 'bg-brand-50/40' }}">
                    <span class="w-8 h-8 rounded-full {{ $tone }} flex items-center justify-center shrink-0">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="{{ $icon }}"></path>
                        </svg>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="flex items-start justify-between gap-2">
                            <span class="text-[12.5px] {{ $n->read_at ? 'font-medium' : 'font-bold' }} leading-snug">{{ $n->data['title'] ?? 'Notifikasi' }}</span>
                            @unless ($n->read_at)
                                <span class="w-2 h-2 rounded-full bg-brand-600 shrink-0 mt-1.5"></span>
                            @endunless
                        </span>
                        <span class="block text-[12px] text-[#6C7387] mt-0.5 leading-snug line-clamp-2">{{ $n->data['message'] ?? '' }}</span>
                        <span class="block text-[11px] text-[#848CA3] mt-1">{{ $n->created_at->diffForHumans() }}</span>
                    </span>
                </button>
            @empty
                <div class="px-4 py-10 text-center text-[13px] text-[#848CA3]">Belum ada notifikasi.</div>
            @endforelse
        </div>
    </div>
</div>