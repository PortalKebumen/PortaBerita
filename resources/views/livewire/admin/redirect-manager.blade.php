<div class="card card-body space-y-5">
    <div>
        <h2 class="text-[15px] font-bold">Pengalihan Alamat (301)</h2>
        <p class="text-[12.5px] text-[#6C7387] mt-1">Mengarahkan alamat lama ke alamat baru agar tautan yang sudah dibagikan atau terindeks Google tidak berujung halaman 404.
            Pengalihan dibuat <strong class="font-semibold">otomatis</strong> saat slug artikel yang sudah terbit diganti. Tambahkan secara manual untuk alamat 404 yang ditemukan di Google Search Console.</p>
    </div>

    @can('settings.update')
        <form wire:submit="save" class="grid grid-cols-1 md:grid-cols-[1fr_1fr_auto] gap-2.5 md:items-start">
            <div class="min-w-0">
                <label class="form-label">Alamat lama</label>
                <input type="text" wire:model="oldUrl" placeholder="/artikel/judul-lama" class="form-input font-mono @error('oldUrl') is-error @enderror">
                @error('oldUrl') <p class="form-error-text">{{ $message }}</p> @enderror
            </div>
            <div class="min-w-0">
                <label class="form-label">Alamat tujuan</label>
                <input type="text" wire:model="newUrl" placeholder="/artikel/judul-baru" class="form-input font-mono @error('newUrl') is-error @enderror">
                @error('newUrl') <p class="form-error-text">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="btn-primary md:mt-[26px]" wire:loading.attr="disabled" wire:target="save">Tambah</button>
        </form>
        <p class="text-[12px] text-[#6C7387] -mt-2">Boleh menempelkan alamat lengkap (https://portalkebumen.com/…); yang disimpan hanya bagian path-nya.</p>
    @endcan

    <div class="flex items-center justify-between gap-3 flex-wrap">
        <input type="text" wire:model.live.debounce.400ms="search" placeholder="Cari alamat..." class="form-input max-w-[280px]">
        <span class="text-[12.5px] text-[#6C7387]">{{ $redirects->total() }} pengalihan</span>
    </div>

    @if ($redirects->isEmpty())
        <p class="text-center text-[13px] text-[#6C7387] py-6">Belum ada pengalihan.</p>
    @else
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-[13px]">
                <thead>
                    <tr class="text-left text-[11px] uppercase tracking-wide text-[#6C7387]">
                        <th class="px-3 py-2 border-b border-[#CDD3DF]">Alamat lama</th>
                        <th class="px-3 py-2 border-b border-[#CDD3DF]">Alamat tujuan</th>
                        <th class="px-3 py-2 border-b border-[#CDD3DF] whitespace-nowrap">Dipakai</th>
                        @can('settings.update') <th class="px-3 py-2 border-b border-[#CDD3DF]"></th> @endcan
                    </tr>
                </thead>
                <tbody>
                    @foreach ($redirects as $redirect)
                        <tr class="border-b border-[#E4E8EF]" wire:key="redirect-{{ $redirect->id }}">
                            <td class="px-3 py-2.5 font-mono break-all">{{ $redirect->old_url }}</td>
                            <td class="px-3 py-2.5 font-mono break-all">{{ $redirect->new_url }}</td>
                            <td class="px-3 py-2.5 font-mono whitespace-nowrap">{{ number_format($redirect->hits, 0, ',', '.') }}×</td>
                            @can('settings.update')
                                <td class="px-3 py-2.5 text-right">
                                    <button type="button" class="btn-outline btn-sm text-danger" wire:click="delete({{ $redirect->id }})"
                                            wire:confirm="Hapus pengalihan {{ $redirect->old_url }}?">Hapus</button>
                                </td>
                            @endcan
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $redirects->links() }}
    @endif
</div>
