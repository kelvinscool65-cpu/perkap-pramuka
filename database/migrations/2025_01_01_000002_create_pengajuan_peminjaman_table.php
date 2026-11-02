<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengajuan_peminjaman', function (Blueprint $table) {
            $table->id();
            $table->string('kode_pengajuan')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('petugas_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('tanggal_pinjam');
            $table->date('tanggal_kembali');
            $table->text('keperluan');
            $table->enum('status_pengajuan', ['pending', 'approved', 'rejected', 'returned'])->default('pending');
            $table->enum('status_akhir', ['good', 'damaged', 'lost'])->nullable();
            $table->text('catatan_admin')->nullable();
            $table->text('catatan_pengembalian')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengajuan_peminjaman');
    }
};
