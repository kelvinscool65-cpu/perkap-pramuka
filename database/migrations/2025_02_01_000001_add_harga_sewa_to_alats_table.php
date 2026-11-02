<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fitur baru: biaya sewa per alat (bisa diedit admin, terlihat oleh
     * peminjam). Migration TERPISAH (bukan edit migration alats yang lama)
     * karena migration yang sudah pernah dijalankan sebaiknya tidak diubah —
     * praktik standar Laravel: tambah migration baru untuk perubahan skema.
     */
    public function up(): void
    {
        Schema::table('alats', function (Blueprint $table) {
            $table->unsignedInteger('harga_sewa')->default(0)->after('satuan');
        });
    }

    public function down(): void
    {
        Schema::table('alats', function (Blueprint $table) {
            $table->dropColumn('harga_sewa');
        });
    }
};
