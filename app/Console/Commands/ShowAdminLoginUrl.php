<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Tampilkan alamat login panel admin yang aktif (rahasia; lihat config auth.login_path).
 * Dipakai semua pengguna (Super Admin, Redaktur, Penulis, Ads Manager); bagikan hanya ke pemilik akun.
 */
class ShowAdminLoginUrl extends Command
{
    protected $signature = 'admin:login-url';

    protected $description = 'Tampilkan alamat halaman login panel admin (semua pengguna) yang aktif';

    public function handle(): int
    {
        $this->info('Alamat login panel admin (semua pengguna): ' . route('login'));

        if (blank(env('ADMIN_LOGIN_PATH'))) {
            $this->warn('ADMIN_LOGIN_PATH belum diisi: alamat di atas dibuat otomatis dari APP_KEY dan akan berubah bila APP_KEY diganti.');
            $this->line('Disarankan isi ADMIN_LOGIN_PATH di .env dengan alamat pilihan sendiri, mis. ruang-redaksi-7k2p.');
        }

        return self::SUCCESS;
    }
}
