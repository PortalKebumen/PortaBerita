<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SystemAccountSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('system.redaksi_email');

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Redaksi',
                'password' => bcrypt(Str::random(40)),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        if (! $user->hasRole('Editor')) {
            $user->assignRole('Editor');
        }
    }
}