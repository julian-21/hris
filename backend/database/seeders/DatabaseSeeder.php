<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            KantorSeeder::class,
            LeaveTypeSeeder::class,
            RolePermissionSeeder::class,
            UserSeeder::class,
        ]);
    }
}