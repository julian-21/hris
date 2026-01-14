<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Kantor;
use Carbon\Carbon;

class KantorSeeder extends Seeder
{
    public function run(): void
    {
        $kantors = [
            [
                'id' => 1,
                'nama' => 'Luar Kantor',
                'alamat' => 'Di luar',
                'latitude' => 0.000000,
                'longitude' => 0.000000,
                'radius' => 100,
                'created_at' => Carbon::parse('2023-07-17 06:54:20'),
                'updated_at' => Carbon::parse('2023-07-17 06:54:20'),
                'is_active' => true,
            ],
            [
                'id' => 2,
                'nama' => 'Kantor Pusat',
                'alamat' => 'Jl. Parangtritis km 5 Tarudan Bangunharjo, Kec. Sewon, Bantul',
                'latitude' => -7.846103,
                'longitude' => 110.361071,
                'radius' => 100,
                'created_at' => Carbon::parse('2023-07-17 09:07:57'),
                'updated_at' => Carbon::parse('2023-07-17 09:07:57'),
                'is_active' => true,
            ],
            [
                'id' => 4,
                'nama' => 'Produksi',
                'alamat' => 'Jl. Parangtritis km 5 Tarudan Bangunharjo, Kec. Sewon, Bantul',
                'latitude' => -7.846109,
                'longitude' => 110.361660,
                'radius' => 100,
                'created_at' => Carbon::parse('2023-07-24 10:18:26'),
                'updated_at' => Carbon::parse('2023-07-24 10:18:26'),
                'is_active' => true,
            ],
            [
                'id' => 5,
                'nama' => 'Melb Flinders',
                'alamat' => 'Flinders',
                'latitude' => -37.820067,
                'longitude' => 144.957897,
                'radius' => 100,
                'created_at' => Carbon::parse('2023-08-02 08:44:00'),
                'updated_at' => Carbon::parse('2023-08-02 08:44:00'),
                'is_active' => true,
            ],
            [
                'id' => 6,
                'nama' => 'Kantor asal',
                'alamat' => 'asal',
                'latitude' => 100.334200,
                'longitude' => -6.469000,
                'radius' => 100,
                'created_at' => Carbon::parse('2023-08-02 08:46:15'),
                'updated_at' => Carbon::parse('2023-08-02 08:46:15'),
                'is_active' => true,
            ],
            [
                'id' => 7,
                'nama' => 'Kantor depan parkiran',
                'alamat' => 'Jl. Parangtritis km 5 Tarudan Bangunharjo, Kec. Sewon, Bantul',
                'latitude' => -7.846126,
                'longitude' => 110.360856,
                'radius' => 100,
                'created_at' => Carbon::parse('2023-09-11 10:22:17'),
                'updated_at' => Carbon::parse('2023-09-11 10:22:17'),
                'is_active' => true,
            ],
            [
                'id' => 8,
                'nama' => 'Finishing',
                'alamat' => 'Jl. Parangtritis km 5 Tarudan Bangunharjo, Kec. Sewon, Bantul',
                'latitude' => -7.846108,
                'longitude' => 110.361016,
                'radius' => 100,
                'created_at' => Carbon::parse('2023-09-11 10:24:36'),
                'updated_at' => Carbon::parse('2023-09-11 10:24:36'),
                'is_active' => true,
            ],
            [
                'id' => 9,
                'nama' => 'Gudang',
                'alamat' => 'Jl. Parangtritis km 5 Tarudan Bangunharjo, Kec. Sewon, Bantul',
                'latitude' => -7.846097,
                'longitude' => 110.361581,
                'radius' => 100,
                'created_at' => Carbon::parse('2023-09-11 10:26:23'),
                'updated_at' => Carbon::parse('2023-09-11 10:26:23'),
                'is_active' => true,
            ],
            [
                'id' => 10,
                'nama' => 'Parkiran belakang',
                'alamat' => 'Jl. Parangtritis km 5 Tarudan Bangunharjo, Kec. Sewon, Bantul',
                'latitude' => -7.846281,
                'longitude' => 110.361725,
                'radius' => 100,
                'created_at' => Carbon::parse('2023-09-11 10:28:22'),
                'updated_at' => Carbon::parse('2023-09-11 10:28:22'),
                'is_active' => true,
            ],
            [
                'id' => 11,
                'nama' => 'Rumah sabut',
                'alamat' => 'Jl. Parangtritis km 5 Tarudan Bangunharjo, Kec. Sewon, Bantul',
                'latitude' => -7.845961,
                'longitude' => 110.361959,
                'radius' => 100,
                'created_at' => Carbon::parse('2023-09-11 10:30:07'),
                'updated_at' => Carbon::parse('2023-09-11 10:30:07'),
                'is_active' => true,
            ],
            [
                'id' => 12,
                'nama' => 'Studio',
                'alamat' => 'Jl. Parangtritis km 5 Tarudan Bangunharjo, Kec. Sewon, Bantul',
                'latitude' => -7.845963,
                'longitude' => 110.362189,
                'radius' => 100,
                'created_at' => Carbon::parse('2023-09-11 10:32:39'),
                'updated_at' => Carbon::parse('2023-09-11 10:32:39'),
                'is_active' => true,
            ],
        ];

        foreach ($kantors as $kantor) {
            Kantor::updateOrCreate(
                ['id' => $kantor['id']],
                $kantor
            );
        }

        $this->command->info('Kantors seeded successfully!');
    }
}