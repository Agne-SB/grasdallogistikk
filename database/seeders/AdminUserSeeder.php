<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = 'agne@grasdal.no';
        $password = 'ChangeMeNow_!234'; // change after first login

        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'name' => 'Agne',
                'password' => Hash::make($password),
                'is_active' => true,
                'email_verified_at' => now(),
                'remember_token' => Str::random(10),
            ]
        );

        // ensure role even if not mass-assignable
        $user->forceFill(['role' => 'admin'])->save();

        $this->command?->info("Admin user ready: {$user->email}");
    }
}
