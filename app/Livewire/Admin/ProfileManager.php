<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

/**
 * Profil Saya: setiap pengguna mengubah nama, email, bio (tampil di halaman profil penulis publik),
 * dan kata sandinya sendiri. Role & status aktif tetap hanya bisa diubah admin di Kelola Pengguna.
 */
class ProfileManager extends Component
{
    public string $name = '';

    public string $email = '';

    public string $bio = '';

    public string $currentPassword = '';

    public string $newPassword = '';

    public string $newPassword_confirmation = '';

    public function mount(): void
    {
        $user = $this->user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->bio = (string) $user->bio;
    }

    protected function user(): User
    {
        return Auth::user();
    }

    public function saveProfile(): void
    {
        $user = $this->user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'bio' => ['nullable', 'string', 'max:1000'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'name.min' => 'Nama minimal 3 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email ini sudah dipakai pengguna lain.',
            'bio.max' => 'Bio maksimal 1.000 karakter.',
        ]);

        $user->forceFill([
            'name' => trim($validated['name']),
            'email' => strtolower(trim($validated['email'])),
            'bio' => trim((string) $validated['bio']) ?: null,
        ])->save();

        $this->dispatch('flash-message', type: 'success', text: 'Profil berhasil diperbarui.');
    }

    public function savePassword(): void
    {
        $user = $this->user();

        $this->validate([
            'currentPassword' => ['required', 'current_password'],
            'newPassword' => ['required', 'confirmed', 'different:currentPassword', Password::min(8)->letters()->numbers()],
        ], [
            'currentPassword.required' => 'Kata sandi saat ini wajib diisi.',
            'currentPassword.current_password' => 'Kata sandi saat ini salah.',
            'newPassword.required' => 'Kata sandi baru wajib diisi.',
            'newPassword.confirmed' => 'Konfirmasi kata sandi baru tidak sama.',
            'newPassword.different' => 'Kata sandi baru harus berbeda dari kata sandi saat ini.',
            'newPassword.min' => 'Kata sandi baru minimal 8 karakter.',
            'newPassword.letters' => 'Kata sandi baru harus memuat huruf.',
            'newPassword.numbers' => 'Kata sandi baru harus memuat angka.',
        ]);

        $user->forceFill([
            'password' => Hash::make($this->newPassword),
            'must_change_password' => false,
        ])->save();

        activity('user')
            ->causedBy($user)
            ->performedOn($user)
            ->log("Pengguna \"{$user->name}\" mengganti kata sandinya");

        $this->reset(['currentPassword', 'newPassword', 'newPassword_confirmation']);
        $this->dispatch('flash-message', type: 'success', text: 'Kata sandi berhasil diganti.');
    }

    public function render()
    {
        $user = $this->user();

        return view('livewire.admin.profile-manager', [
            'user' => $user,
            'roleName' => ($role = $user->getRoleNames()->first()) ? config("rbac.role_aliases.{$role}", $role) : null,
            'mustChange' => (bool) $user->must_change_password,
        ]);
    }
}
