<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DemoUserSeeder extends Seeder
{
    /**
     * Akun contoh untuk server testing/staging, minimal satu per role.
     *
     * Semua email memakai domain .test (dicadangkan RFC 2606, tidak pernah menerima email), jadi uji
     * "lupa password" atau notifikasi tidak terkirim ke orang sungguhan. Setelah seeding, ganti email
     * & nama tiap akun dengan milik penguji lewat Admin > Pengguna & Role.
     *
     * Password diambil dari SEEDER_PASSWORD di .env (default "password"). Akun yang sudah ada
     * (email sama) tidak diubah, sehingga seeder aman dijalankan ulang.
     *
     * Nama dua akun Penulis dipakai DummyAuthorProfileSeeder untuk mengisi bio, jadi jangan diubah di sini.
     */
    public const USERS = [
        // [nama, email, role, keterangan]
        ['Admin Portal', 'admin@portalkebumen.test', 'Super Admin', 'Pengelola sistem: pengguna & izin, menu, halaman, pengaturan'],
        ['Pemimpin Redaksi', 'pemred@portalkebumen.test', 'Editor', 'Redaktur: review, publikasi, riwayat versi, laporan konten'],
        ['Redaktur', 'redaktur@portalkebumen.test', 'Editor', 'Redaktur kedua untuk uji alur review'],
        ['Bagus Prakoso', 'penulis1@portalkebumen.test', 'Penulis', 'Penulis/reporter (punya artikel & bio contoh)'],
        ['Ahmad Fauzan', 'penulis2@portalkebumen.test', 'Penulis', 'Penulis kedua untuk uji batas hak edit antar penulis'],
        ['Ads Manager', 'iklan@portalkebumen.test', 'Ads Manager', 'Pengelola iklan & laporan iklan'],
    ];

    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        // "SEEDER_PASSWORD=" (kosong, seperti di .env.example) dibaca sebagai teks kosong, bukan null:
        // tanpa ?: semua akun akan dibuat dengan password kosong dan tidak bisa login
        $password = (string) env('SEEDER_PASSWORD') ?: 'password';
        $rows = [];

        foreach (self::USERS as [$name, $email, $role, $note]) {
            $user = User::firstOrNew(['email' => $email]);
            $created = ! $user->exists;

            if ($created) {
                // email_verified_at tidak termasuk $fillable User, jadi diisi lewat forceFill
                $user->forceFill(['name' => $name, 'password' => $password, 'email_verified_at' => now(), 'is_active' => true])->save();
            }

            if (! $user->hasRole($role)) {
                $user->assignRole($role);
            }

            $rows[] = [$user->name, $email, $role, $created ? 'dibuat' : 'sudah ada', $note];
        }

        $this->command?->table(['Nama', 'Email', 'Role', 'Status', 'Keterangan'], $rows);
        $this->command?->info('Ganti email & nama akun di atas dengan milik penguji lewat Admin > Pengguna & Role.');

        if ($password === 'password') {
            $this->command?->warn('Akun contoh memakai password default "password". Isi SEEDER_PASSWORD di .env untuk server yang bisa diakses publik.');
        }
    }
}
