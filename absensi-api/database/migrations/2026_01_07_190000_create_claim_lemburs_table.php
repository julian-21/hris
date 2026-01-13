<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('lembur_claims', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('time'); // waktu yang di-claim dalam menit
            $table->date('date'); // tanggal claim
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['waiting', 'approved', 'rejected'])->default('waiting');
            $table->string('reject_reason')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'date']);
            $table->index(['status', 'date']);
        });

        // Tambahkan kolom expire_at di tabel lemburs
        Schema::table('lemburs', function (Blueprint $table) {
            $table->date('expire_at')->nullable()->after('final_status');
            $table->boolean('is_expired')->default(false)->after('expire_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('lembur_claims');
        
        Schema::table('lemburs', function (Blueprint $table) {
            $table->dropColumn(['expire_at', 'is_expired']);
        });
    }
};