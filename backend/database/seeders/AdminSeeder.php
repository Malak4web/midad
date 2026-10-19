<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('SEED_ADMIN_EMAIL', 'admin@midad.local');
        if (User::where('email', $email)->exists()) {
            return;
        }
        $password = env('SEED_ADMIN_PASSWORD') ?: Str::password(16);
        $user = User::create(['name' => 'مدير مداد', 'email' => $email, 'password' => $password]);
        $user->forceFill(['role' => User::ROLE_ADMIN, 'email_verified_at' => now()])->save();

        $this->command?->warn("Admin: {$email} / {$password}  (change it after first login)");
    }
}
