<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Jaring pengaman untuk database hasil versi lama proyek.
     *
     * Kasus nyata: database yang dibuat dari versi proyek sebelum fitur harga sewa kadang
     * tercatat "sudah migrasi" tetapi kolom `harga_sewa` / `harga_saat_pinjam` tidak ada atau
     * berisi NULL. Akibatnya tombol "Ajukan" dan tampilan harga rusak tanpa pesan galat jelas.
     *
     * Migrasi ini IDEMPOTEN: tidak melakukan apa pun bila kolom sudah benar, dan tidak pernah
     * menghapus atau menimpa data yang sudah terisi.
     */
    public function up(): void
    {
        if (Schema::hasTable('alats')) {
            if (! Schema::hasColumn('alats', 'harga_sewa')) {
                Schema::table('alats', function (Blueprint $table) {
                    $table->unsignedInteger('harga_sewa')->default(0)->after('satuan');
                });
            } else {
                DB::table('alats')->whereNull('harga_sewa')->update(['harga_sewa' => 0]);
            }
        }

        if (Schema::hasTable('pengajuan_detail')) {
            if (! Schema::hasColumn('pengajuan_detail', 'harga_saat_pinjam')) {
                Schema::table('pengajuan_detail', function (Blueprint $table) {
                    $table->unsignedInteger('harga_saat_pinjam')->default(0)->after('jumlah');
                });
            } else {
                DB::table('pengajuan_detail')->whereNull('harga_saat_pinjam')->update(['harga_saat_pinjam' => 0]);
            }
        }
    }

    public function down(): void
    {
        // Sengaja kosong: kolom-kolom ini milik migrasi 2025_02_01_*; migrasi ini hanya memperbaiki.
    }
};
