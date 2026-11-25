<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\TwoFactor;
use Illuminate\Console\Command;

/**
 * Jalan darurat bila Super Admin sendiri kehilangan HP dan kode cadangan:
 *   php artisan user:reset-2fa email@contoh.com
 * Setelah itu akun bisa masuk dengan kata sandi lalu mengaktifkan verifikasi dua langkah lagi.
 */
class ResetTwoFactor extends Command
{
    protected $signature = 'user:reset-2fa {email : Email akun yang verifikasi dua langkahnya direset}';

    protected $description = 'Reset verifikasi dua langkah sebuah akun (HP hilang dan kode cadangan tidak ada)';

    public function handle(TwoFactor $twoFactor): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('Akun dengan email tersebut tidak ditemukan.');

            return self::FAILURE;
        }

        $twoFactor->reset($user);
        activity('pengguna')->performedOn($user)->log('Mereset verifikasi dua langkah lewat terminal server');

        $this->info("Verifikasi dua langkah {$user->name} ({$user->email}) sudah direset. Masuk dengan kata sandi, lalu aktifkan lagi.");

        return self::SUCCESS;
    }
}
