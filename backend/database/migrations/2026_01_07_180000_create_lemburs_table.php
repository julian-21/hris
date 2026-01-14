<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lemburs', function (Blueprint $table) {
            $table->id();

            $table->date('tanggal_lembur');
            $table->integer('lama_lembur')->comment('Durasi lembur dalam menit');
            $table->text('alasan_lembur');

            // FK harus BIGINT UNSIGNED
            $table->unsignedBigInteger('user_id');

            $table->enum('status', ['accepted', 'rejected', 'waiting'])->default('waiting');
            $table->integer('sisa_waktu_claim')->default(0)->comment('Sisa waktu yang bisa di-claim dalam menit');
            $table->enum('final_status', ['accepted', 'rejected', 'waiting'])->default('waiting');

            $table->timestamps();

            // Foreign Key
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            // Indexes
            $table->index('user_id');
            $table->index('tanggal_lembur');
            $table->index('status');
            $table->index('final_status');
            $table->index(['user_id', 'tanggal_lembur']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lemburs');
    }
};
