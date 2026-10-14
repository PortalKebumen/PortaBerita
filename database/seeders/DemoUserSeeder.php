<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DemoUserSeeder extends Seeder
{
    /**
     * Akun redaksi untuk server testing (satu per role). Password diambil dari SEEDER_PASSWORD di .env,
     * default "password". Akun yang sudah ada tidak diubah password-nya.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $password = env('SEEDER_PASSWORD', 'password');

        $users = [
            ['Admin', 'admin@portalkebumen.test', 'Super Admin'],
            ['Wayan', 'wayantrk@gmail.com', 'Editor'],
            ['Ads Manager', 'iklanmanajer@portalkebumen.com', 'Ads Manager'],
            ['Bagus Prakoso', 'ahmad1606wayan002@gmail.com', 'Penulis'],
            ['Ahmad Fauzan', 'ahmad.f@portalkebumen.com', 'Penulis'],
        ];

        foreach ($users as [$name, $email, $role]) {
            $user = User::firstOrNew(['email' => $email]);

            if (! $user->exists) {
                // email_verified_at tidak termasuk $fillable User, jadi diisi lewat forceFill
                $user->forceFill(['name' => $name, 'password' => $password, 'email_verified_at' => now(), 'is_active' => true])->save();
            }

            if (! $user->hasRole($role)) {
                $user->assignRole($role);
            }
        }

        if ($password === 'password') {
            $this->command?->warn('Akun demo memakai password default "password". Isi SEEDER_PASSWORD di .env untuk server yang bisa diakses publik.');
        }
    }
}
