<div>
    @if (session('status'))
        <div class="alert-success mb-5">{{ session('status') }}</div>
    @endif

    <div class="alert-info mb-5">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="shrink-0 mt-0.5"><circle cx="12" cy="12" r="9"></circle><path d="M12 16v-5M12 8h.01"></path></svg>
        <div>Activity Log bersifat <strong class="font-semibold">hanya-baca (read-only)</strong> sebagai jejak audit — catatan tidak dapat diedit. Admin hanya dapat melihat detail dan membersihkan log lama untuk menghemat ruang penyimpanan.</div>
    </div>

    <div class="flex flex-col lg:flex-row lg:items-center gap-3 mb-5">
        <div class="relative flex-1 max-w-[300px]">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#848CA3" stroke-width="2" class="absolute left-3.5 top-1/2 -translate-y-1/2"><circle cx="11" cy="11" r="7"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
            <input type="text" wire:model.live.debounce.400ms="search" class="form-input pl-9" placeholder="Cari pengguna atau objek...">
        </div>

        @if ($this->userOptions->isNotEmpty())
            <select wire:model.live="userId" class="form-select w-full lg:w-44">
                <option value="">Semua Pengguna</option>
                @foreach ($this->userOptions as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
        @endif

        <select wire:model.live="actionFilter" class="form-select w-full lg:w-40">
            <option value="">Semua Aksi</option>
            @foreach ($this->actionOptions as $eventOption)
                <option value="{{ $eventOption }}">{{ $this->actionMeta($eventOption)[0] }}</option>
            @endforeach
        </select>

        <input type="date" wire:model.live="date" class="form-input w-full lg:w-40">

        @can('activity-log.purge')
            <button type="button" wire:click="openPurgeModal" class="btn-danger lg:ml-auto shrink-0">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0l-1 14a2 2 0 01-2 2H7a2 2 0 01-2-2L4 6"></path></svg>
                Bersihkan Log Lama
            </button>
        @endcan
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[760px] border-collapse">
                <thead>
                    <tr>
                        <th class="text-left text-[11px] uppercase tracking-wide text-[#848CA3] font-bold px-4 py-2.5 border-b border-[#CDD3DF] whitespace-nowrap">Waktu</th>
                        <th class="text-left text-[11px] uppercase tracking-wide text-[#848CA3] font-bold px-4 py-2.5 border-b border-[#CDD3DF]">Pengguna</th>
                        <th class="text-left text-[11px] uppercase tracking-wide text-[#848CA3] font-bold px-3 py-2.5 border-b border-[#CDD3DF]">Aksi</th>
                        <th class="text-left text-[11px] uppercase tracking-wide text-[#848CA3] font-bold px-4 py-2.5 border-b border-[#CDD3DF]">Objek</th>
                        <th class="text-left text-[11px] uppercase tracking-wide text-[#848CA3] font-bold px-4 py-2.5 border-b border-[#CDD3DF]">IP</th>
                        <th class="text-left text-[11px] uppercase tracking-wide text-[#848CA3] font-bold px-3 py-2.5 border-b border-[#CDD3DF]"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($this->logs as $log)
                        @php [$actionLabel, $actionClass] = $this->actionMeta($log->event); @endphp
                        <tr class="border-b border-[#E4E8EF]">
                            <td class="px-4 py-3 text-[12.5px] font-mono text-[#6C7387] whitespace-nowrap">{{ $log->created_at->translatedFormat('d M Y, H:i') }}</td>
                            <td class="px-4 py-3 text-sm font-semibold whitespace-nowrap">{{ $log->causer->name ?? 'Sistem' }}</td>
                            <td class="px-3 py-3 whitespace-nowrap"><span class="{{ $actionClass }}"><span class="badge-dot"></span>{{ $actionLabel }}</span></td>
                            <td class="px-4 py-3 text-sm">{{ $this->subjectLabel($log) }}</td>
                            <td class="px-4 py-3 text-[12px] font-mono text-[#848CA3] whitespace-nowrap">{{ $log->properties['ip'] ?? '—' }}</td>
                            <td class="px-2 py-3">
                                <button type="button" wire:click="showDetail({{ $log->id }})" class="text-[12.5px] font-semibold text-brand-600">Detail</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-[13px] text-[#848CA3]">Tidak ada entri log yang cocok dengan filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-4 border-t border-[#CDD3DF]">
            {{ $this->logs->links() }}
        </div>
    </div>

    @if ($this->selectedLog)
        <div class="modal-overlay fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40">
            <div class="bg-white rounded-card max-w-[520px] w-full p-6 shadow-xl">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-[16px] font-bold">Detail Aktivitas</h3>
                    <button type="button" wire:click="closeDetail" class="btn-icon bg-[#F1F3F7]" aria-label="Tutup">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"></path></svg>
                    </button>
                </div>
                @php [$dLabel] = $this->actionMeta($this->selectedLog->event); @endphp
                <dl class="grid grid-cols-3 gap-y-3 text-[13px] mb-4">
                    <dt class="text-[#848CA3]">Waktu</dt><dd class="col-span-2 font-medium">{{ $this->selectedLog->created_at->translatedFormat('d M Y, H:i') }}</dd>
                    <dt class="text-[#848CA3]">Pengguna</dt><dd class="col-span-2 font-medium">{{ $this->selectedLog->causer->name ?? 'Sistem' }}</dd>
                    <dt class="text-[#848CA3]">Aksi</dt><dd class="col-span-2 font-medium">{{ $dLabel }} — {{ $this->selectedLog->description }}</dd>
                    <dt class="text-[#848CA3]">Objek</dt><dd class="col-span-2 font-medium">{{ $this->subjectLabel($this->selectedLog) }}</dd>
                    <dt class="text-[#848CA3]">Alamat IP</dt><dd class="col-span-2 font-mono">{{ $this->selectedLog->properties['ip'] ?? '—' }}</dd>
                </dl>
                @php
                    $changes = $this->selectedLog->attribute_changes ?? [];
                    $extraProps = collect($this->selectedLog->properties ?? [])->except('ip');
                @endphp
                @if (!empty($changes))
                    <div class="text-[12px] font-semibold text-[#6C7387] mb-2">Perubahan Data (properties)</div>
                    <pre class="code-block">{{ json_encode($changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                @elseif ($extraProps->isNotEmpty())
                    <div class="text-[12px] font-semibold text-[#6C7387] mb-2">Detail Tambahan</div>
                    <pre class="code-block">{{ json_encode($extraProps, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                @endif
                <div class="flex justify-end mt-5">
                    <button type="button" wire:click="closeDetail" class="btn-secondary">Tutup</button>
                </div>
            </div>
        </div>
    @endif

    @if ($showPurgeModal)
        <div class="modal-overlay fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/40">
            <div class="bg-white rounded-card max-w-[440px] w-full p-6 shadow-xl">
                <div class="w-11 h-11 rounded-full bg-danger-bg text-danger flex items-center justify-center mb-4">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0l-1 14a2 2 0 01-2 2H7a2 2 0 01-2-2L4 6"></path></svg>
                </div>
                <h3 class="text-[16px] font-bold mb-1.5">Bersihkan log lama?</h3>
                <p class="text-[13px] text-[#6C7387] mb-4">Hapus permanen seluruh entri log yang lebih lama dari periode berikut:</p>
                <select wire:model="purgeOlderThan" class="form-select mb-5">
                    <option value="3months">Lebih dari 3 bulan</option>
                    <option value="6months">Lebih dari 6 bulan</option>
                    <option value="1year">Lebih dari 1 tahun</option>
                </select>
                <div class="flex justify-end gap-2.5">
                    <button type="button" wire:click="closePurgeModal" class="btn-secondary">Batal</button>
                    <button type="button" wire:click="purgeOldLogs" wire:confirm="Yakin ingin menghapus permanen log lama ini?" class="btn-danger">Ya, Bersihkan</button>
                </div>
            </div>
        </div>
    @endif
</div>