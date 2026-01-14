<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Admin HR
        $admin = User::create([
            'name' => 'Admin HR',
            'email' => 'admin@absensi.test',
            'password' => Hash::make('password'),
            'jatah_cuti_tambahan' => 5.00,
            'company' => null,
            'atasan_id' => null,
        ]);
        $admin->assignRole('Admin');

        // 2. HRD
        $hrd = User::create([
            'name' => 'HRD',
            'email' => 'hr@absensi.test',
            'password' => Hash::make('password'),
            'jatah_cuti_tambahan' => 3.00,
            'company' => null,
            'atasan_id' => $admin->id,
        ]);
        $hrd->assignRole('HR');

        // 3. Karyawan 1
        $karyawan = User::create([
            'name' => 'Karyawan 1',
            'email' => 'user@absensi.test',
            'password' => Hash::make('password'),
            'jatah_cuti_tambahan' => 0.00,
            'company' => null,
            'atasan_id' => $hrd->id,
        ]);
        $karyawan->assignRole('Karyawan');

        // 4. Sales Manager
        $sales = User::create([
            'name' => 'Sales Manager',
            'email' => 'sales@absensi.test',
            'password' => Hash::make('password'),
            'jatah_cuti_tambahan' => 2.00,
            'company' => null,
            'atasan_id' => $admin->id,
        ]);
        $sales->assignRole('Sales');

        // 5. Engineer Lead
        $engineer = User::create([
            'name' => 'Engineer Lead',
            'email' => 'engineer@absensi.test',
            'password' => Hash::make('password'),
            'jatah_cuti_tambahan' => 2.00,
            'company' => null,
            'atasan_id' => $admin->id,
        ]);
        $engineer->assignRole('Engineer');

        // 6. Direktur
        $direktur = User::create([
            'name' => 'Direktur Utama',
            'email' => 'direktur@absensi.test',
            'password' => Hash::make('password'),
            'jatah_cuti_tambahan' => 10.00,
            'company' => null,
            'atasan_id' => null,
        ]);
        $direktur->assignRole('Direktur');
    }
}