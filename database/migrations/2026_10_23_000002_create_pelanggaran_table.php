<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catatan pelanggaran peminjam, dicatat MANUAL oleh petugas. Dasar keputusan memblokir akun
     * (mis. 3× "penipuan" → akun layak diblokir). Riwayat tidak dihapus saat akun diblokir/dihapus.
     */
    public function up(): void
    {
        if (Schema::hasTable('pelanggaran')) {
            return;
        }

        Schema::create('pelanggaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('pengajuan_id')->nullable()->constrained('pengajuan_peminjaman')->nullOnDelete();
            // penipuan | kerusakan | kehilangan | terlambat | lainnya
            $table->string('jenis', 20);
            $table->text('keterangan');
            $table->foreignId('dicatat_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'jenis']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pelanggaran');
    }
};
