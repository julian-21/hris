<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cek apakah kolom sudah ada
        if (!Schema::hasColumn('users', 'kantor_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('kantor_id')->nullable()->after('atasan_id');
                $table->foreign('kantor_id')->references('id')->on('kantors')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'kantor_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['kantor_id']);
                $table->dropColumn('kantor_id');
            });
        }
    }
};