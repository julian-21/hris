<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Pastikan tabel kantors sudah ada
        if (!Schema::hasTable('kantors')) {
            throw new \Exception('Table kantors must be created first!');
        }

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('tanggal');
            $table->time('jadwal_checkin')->nullable();
            $table->time('jadwal_checkout')->nullable();
            $table->dateTime('check_in')->nullable();
            $table->dateTime('check_out')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->unsignedBigInteger('schedule_id')->nullable();
            $table->unsignedBigInteger('kantor_id')->nullable();
            $table->integer('lama_lembur')->nullable();
            $table->integer('makan_lembur')->nullable();
            $table->text('alasan_lembur')->nullable();
            $table->text('alasan_cuti')->nullable();
            $table->double('lama_cuti', 8, 2)->nullable();
            $table->unsignedInteger('jeniscuti_id')->nullable();
            $table->enum('status_cuti', ['pending', 'disetujui', 'ditolak'])->nullable();
            $table->boolean('is_holiday')->default(0);
            $table->boolean('out_of_office')->default(0); // Tambahan: flag di luar kantor
            $table->text('out_of_office_reason')->nullable(); // Tambahan: keterangan di luar kantor
            $table->timestamps();

            // Foreign key untuk kantor
            $table->foreign('kantor_id')->references('id')->on('kantors')->onDelete('set null');

            // Index untuk performa
            $table->index(['user_id', 'tanggal']);
            $table->index('tanggal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};