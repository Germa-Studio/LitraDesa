<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default admin user
        User::create([
            'name' => 'Admin LitraDesa',
            'email' => 'admin@litradesa.id',
            'email_verified_at' => now(),
            'password' => Hash::make('admin123'), // Change this in production!
            'role' => 'admin',
            'status' => 'active',
            'phone' => '081234567890',
            'address' => 'Kantor Desa',
            'ktp_number' => '1234567890123456',
            'qr_code' => 'ADM-' . strtoupper(Str::random(10)),
            'approved_by' => null,
            'approved_at' => now(),
            'remember_token' => Str::random(10),
        ]);

        $this->command->info('Admin user created successfully!');
        $this->command->info('Email: admin@litradesa.id');
        $this->command->info('Password: admin123');
        $this->command->warn('⚠️  Please change the admin password after first login!');
    }
}

// Made with Bob
