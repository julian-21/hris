<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LeaveTypeSeeder extends Seeder
{
    public function run(): void
    {
        $leaveTypes = [
            [
                'id' => 1,
                'nama' => 'Cuti Tahunan',
                'keterangan' => 'Cuti yang didapatkan setelah masa kerja 1 tahun',
                'jumlah_hari' => 12,
                'is_active' => true,
            ],
            [
                'id' => 2,
                'nama' => 'Cuti Duka',
                'keterangan' => 'Cuti yang didapatkan ketika ayah/ibu/mertua/anak/suami/istri meninggal dunia',
                'jumlah_hari' => 2,
                'is_active' => true,
            ],
            [
                'id' => 3,
                'nama' => 'Cuti Nikah',
                'keterangan' => 'Cuti yang didapatkan ketika karyawan menikah',
                'jumlah_hari' => 3,
                'is_active' => true,
            ],
            [
                'id' => 4,
                'nama' => 'Cuti Menikahkan Anak',
                'keterangan' => 'Cuti yang didapatkan ketika menikahkan anak atau perwalian',
                'jumlah_hari' => 0,
                'is_active' => true,
            ],
            [
                'id' => 5,
                'nama' => 'Cuti Melahirkan',
                'keterangan' => 'Cuti yang diberikan untuk karyawati untuk hari istirahat sebelum dan sesudah melahirkan',
                'jumlah_hari' => 90,
                'is_active' => true,
            ],
            [
                'id' => 6,
                'nama' => 'Cuti Istri Sah Melahirkan',
                'keterangan' => 'Cuti untuk karyawan yang memiliki istri sah yang sedang melahirkan',
                'jumlah_hari' => 2,
                'is_active' => true,
            ],
            [
                'id' => 7,
                'nama' => 'Cuti Duka Kerabat',
                'keterangan' => 'Cuti yang diberikan untuk karyawan yang kerabat sedarahnya meninggal',
                'jumlah_hari' => 1,
                'is_active' => true,
            ],
            [
                'id' => 8,
                'nama' => 'Cuti Haji',
                'keterangan' => 'Cuti yang diberikan karyawan yang melakukan ibadah haji',
                'jumlah_hari' => 45,
                'is_active' => true,
            ],
            [
                'id' => 9,
                'nama' => 'Cuti Umrah',
                'keterangan' => 'Cuti yang diberikan karyawan yang melakukan ibadah umrah',
                'jumlah_hari' => 12,
                'is_active' => true,
            ],
            [
                'id' => 10,
                'nama' => 'Izin (karyawan lebih 12 bln)',
                'keterangan' => 'Izin untuk karyawan yang sudah mendapatkan cuti ada perlu mendadak',
                'jumlah_hari' => 2,
                'is_active' => true,
            ],
            [
                'id' => 11,
                'nama' => 'Sakit',
                'keterangan' => 'Karyawan yang tidak masuk kerja karena sakit',
                'jumlah_hari' => 50,
                'is_active' => true,
            ],
            [
                'id' => 12,
                'nama' => 'Work From Home (WFH)',
                'keterangan' => 'Karyawan yang tidak memungkinkan untuk bekerja di kantor',
                'jumlah_hari' => 40,
                'is_active' => true,
            ],
            [
                'id' => 13,
                'nama' => 'Izin (karyawan kurang 12 bln)',
                'keterangan' => 'Izin untuk karyawan yang belum mendapatkan cuti ada keperluan mendadak',
                'jumlah_hari' => 12,
                'is_active' => true,
            ],
        ];

        foreach ($leaveTypes as $type) {
            DB::table('leave_types')->insert([
                'id' => $type['id'],
                'nama' => $type['nama'],
                'keterangan' => $type['keterangan'],
                'jumlah_hari' => $type['jumlah_hari'],
                'is_active' => $type['is_active'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}