<div class="space-y-5">

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach ($this->roles as $role)
            <div class="card p-5 space-y-3">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-[#1B2033]">{{ $this->roleLabel($role->name) }}</h3>
                    <span class="badge-neutral">{{ $role->users_count }} pengguna</span>
                </div>
                <p class="text-xs text-[#848CA3]">
                    @if ($this->isLockedRole($role->name))
                        Akses penuh ke seluruh fitur. Izin role ini tidak bisa diubah.
                    @else
                        Kelola izin akses fitur untuk role {{ $this->roleLabel($role->name) }}.
                    @endif
                </p>
                @can('roles.update-permissions')
                    @if ($this->isLockedRole($role->name))
                        <button type="button" class="btn-secondary w-full opacity-50 cursor-not-allowed" disabled>Kelola Izin</button>
                    @else
                        <button type="button" wire:click="openPermissionModal({{ $role->id }})" class="btn-secondary w-full">Kelola Izin</button>
                    @endif
                @endcan
            </div>
        @endforeach
    </div>

    <div class="modal-overlay {{ $showPermissionModal ? 'flex' : 'hidden' }} fixed inset-0 bg-black/40 items-center justify-center z-50">
        <div class="bg-white rounded-xl w-full max-w-2xl max-h-[85vh] flex flex-col">
            <div class="flex items-center justify-between px-6 py-4 border-b border-[#E4E8EF]">
                <h3 class="font-semibold text-[#1B2033]">Kelola Izin</h3>
                <button type="button" wire:click="closePermissionModal" class="text-[#848CA3]">&times;</button>
            </div>
            <div class="px-6 py-5 overflow-y-auto space-y-5">
                @foreach ($this->permissionCatalog as $group)
                    <div>
                        <div class="text-sm font-semibold text-[#1B2033] mb-2">{{ $group['label'] }}</div>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($group['permissions'] as $slug => $label)
                                <label class="flex items-center gap-2 px-3 py-1.5 rounded-lg border border-[#E4E8EF] text-xs">
                                    <input type="checkbox" wire:model="selectedPermissions" value="{{ $slug }}" class="form-checkbox">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="flex justify-end gap-2.5 px-6 py-4 border-t border-[#E4E8EF]">
                <button type="button" wire:click="closePermissionModal" class="btn-secondary">Batal</button>
                <button type="button" wire:click="savePermissions" class="btn-primary">Simpan Izin</button>
            </div>
        </div>
    </div>
</div>