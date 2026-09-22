<?php

namespace App\Livewire\Admin;

use Livewire\Attributes\Computed;
use Livewire\Component;
use Spatie\Permission\Models\Role;

class RoleManager extends Component
{
    public ?int $managingRoleId = null;
    public bool $showPermissionModal = false;
    public array $selectedPermissions = [];

    #[Computed]
    public function roles()
    {
        return Role::withCount('users')->orderBy('name')->get();
    }

    #[Computed]
    public function permissionCatalog(): array
    {
        return config('permission_labels', []);
    }

    public function roleLabel(string $roleName): string
    {
        return config("rbac.role_aliases.{$roleName}", $roleName);
    }

    public function isLockedRole(string $roleName): bool
    {
        return $roleName === 'Super Admin';
    }

    public function openPermissionModal(int $roleId): void
    {
        $this->authorize('roles.update-permissions');

        $role = Role::findOrFail($roleId);

        if ($this->isLockedRole($role->name)) {
            return;
        }

        $this->managingRoleId = $role->id;
        $this->selectedPermissions = $role->permissions->pluck('name')->toArray();
        $this->showPermissionModal = true;
    }

    public function savePermissions(): void
    {
        $this->authorize('roles.update-permissions');

        $role = Role::findOrFail($this->managingRoleId);

        if ($this->isLockedRole($role->name)) {
            $this->dispatch('flash-message', type: 'error', text: 'Izin Super Admin tidak bisa diubah dari sini.');
            $this->closePermissionModal();

            return;
        }

        $role->syncPermissions($this->selectedPermissions);

        $this->closePermissionModal();
        unset($this->roles);
        $this->dispatch('flash-message', type: 'success', text: 'Izin role berhasil diperbarui.');
    }

    public function closePermissionModal(): void
    {
        $this->showPermissionModal = false;
        $this->managingRoleId = null;
        $this->selectedPermissions = [];
    }

    public function render()
    {
        return view('livewire.admin.role-manager');
    }
}