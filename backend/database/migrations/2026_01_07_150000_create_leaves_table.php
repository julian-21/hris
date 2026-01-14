<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tabel jenis cuti (master data)
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->text('keterangan')->nullable();
            $table->integer('jumlah_hari')->default(0); // alokasi default per tahun
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Tabel pengajuan cuti
        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('leave_type_id')->constrained()->onDelete('cascade');
            
            // Tipe durasi
            $table->enum('tipe_durasi', ['sehari', 'setengah_hari', 'lebih_dari_sehari']);
            
            // Tanggal
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            
            // Untuk setengah hari
            $table->enum('setengah_hari_tipe', ['pagi', 'siang'])->nullable();
            
            // Jumlah hari (auto-calculated)
            $table->decimal('jumlah_hari', 5, 1); // support 0.5 untuk setengah hari
            
            // Alasan & dokumen
            $table->text('alasan');
            $table->string('dokumen_pendukung')->nullable();
            
            // Status approval
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan_approval')->nullable();
            $table->timestamp('approved_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['tanggal_mulai', 'tanggal_selesai']);
            $table->index('leave_type_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leaves');
        Schema::dropIfExists('leave_types');
    }
};