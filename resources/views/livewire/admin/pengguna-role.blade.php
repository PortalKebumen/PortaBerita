<x-layouts.admin title="Pengguna & Role" :breadcrumbs="[['label' => 'Pengguna & Role']]">
    <div x-data="{ tab: 'pengguna' }" class="space-y-5">
        <div class="flex gap-2 border-b border-[#E4E8EF]">
            <button type="button" @click="tab = 'pengguna'" :class="tab === 'pengguna' ? 'is-active' : ''" class="tab-btn">Pengguna</button>
            <button type="button" @click="tab = 'role'" :class="tab === 'role' ? 'is-active' : ''" class="tab-btn">Role & Izin</button>
        </div>

        <div x-show="tab === 'pengguna'">
            <livewire:admin.user-manager />
        </div>
        <div x-show="tab === 'role'">
            <livewire:admin.role-manager />
        </div>
    </div>
</x-layouts.admin>