<div class="space-y-5">

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-3">
            <input type="text" wire:model.live.debounce.400ms="search" placeholder="Cari nama atau email..." class="form-input w-64">
            <select wire:model.live="roleFilter" class="form-select w-56">
                <option value="">Semua Role</option>
                @foreach ($this->roleOptions as $opt)
                    <option value="{{ $opt['name'] }}">{{ $opt['label'] }}</option>
                @endforeach
            </select>
        </div>

        @can('users.create')
            <button type="button" wire:click="openCreate" class="btn-primary shrink-0">+ Tambah Pengguna</button>
        @endcan
    </div>

    <div class="card overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-[#6C7387] border-b border-[#E4E8EF]">
                    <th class="px-4 py-3 font-medium">Nama</th>
                    <th class="px-4 py-3 font-medium">Role</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 font-medium">Login Terakhir</th>
                    <th class="px-4 py-3 font-medium text-right">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($this->users as $user)
                    <tr class="border-b border-[#E4E8EF] last:border-0">
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="h-9 w-9 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center text-xs font-semibold uppercase">
                                    {{ collect(explode(' ', $user->name))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->implode('') }}
                                </div>
                                <div>
                                    <div class="font-medium text-[#1B2033]">{{ $user->name }}</div>
                                    <div class="text-xs text-[#848CA3]">{{ $user->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            @php($roleName = $user->roles->first()?->name)
                            @if ($roleName)
                                <span class="{{ $this->roleBadgeClass($roleName) }}">{{ $this->roleLabel($roleName) }}</span>
                            @else
                                <span class="text-xs text-[#848CA3]">Belum ada role</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @if ($user->is_active)
                                <span class="badge-success">Aktif</span>
                            @else
                                <span class="badge-danger">Nonaktif</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-[#6C7387]">
                            {{ $user->last_login_at ? \Illuminate\Support\Carbon::parse($user->last_login_at)->diffForHumans() : 'Belum pernah login' }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-2">
                                @can('users.update')
                                    <button type="button" wire:click="openEdit({{ $user->id }})" class="btn-icon" title="Edit">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 20h9"></path><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"></path></svg>
                                    </button>
                                @endcan
                                @can('users.delete')
                                    <button type="button" wire:click="confirmDelete({{ $user->id }})" class="btn-icon text-danger" title="Hapus">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 6h18"></path><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path></svg>
                                    </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-[#848CA3]">Tidak ada pengguna ditemukan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $this->users->links() }}</div>

    <div class="modal-overlay {{ $showUserModal ? 'flex' : 'hidden' }} fixed inset-0 bg-black/40 items-center justify-center z-50">
        <div class="bg-white rounded-xl w-full max-w-md">
            <div class="flex items-center justify-between px-6 py-4 border-b border-[#E4E8EF]">
                <h3 class="font-semibold text-[#1B2033]">{{ $editingUserId ? 'Edit Pengguna' : 'Tambah Pengguna' }}</h3>
                <button type="button" wire:click="closeModal" class="text-[#848CA3]">&times;</button>
            </div>
            <form wire:submit="save" class="px-6 py-5 space-y-4">
                <div>
                    <label class="form-label">Nama Lengkap</label>
                    <input type="text" wire:model="name" class="form-input">
                    @error('name') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Email</label>
                    <input type="email" wire:model="email" class="form-input">
                    @error('email') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="form-label">Role</label>
                    <select wire:model="role" class="form-select" {{ $editingUserId && $editingUserId === auth()->id() ? 'disabled' : '' }}>
                        <option value="">Pilih role</option>
                        @foreach ($this->roleOptions as $opt)
                            <option value="{{ $opt['name'] }}">{{ $opt['label'] }}</option>
                        @endforeach
                    </select>
                    @error('role') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
                    @if ($editingUserId && $editingUserId === auth()->id())
                        <p class="text-xs text-[#848CA3] mt-1">Anda tidak bisa mengubah role akun sendiri.</p>
                    @endif
                </div>
                <div>
                    <label class="form-label">Kata Sandi</label>
                    <input type="password" wire:model="password" class="form-input" placeholder="{{ $editingUserId ? 'Kosongkan jika tidak diubah' : '' }}">
                    @error('password') <p class="text-xs text-danger mt-1">{{ $message }}</p> @enderror
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-sm text-[#1B2033]">Akun aktif</span>
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model="isActive" class="peer sr-only">
                        <div class="relative w-11 h-6 bg-[#D6DAE4] peer-checked:bg-brand-600 rounded-full transition-colors duration-200 after:content-[''] after:absolute after:top-0.5 after:left-0.5 after:bg-white after:rounded-full after:h-5 after:w-5 after:shadow after:transition-transform after:duration-200 peer-checked:after:translate-x-5"></div>
                    </label>
                </div>
                <div class="flex justify-end gap-2.5 pt-2">
                    <button type="button" wire:click="closeModal" class="btn-secondary">Batal</button>
                    <button type="submit" class="btn-primary">{{ $editingUserId ? 'Simpan Perubahan' : 'Simpan Pengguna' }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal-overlay {{ $showDeleteModal ? 'flex' : 'hidden' }} fixed inset-0 bg-black/40 items-center justify-center z-50">
        <div class="bg-white rounded-xl w-full max-w-sm p-6 space-y-4">
            <h3 class="font-semibold text-[#1B2033]">Hapus Pengguna</h3>
            <p class="text-sm text-[#6C7387]">Artikel yang sudah ditulis pengguna ini akan tetap ada, namun kepemilikannya dipindah ke akun Redaksi. Tindakan ini tidak bisa dibatalkan.</p>
            <div class="flex justify-end gap-2.5">
                <button type="button" wire:click="closeDeleteModal" class="btn-secondary">Batal</button>
                <button type="button" wire:click="deleteUser" class="btn-danger">Hapus Pengguna</button>
            </div>
        </div>
    </div>
</div>