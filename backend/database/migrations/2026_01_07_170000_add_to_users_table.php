<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Update company menjadi enum
            $table->enum('company', [
                'PT Lintas Karya Sejahtera',
                'PT Karya Sukses Bersama',
                'PT Mitra Solusi Indonesia',
                'PT Teknologi Nusantara'
            ])->nullable()->change();
            
            // Tambah kolom posisi/jabatan
            $table->string('posisi')->nullable()->after('company');
            
            // Tambah kolom nomor telepon
            $table->string('phone')->nullable()->after('email');
            
            // Tambah kolom tanggal bergabung
            $table->date('tanggal_bergabung')->nullable()->after('posisi');
            
            // Tambah kolom status (aktif/nonaktif)
            $table->boolean('is_active')->default(true)->after('tanggal_bergabung');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('company')->nullable()->change();
            $table->dropColumn(['posisi', 'phone', 'tanggal_bergabung', 'is_active']);
        });
    }
};