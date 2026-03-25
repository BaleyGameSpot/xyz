<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@tradingsignals.com')],
            [
                'name'              => 'Admin',
                'email'             => env('ADMIN_EMAIL', 'admin@tradingsignals.com'),
                'password'          => Hash::make(env('ADMIN_PASSWORD', 'Admin@123456')),
                'role'              => 'admin',
                'status'            => 'active',
                'email_verified_at' => now(),
            ]
        );

        $this->command->info('Admin user created: ' . env('ADMIN_EMAIL', 'admin@tradingsignals.com'));
        $this->command->warn('IMPORTANT: Change the admin password immediately in production!');
    }
}
