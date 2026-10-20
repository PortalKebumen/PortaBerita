<?php

namespace App\Models;

use App\Mail\ResetPasswordMail;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\LogsModelActivity;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'is_active'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, LogsModelActivity, Notifiable;

    /** Sama dengan default kolom di database: akun baru aktif kecuali dinonaktifkan admin. */
    protected $attributes = [
        'is_active' => true,
        'must_change_password' => false,
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    protected array $logAttributes = ['name', 'email', 'bio', 'is_active'];

    protected string $logLabel = 'Pengguna';

    /**
     * Segmen nama untuk URL profil publik, gaya "Nama.Penulis" (contoh: Bagus.Prakoso).
     */
    public function profileSlug(): string
    {
        return Str::of($this->name)->ascii()->replaceMatches('/[^A-Za-z0-9]+/', '.')->trim('.')->value() ?: 'penulis';
    }

    /** URL profil publik: /profile/{id}/{Nama.Penulis} */
    public function profileUrl(): string
    {
        return route('public.byline', ['user' => $this->id, 'slug' => $this->profileSlug()]);
    }

    /**
     * Kirim email reset password memakai template PortalKebumen.
     */
    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        Mail::to($this->getEmailForPasswordReset())->send(
            new ResetPasswordMail($this, $token)
        );
    }
}
