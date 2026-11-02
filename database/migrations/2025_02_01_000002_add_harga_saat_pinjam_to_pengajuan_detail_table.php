<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Snapshot harga sewa PADA SAAT pengajuan dibuat — supaya riwayat biaya
     * tetap akurat historis meskipun admin mengubah harga_sewa alat di
     * kemudian hari (mirip pola "harga saat transaksi" di sistem e-commerce).
     */
    public function up(): void
    {
        Schema::table('pengajuan_detail', function (Blueprint $table) {
            $table->unsignedInteger('harga_saat_pinjam')->default(0)->after('jumlah');
        });
    }

    public function down(): void
    {
        Schema::table('pengajuan_detail', function (Blueprint $table) {
            $table->dropColumn('harga_saat_pinjam');
        });
    }
};
