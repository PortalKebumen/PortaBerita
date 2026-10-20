<?php

namespace App\Services;

use App\Mail\AccountCredentialsMail;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Kirim info login ke email pengguna. Email dikirim DULU; kata sandi baru baru disimpan bila email
 * berhasil terkirim, sehingga pengguna tidak terkunci dengan kata sandi yang tidak pernah ia terima.
 */
class AccountCredentials
{
    /**
     * @param  string|null  $password  kata sandi yang diketik admin (akun baru); null = buat acak
     *
     * @throws TransportExceptionInterface bila email gagal dikirim
     */
    public function send(User $user, #[\SensitiveParameter] ?string $password = null, bool $isNewAccount = false): void
    {
        $password ??= self::generatePassword();

        Mail::to($user->email)->send(new AccountCredentialsMail($user, $password, $isNewAccount));

        $user->forceFill([
            'password' => Hash::make($password),
            'must_change_password' => true,
        ])->save();

        activity('user')
            ->causedBy(Auth::user())
            ->performedOn($user)
            ->log("Info login dikirim ke email pengguna \"{$user->name}\" ({$user->email})");
    }

    /** 12 karakter tanpa huruf/angka yang mirip (0/O, 1/l/I) agar mudah diketik dari email. */
    public static function generatePassword(int $length = 12): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
        $password = '';
        for ($i = 0; $i < $length; $i++) {
            $password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }

        // Pastikan ada huruf besar, huruf kecil, dan angka
        return preg_match('/[A-Z]/', $password) && preg_match('/[a-z]/', $password) && preg_match('/\d/', $password)
            ? $password
            : self::generatePassword($length);
    }

    /** Pesan error SMTP yang ringkas untuk ditampilkan ke admin. */
    public static function describeMailError(\Throwable $e): string
    {
        return Str::limit(trim(preg_replace('/\s+/', ' ', $e->getMessage())), 180);
    }
}
