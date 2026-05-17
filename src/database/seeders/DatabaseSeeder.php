<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed roles and permissions first
        $this->call([
            RolesAndPermissionsSeeder::class,
            AdminSeeder::class,
        ]);

        // Uncomment to seed sample members for testing
        // User::factory(10)->create([
        //     'role' => 'member',
        //     'status' => 'active',
        // ]);
    }
}
