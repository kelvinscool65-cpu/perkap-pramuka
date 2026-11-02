<?php

namespace Database\Seeders;

use App\Models\Alat;
use Illuminate\Database\Seeder;

class AlatSeeder extends Seeder
{
    /**
     * 52 barang perkap sesuai daftar wajib di brief.
     * stok_tersedia = stok_total di awal, kondisi = good, status = tersedia.
     * kode_alat digenerate otomatis per kategori (TDA-001, SRG-001, dst)
     * lewat Alat::generateKodeAlat() supaya konsisten dengan yang dipakai AlatController.
     *
     * `harga_sewa` (Rp/peminjaman): NILAI DIKARANG untuk keperluan demo —
     * wajar secara relatif (barang kecil/atribut lebih murah, seragam &
     * tenda lebih mahal) dan bisa diubah admin kapan saja lewat halaman
     * Perlengkapan > Edit.
     */
    public function run(): void
    {
        $alats = [
            ['nama_alat' => 'SB', 'kategori' => 'Tenda', 'stok_total' => 3, 'satuan' => 'Pcs', 'harga_sewa' => 10000],
            ['nama_alat' => 'Matras Halus', 'kategori' => 'Tenda', 'stok_total' => 1, 'satuan' => 'Pcs', 'harga_sewa' => 5000],
            ['nama_alat' => 'Matras Kasar', 'kategori' => 'Tenda', 'stok_total' => 6, 'satuan' => 'Pcs', 'harga_sewa' => 5000],
            ['nama_alat' => 'Kompor', 'kategori' => 'Peralatan', 'stok_total' => 2, 'satuan' => 'Pcs', 'harga_sewa' => 10000],
            ['nama_alat' => 'Tiker Hijau', 'kategori' => 'Tenda', 'stok_total' => 1, 'satuan' => 'Pcs', 'harga_sewa' => 5000],
            ['nama_alat' => 'Pasak Tenda Dum', 'kategori' => 'Tenda', 'stok_total' => 10, 'satuan' => 'Biji', 'harga_sewa' => 1000],
            ['nama_alat' => 'Wadah Semaphore', 'kategori' => 'Semaphore', 'stok_total' => 10, 'satuan' => 'Biji', 'harga_sewa' => 2000],
            ['nama_alat' => 'Semaphore Lama', 'kategori' => 'Semaphore', 'stok_total' => 10, 'satuan' => 'Pasang', 'harga_sewa' => 3000],
            ['nama_alat' => 'Semaphore Baru', 'kategori' => 'Semaphore', 'stok_total' => 10, 'satuan' => 'Pasang', 'harga_sewa' => 4000],
            ['nama_alat' => 'Tongkat Semaphore', 'kategori' => 'Semaphore', 'stok_total' => 6, 'satuan' => 'Biji', 'harga_sewa' => 2000],
            ['nama_alat' => 'Kaos Tangan', 'kategori' => 'Seragam', 'stok_total' => 32, 'satuan' => 'Pasang', 'harga_sewa' => 2000],
            ['nama_alat' => 'Tenda Regu Biru', 'kategori' => 'Tenda', 'stok_total' => 1, 'satuan' => 'Pcs', 'harga_sewa' => 30000],
            ['nama_alat' => 'Topi Boni Lama', 'kategori' => 'Seragam', 'stok_total' => 12, 'satuan' => 'Pcs', 'harga_sewa' => 3000],
            ['nama_alat' => 'Topi Boni Baru', 'kategori' => 'Seragam', 'stok_total' => 15, 'satuan' => 'Pcs', 'harga_sewa' => 4000],
            ['nama_alat' => 'Sampur Biru', 'kategori' => 'Atribut', 'stok_total' => 9, 'satuan' => 'Pcs', 'harga_sewa' => 3000],
            ['nama_alat' => 'Kopel', 'kategori' => 'Atribut', 'stok_total' => 1, 'satuan' => 'Biji', 'harga_sewa' => 3000],
            ['nama_alat' => 'Kain Celana Kontingen', 'kategori' => 'Seragam', 'stok_total' => 1, 'satuan' => 'Pcs', 'harga_sewa' => 5000],
            ['nama_alat' => 'Kain Baju Kontingen', 'kategori' => 'Seragam', 'stok_total' => 1, 'satuan' => 'Pcs', 'harga_sewa' => 5000],
            ['nama_alat' => 'Tali Komando Hitam', 'kategori' => 'Atribut', 'stok_total' => 15, 'satuan' => 'Pcs', 'harga_sewa' => 2000],
            ['nama_alat' => 'Tali Komando Coklat', 'kategori' => 'Atribut', 'stok_total' => 12, 'satuan' => 'Pcs', 'harga_sewa' => 2000],
            ['nama_alat' => 'Kolongan Besi Kuning', 'kategori' => 'Atribut', 'stok_total' => 11, 'satuan' => 'Pcs', 'harga_sewa' => 2000],
            ['nama_alat' => 'Kolongan Tunar', 'kategori' => 'Atribut', 'stok_total' => 12, 'satuan' => 'Pcs', 'harga_sewa' => 2000],
            ['nama_alat' => 'Kolongan Rotan', 'kategori' => 'Atribut', 'stok_total' => 3, 'satuan' => 'Pcs', 'harga_sewa' => 2000],
            ['nama_alat' => 'Ikat Pinggang', 'kategori' => 'Atribut', 'stok_total' => 9, 'satuan' => 'Pcs', 'harga_sewa' => 4000],
            ['nama_alat' => 'Baret Baru', 'kategori' => 'Seragam', 'stok_total' => 14, 'satuan' => 'Pcs', 'harga_sewa' => 5000],
            ['nama_alat' => 'Baret Lama', 'kategori' => 'Seragam', 'stok_total' => 16, 'satuan' => 'Pcs', 'harga_sewa' => 4000],
            ['nama_alat' => 'Dek Laksana', 'kategori' => 'Atribut', 'stok_total' => 31, 'satuan' => 'Pasang', 'harga_sewa' => 3000],
            ['nama_alat' => 'Dek Bantara', 'kategori' => 'Atribut', 'stok_total' => 29, 'satuan' => 'Pasang', 'harga_sewa' => 3000],
            ['nama_alat' => 'Pin Baret', 'kategori' => 'Atribut', 'stok_total' => 22, 'satuan' => 'Pcs', 'harga_sewa' => 1000],
            ['nama_alat' => 'Pin Dewan', 'kategori' => 'Atribut', 'stok_total' => 1, 'satuan' => 'Pcs', 'harga_sewa' => 1000],
            ['nama_alat' => 'Pin Nama', 'kategori' => 'Atribut', 'stok_total' => 16, 'satuan' => 'Pcs', 'harga_sewa' => 1000],
            ['nama_alat' => 'Hasduk', 'kategori' => 'Atribut', 'stok_total' => 66, 'satuan' => 'Pcs', 'harga_sewa' => 3000],
            ['nama_alat' => 'Ban Lengan DSW', 'kategori' => 'Atribut', 'stok_total' => 8, 'satuan' => 'Pcs', 'harga_sewa' => 2000],
            ['nama_alat' => 'Ban Lengan WKP', 'kategori' => 'Atribut', 'stok_total' => 8, 'satuan' => 'Pcs', 'harga_sewa' => 2000],
            ['nama_alat' => 'Karpet Putih Kuning', 'kategori' => 'Peralatan', 'stok_total' => 1, 'satuan' => 'Pcs', 'harga_sewa' => 10000],
            ['nama_alat' => 'Tenda Regu', 'kategori' => 'Tenda', 'stok_total' => 8, 'satuan' => 'Pcs', 'harga_sewa' => 25000],
            ['nama_alat' => 'Scraf Merah', 'kategori' => 'Atribut', 'stok_total' => 8, 'satuan' => 'Pcs', 'harga_sewa' => 3000],
            ['nama_alat' => 'Bendera Merah Putih', 'kategori' => 'Peralatan', 'stok_total' => 6, 'satuan' => 'Pcs', 'harga_sewa' => 5000],
            ['nama_alat' => 'Wosm', 'kategori' => 'Atribut', 'stok_total' => 6, 'satuan' => 'Pcs', 'harga_sewa' => 1000],
            ['nama_alat' => 'Cikal', 'kategori' => 'Atribut', 'stok_total' => 29, 'satuan' => 'Pcs', 'harga_sewa' => 1000],
            ['nama_alat' => 'Baju Kontingen Cowok New', 'kategori' => 'Seragam', 'stok_total' => 6, 'satuan' => 'Pcs', 'harga_sewa' => 15000],
            ['nama_alat' => 'Baju Kontingen Cowok Lama', 'kategori' => 'Seragam', 'stok_total' => 17, 'satuan' => 'Pcs', 'harga_sewa' => 10000],
            ['nama_alat' => 'Celana Kontingen Cowok New', 'kategori' => 'Seragam', 'stok_total' => 6, 'satuan' => 'Pcs', 'harga_sewa' => 15000],
            ['nama_alat' => 'Celana Kontingen Cowok Lama', 'kategori' => 'Seragam', 'stok_total' => 10, 'satuan' => 'Pcs', 'harga_sewa' => 10000],
            ['nama_alat' => 'Baju Kontingen Cewek', 'kategori' => 'Seragam', 'stok_total' => 3, 'satuan' => 'Pcs', 'harga_sewa' => 15000],
            ['nama_alat' => 'Celana Kontingen Cewek', 'kategori' => 'Seragam', 'stok_total' => 2, 'satuan' => 'Pcs', 'harga_sewa' => 15000],
            ['nama_alat' => 'Topi Rimba Hitam', 'kategori' => 'Seragam', 'stok_total' => 20, 'satuan' => 'Pcs', 'harga_sewa' => 5000],
            ['nama_alat' => 'Rompi Hitam', 'kategori' => 'Seragam', 'stok_total' => 5, 'satuan' => 'Pcs', 'harga_sewa' => 8000],
            ['nama_alat' => 'Scraf Hitam Putih', 'kategori' => 'Atribut', 'stok_total' => 15, 'satuan' => 'Pcs', 'harga_sewa' => 3000],
            ['nama_alat' => 'Bendera Latihan', 'kategori' => 'Peralatan', 'stok_total' => 1, 'satuan' => 'Pcs', 'harga_sewa' => 5000],
            ['nama_alat' => 'Bendera Morse', 'kategori' => 'Peralatan', 'stok_total' => 2, 'satuan' => 'Pcs', 'harga_sewa' => 5000],
            ['nama_alat' => 'Tongkat Pramuka', 'kategori' => 'Peralatan', 'stok_total' => 1000, 'satuan' => 'Pcs', 'harga_sewa' => 2000],
        ];

        foreach ($alats as $item) {
            Alat::create([
                'kode_alat' => Alat::generateKodeAlat($item['kategori']),
                'nama_alat' => $item['nama_alat'],
                'kategori' => $item['kategori'],
                'satuan' => $item['satuan'],
                'stok_total' => $item['stok_total'],
                'stok_tersedia' => $item['stok_total'],
                'harga_sewa' => $item['harga_sewa'],
                'kondisi' => 'good',
                'status' => 'tersedia',
            ]);
        }
    }
}
