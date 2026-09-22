<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class RoleTestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $this->call(RolePermissionSeeder::class);

        foreach ([
            'editor@portalkebumen.test' => ['Editor Test', 'Editor'],
            'penulis@portalkebumen.test' => ['Penulis Test', 'Penulis'],
            'ads@portalkebumen.test' => ['Ads Manager Test', 'Ads Manager'],
        ] as $email => [$name, $role]) {
            $user = User::firstOrCreate(['email' => $email], [
                'name' => $name,
                'password' => 'password',
                'email_verified_at' => now(),
            ]);

            if ($user->wasRecentlyCreated) {
                $user->assignRole($role);
            }
        }
    }
}
