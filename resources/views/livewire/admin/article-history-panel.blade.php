<div class="rounded-card border border-[#E4E8EF] bg-white">
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-[#E4E8EF] px-5 py-4">
        <div class="flex items-center gap-2.5">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-600">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l3 2"/></svg>
            </div>
            <div>
                <h3 class="text-[15px] font-bold text-[#171B28]">Riwayat Versi</h3>
                <p class="text-[12px] text-[#6C7387]">Setiap perubahan tercatat untuk audit: siapa, kapan, dan apa yang diubah.</p>
            </div>
        </div>
        <span class="text-[12px] text-[#848CA3]">{{ $this->versions->count() }} catatan</span>
    </div>

    {{-- Daftar versi --}}
    @if ($this->versions->isEmpty())
        <p class="px-5 py-8 text-center text-[13px] text-[#848CA3]">Belum ada riwayat perubahan untuk artikel ini.</p>
    @else
        <ol class="divide-y divide-[#F1F3F7]">
            @foreach ($this->versions->take($limit) as $version)
                @php
                    $activity = $version['activity'];
                    [$badgeLabel, $badgeClass] = $this->badge($version);
                    $causer = $activity->causer?->name ?? 'Sistem';
                @endphp
                <li wire:key="version-{{ $activity->id }}" class="flex items-start gap-3 px-5 py-3.5">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#F1F3F7] text-[11px] font-bold text-[#3B4152]">
                        {{ strtoupper(mb_substr($causer, 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                            <span class="text-[13px] font-semibold text-[#171B28]">{{ $causer }}</span>
                            <span class="{{ $badgeClass }}">{{ $badgeLabel }}</span>
                            @if ($loop->first)
                                <span class="rounded bg-brand-50 px-1.5 py-0.5 text-[10.5px] font-semibold text-brand-600">Terbaru</span>
                            @endif
                        </div>
                        <p class="mt-0.5 text-[12.5px] text-[#3B4152]">
                            @if ($version['changed'])
                                Mengubah {{ collect($version['changed'])->map(fn ($k) => \App\Services\ArticleHistory::FIELDS[$k])->implode(', ') }}
                            @else
                                {{ $activity->description }}
                            @endif
                        </p>
                        <p class="mt-0.5 text-[11.5px] text-[#848CA3]" title="{{ $activity->created_at->translatedFormat('l, d F Y H:i:s') }}">
                            {{ $activity->created_at->translatedFormat('d M Y, H:i') }} · {{ $activity->created_at->diffForHumans() }}
                            @if ($ip = $activity->properties['ip'] ?? null) · IP {{ $ip }} @endif
                        </p>
                    </div>
                    @if ($version['changed'])
                        <button type="button" wire:click="showVersion({{ $activity->id }})" class="btn-secondary btn-sm shrink-0">Lihat</button>
                    @endif
                </li>
            @endforeach
        </ol>

        @if ($this->versions->count() > $limit)
            <div class="border-t border-[#E4E8EF] px-5 py-3 text-center">
                <button type="button" wire:click="showMore" class="text-[13px] font-semibold text-brand-600 hover:underline">
                    Tampilkan lebih banyak ({{ $this->versions->count() - $limit }} lagi)
                </button>
            </div>
        @endif
    @endif

    {{-- Modal perbandingan versi --}}
    @if ($this->selected)
        @php $sel = $this->selected['activity']; @endphp
        <div class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/40 p-4" wire:keydown.escape.window="closeVersion">
            <div class="flex max-h-[90vh] w-full max-w-3xl flex-col rounded-card bg-white shadow-xl">
                <div class="flex items-start justify-between gap-3 border-b border-[#E4E8EF] px-6 py-4">
                    <div>
                        <h3 class="text-[16px] font-bold text-[#171B28]">Perubahan Versi</h3>
                        <p class="mt-0.5 text-[12px] text-[#6C7387]">
                            {{ $sel->causer?->name ?? 'Sistem' }} · {{ $sel->created_at->translatedFormat('l, d F Y H:i') }}
                        </p>
                    </div>
                    <button type="button" wire:click="closeVersion" class="text-xl leading-none text-[#848CA3] hover:text-[#171B28]" aria-label="Tutup">&times;</button>
                </div>

                <div class="space-y-5 overflow-y-auto px-6 py-5 [&_del]:mr-0.5 [&_del]:rounded-sm [&_del]:px-0.5 [&_del]:bg-red-100 [&_del]:text-red-800 [&_ins]:rounded-sm [&_ins]:px-0.5 [&_ins]:bg-emerald-100 [&_ins]:text-emerald-900 [&_ins]:no-underline">
                    <div class="flex flex-wrap gap-3 text-[11.5px] text-[#6C7387]">
                        <span><del class="px-1">teks dihapus</del></span>
                        <span><ins class="px-1">teks ditambah</ins></span>
                    </div>

                    @foreach ($this->comparison as $row)
                        <section wire:key="cmp-{{ $row['key'] }}">
                            <h4 class="mb-1.5 text-[12px] font-bold uppercase tracking-wide text-[#6C7387]">{{ $row['label'] }}</h4>
                            @isset($row['diff'])
                                <div class="space-y-2 rounded-lg border border-[#E4E8EF] bg-[#FAFBFC] p-3 text-[13.5px] leading-relaxed text-[#171B28]">
                                    @forelse ($row['diff'] as $line)
                                        @if ($line['type'] === 'skip')
                                            <p class="text-center text-[11.5px] italic text-[#848CA3]">… {{ $line['count'] }} paragraf tidak berubah …</p>
                                        @elseif ($line['type'] === 'add')
                                            <p class="border-l-2 border-emerald-500 pl-2"><ins>{!! $line['html'] !!}</ins></p>
                                        @elseif ($line['type'] === 'del')
                                            <p class="border-l-2 border-red-400 pl-2"><del>{!! $line['html'] !!}</del></p>
                                        @elseif ($line['type'] === 'change')
                                            <p class="border-l-2 border-amber-400 pl-2">{!! $line['html'] ?: '<span class="text-[#848CA3]">(kosong)</span>' !!}</p>
                                        @else
                                            <p class="pl-2.5 text-[#6C7387]">{!! $line['html'] !!}</p>
                                        @endif
                                    @empty
                                        <p class="text-[#848CA3]">Hanya format/tag HTML yang berubah.</p>
                                    @endforelse
                                </div>
                            @else
                                <div class="flex flex-wrap items-center gap-2 text-[13px]">
                                    <span class="rounded bg-red-50 px-2 py-1 text-red-800 line-through decoration-red-300">{{ $row['before'] }}</span>
                                    <span class="text-[#848CA3]">&rarr;</span>
                                    <span class="rounded bg-emerald-50 px-2 py-1 text-emerald-900">{{ $row['after'] }}</span>
                                </div>
                            @endisset
                        </section>
                    @endforeach
                </div>

                <div class="flex flex-col gap-3 border-t border-[#E4E8EF] px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                    @if ($confirmingRestore)
                        <p class="text-[12.5px] text-[#3B4152]">
                            {{ $confirmingRestore === 'before' ? 'Perubahan ini akan dibatalkan:' : 'Isi artikel akan dikembalikan seperti setelah versi ini:' }}
                            judul, ringkasan, isi, kategori, dan SEO. Status, slug, dan gambar tidak berubah. Pemulihan juga tercatat di riwayat.
                        </p>
                        <div class="flex shrink-0 gap-2">
                            <button type="button" wire:click="$set('confirmingRestore', null)" class="btn-secondary btn-sm">Batal</button>
                            <button type="button" wire:click="restore" wire:loading.attr="disabled" class="btn-primary btn-sm">
                                <span wire:loading.remove wire:target="restore">Ya, Pulihkan</span>
                                <span wire:loading wire:target="restore">Memulihkan...</span>
                            </button>
                        </div>
                    @else
                        <div class="flex flex-wrap gap-2">
                            @if (in_array('before', $this->restoreModes, true))
                                <button type="button" wire:click="$set('confirmingRestore', 'before')" class="btn-secondary btn-sm">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="inline"><path d="M9 14L4 9l5-5"/><path d="M4 9h11a5 5 0 010 10h-3"/></svg>
                                    Batalkan perubahan ini
                                </button>
                            @endif
                            @if (in_array('after', $this->restoreModes, true))
                                <button type="button" wire:click="$set('confirmingRestore', 'after')" class="btn-secondary btn-sm">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="inline"><path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/></svg>
                                    Pulihkan ke versi ini
                                </button>
                            @endif
                        </div>
                        <button type="button" wire:click="closeVersion" class="btn-secondary btn-sm self-end sm:self-auto">Tutup</button>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
