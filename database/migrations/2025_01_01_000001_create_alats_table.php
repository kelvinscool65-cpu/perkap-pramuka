<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * FIX dari brief asli: kolom `satuan` ditambahkan karena dipakai di data
     * AlatSeeder (Pcs/Biji/Pasang) tapi sebelumnya tidak ada di skema tabel.
     */
    public function up(): void
    {
        Schema::create('alats', function (Blueprint $table) {
            $table->id();
            $table->string('kode_alat')->unique();
            $table->string('nama_alat');
            $table->string('kategori')->index();
            $table->text('deskripsi')->nullable();
            $table->string('satuan')->default('Pcs');
            $table->unsignedInteger('stok_total')->default(0);
            $table->unsignedInteger('stok_tersedia')->default(0);
            $table->enum('kondisi', ['good', 'damaged', 'lost'])->default('good');
            $table->enum('status', ['tersedia', 'dipinjam', 'perawatan'])->default('tersedia');
            $table->string('gambar')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alats');
    }
};
