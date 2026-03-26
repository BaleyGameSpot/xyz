<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        Admin::updateOrCreate(
            ['email' => env('ADMIN_EMAIL', 'admin@tradingsignals.com')],
            [
                'name'      => 'Super Admin',
                'email'     => env('ADMIN_EMAIL', 'admin@tradingsignals.com'),
                'password'  => Hash::make(env('ADMIN_PASSWORD', 'Admin@123456')),
                'role'      => 'super_admin',
                'is_active' => true,
            ]
        );

        $this->command->info('Admin created: ' . env('ADMIN_EMAIL', 'admin@tradingsignals.com'));
        $this->command->warn('IMPORTANT: Change the admin password immediately after setup!');
    }
}
