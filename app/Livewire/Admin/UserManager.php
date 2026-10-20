<?php

namespace App\Livewire\Admin;

use App\Models\Article;
use App\Models\User;
use App\Services\AccountCredentials;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;

class UserManager extends Component
{
    use WithPagination;

    // #[Url(history: true)]
    public string $search = '';

    // #[Url(history: true)]
    public string $roleFilter = '';

    public bool $showUserModal = false;

    public ?int $editingUserId = null;

    public string $name = '';

    public string $email = '';

    public string $role = '';

    public string $password = '';

    public bool $isActive = true;

    public bool $showDeleteModal = false;

    public ?int $deletingUserId = null;

    /** Akun baru: kirim info login (email + kata sandi sementara) ke email pengguna */
    public bool $sendCredentials = true;

    public bool $showCredentialsModal = false;

    public ?int $credentialsUserId = null;

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($this->editingUserId),
            ],
            'role' => ['required', Rule::exists('roles', 'name')],
            // Akun baru boleh tanpa kata sandi bila info login dikirim: sistem membuatkan kata sandi sementara
            'password' => [$this->editingUserId || $this->sendCredentials ? 'nullable' : 'required', 'min:8'],
            'isActive' => ['boolean'],
        ];
    }

    protected array $messages = [
        'name.required' => 'Nama lengkap wajib diisi.',
        'email.required' => 'Email wajib diisi.',
        'email.email' => 'Format email tidak valid.',
        'email.unique' => 'Email ini sudah dipakai pengguna lain.',
        'role.required' => 'Role wajib dipilih.',
        'role.exists' => 'Role tidak valid.',
        'password.required' => 'Kata sandi wajib diisi untuk pengguna baru.',
        'password.min' => 'Kata sandi minimal 8 karakter.',
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function users()
    {
        $users = User::query()
            ->with('roles')
            ->when($this->search, function ($q) {
                $term = $this->search;
                $q->where(function ($sub) use ($term) {
                    $sub->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
            })
            ->when($this->roleFilter, fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', $this->roleFilter)))
            ->orderBy('name')
            ->paginate(10);

        $userIds = $users->pluck('id');

        $lastLogins = Activity::query()
            ->where('causer_type', User::class)
            ->whereIn('causer_id', $userIds)
            ->where('event', 'login')
            ->selectRaw('causer_id, MAX(created_at) as last_login_at')
            ->groupBy('causer_id')
            ->pluck('last_login_at', 'causer_id');

        $users->getCollection()->transform(function ($user) use ($lastLogins) {
            $user->last_login_at = $lastLogins->get($user->id);

            return $user;
        });

        return $users;
    }

    #[Computed]
    public function roleOptions()
    {
        return Role::orderBy('name')->get()->map(fn ($role) => [
            'name' => $role->name,
            'label' => $this->roleLabel($role->name),
        ]);
    }

    public function roleLabel(string $roleName): string
    {
        return config("rbac.role_aliases.{$roleName}", $roleName);
    }

    public function roleBadgeClass(string $roleName): string
    {
        return match ($roleName) {
            'Super Admin' => 'badge-accent',
            'Editor' => 'badge-info',
            'Penulis' => 'badge-neutral',
            'Ads Manager' => 'badge-warning',
            default => 'badge-neutral',
        };
    }

    public function openCreate(): void
    {
        $this->authorize('users.create');

        $this->resetForm();
        $this->showUserModal = true;
    }

    public function openEdit(int $userId): void
    {
        $this->authorize('users.update');

        $user = User::with('roles')->findOrFail($userId);

        $this->editingUserId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->roles->first()?->name ?? '';
        $this->password = '';
        $this->isActive = (bool) $user->is_active;
        $this->showUserModal = true;
    }

    public function save(): void
    {
        $this->authorize($this->editingUserId ? 'users.update' : 'users.create');

        $validated = $this->validate();

        if ($this->editingUserId) {
            $user = User::with('roles')->findOrFail($this->editingUserId);
            $oldRole = $user->roles->first()?->name;

            if ($user->id === Auth::id() && $validated['role'] !== $oldRole) {
                $this->addError('role', 'Anda tidak bisa mengubah role akun sendiri.');

                return;
            }

            if ($user->id === Auth::id() && ! $validated['isActive']) {
                $this->addError('isActive', 'Anda tidak bisa menonaktifkan akun sendiri.');

                return;
            }

            $user->name = $validated['name'];
            $user->email = $validated['email'];
            $user->is_active = $validated['isActive'];

            if (! empty($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            $user->save();
            $user->syncRoles([$validated['role']]);

            if ($oldRole !== $validated['role']) {
                activity('user')
                    ->causedBy(Auth::user())
                    ->performedOn($user)
                    ->withProperties([
                        'old' => ['role' => $oldRole],
                        'attributes' => ['role' => $validated['role']],
                    ])
                    ->log("Role pengguna \"{$user->name}\" diubah dari ".$this->roleLabel($oldRole ?? '-').' menjadi '.$this->roleLabel($validated['role']));
            }
        } else {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password'] ?: AccountCredentials::generatePassword()),
                'is_active' => $validated['isActive'],
                'email_verified_at' => now(),
            ]);
            $user->assignRole($validated['role']);

            activity('user')
                ->causedBy(Auth::user())
                ->performedOn($user)
                ->withProperties(['role' => $validated['role']])
                ->log("Pengguna \"{$user->name}\" dibuat dengan role ".$this->roleLabel($validated['role']));

            if ($this->sendCredentials) {
                try {
                    app(AccountCredentials::class)->send($user, $validated['password'] ?: null, isNewAccount: true);
                } catch (\Throwable $e) {
                    Log::error('Gagal mengirim info login akun baru', ['user_id' => $user->id, 'error' => $e->getMessage()]);
                    $this->showUserModal = false;
                    $this->resetForm();
                    unset($this->users);
                    $this->dispatch('flash-message', type: 'error', text: 'Pengguna dibuat, tetapi email info login gagal dikirim ('.AccountCredentials::describeMailError($e).'). Perbaiki pengaturan email, lalu pakai tombol Kirim Info Login.');

                    return;
                }

                $this->showUserModal = false;
                $this->resetForm();
                unset($this->users);
                $this->dispatch('flash-message', type: 'success', text: "Pengguna dibuat dan info login dikirim ke {$user->email}.");

                return;
            }
        }

        $this->showUserModal = false;
        $this->resetForm();
        unset($this->users);
        $this->dispatch('flash-message', type: 'success', text: 'Data pengguna berhasil disimpan.');
    }

    public function confirmDelete(int $userId): void
    {
        $this->authorize('users.delete');

        $user = User::findOrFail($userId);

        if ($user->id === Auth::id()) {
            $this->dispatch('flash-message', type: 'error', text: 'Anda tidak bisa menghapus akun Anda sendiri.');

            return;
        }

        if ($user->email === config('system.redaksi_email')) {
            $this->dispatch('flash-message', type: 'error', text: 'Akun Redaksi tidak bisa dihapus karena dipakai sistem untuk artikel yang ditinggalkan penulisnya.');

            return;
        }

        $this->deletingUserId = $userId;
        $this->showDeleteModal = true;
    }

    public function deleteUser(): void
    {
        $this->authorize('users.delete');

        $user = User::findOrFail($this->deletingUserId);

        if ($user->id === Auth::id()) {
            $this->dispatch('flash-message', type: 'error', text: 'Anda tidak bisa menghapus akun Anda sendiri.');
            $this->closeDeleteModal();

            return;
        }

        if ($user->hasRole('Super Admin') && User::role('Super Admin')->count() <= 1) {
            $this->dispatch('flash-message', type: 'error', text: 'Tidak bisa menghapus satu-satunya akun Super Admin.');
            $this->closeDeleteModal();

            return;
        }

        $redaksi = User::where('email', config('system.redaksi_email'))->first();

        if (! $redaksi) {
            $this->dispatch('flash-message', type: 'error', text: 'Akun Redaksi belum ada. Jalankan seeder SystemAccountSeeder dulu.');
            $this->closeDeleteModal();

            return;
        }

        DB::transaction(function () use ($user, $redaksi) {
            Article::where('author_id', $user->id)->update(['author_id' => $redaksi->id]);
            $user->delete();
        });

        $this->closeDeleteModal();
        unset($this->users);
        $this->dispatch('flash-message', type: 'success', text: 'Pengguna berhasil dihapus, artikel miliknya dipindahkan ke akun Redaksi.');
    }

    public function confirmSendCredentials(int $userId): void
    {
        $this->authorize('users.update');

        if ($userId === Auth::id()) {
            $this->dispatch('flash-message', type: 'error', text: 'Untuk akun sendiri, ganti kata sandi lewat menu Profil Saya.');

            return;
        }

        $this->credentialsUserId = User::findOrFail($userId)->id;
        $this->showCredentialsModal = true;
    }

    public function sendCredentialsNow(): void
    {
        $this->authorize('users.update');

        $user = User::findOrFail($this->credentialsUserId);
        $this->showCredentialsModal = false;
        $this->credentialsUserId = null;

        if ($user->id === Auth::id()) {
            return;
        }

        try {
            app(AccountCredentials::class)->send($user);
        } catch (\Throwable $e) {
            Log::error('Gagal mengirim info login', ['user_id' => $user->id, 'error' => $e->getMessage()]);
            $this->dispatch('flash-message', type: 'error', text: 'Email gagal dikirim, kata sandi pengguna tidak diubah. Penyebab: '.AccountCredentials::describeMailError($e));

            return;
        }

        unset($this->users);
        $this->dispatch('flash-message', type: 'success', text: "Info login dengan kata sandi sementara baru dikirim ke {$user->email}.");
    }

    public function closeCredentialsModal(): void
    {
        $this->showCredentialsModal = false;
        $this->credentialsUserId = null;
    }

    public function closeDeleteModal(): void
    {
        $this->showDeleteModal = false;
        $this->deletingUserId = null;
    }

    public function closeModal(): void
    {
        $this->showUserModal = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['editingUserId', 'name', 'email', 'role', 'password']);
        $this->isActive = true;
        $this->sendCredentials = true;
        $this->resetErrorBag();
    }

    public function render()
    {
        return view('livewire.admin.user-manager');
    }
}
