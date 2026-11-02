<?php

namespace Database\Seeders;

use App\Models\Alat;
use App\Models\Notifikasi;
use App\Models\PengajuanPeminjaman;
use App\Models\User;
use Illuminate\Database\Seeder;

class PengajuanSeeder extends Seeder
{
    /**
     * IDE TAMBAHAN: seeder ini bukan bagian dari brief asli, tapi ditambahkan
     * supaya dashboard admin & riwayat user tidak kosong saat pertama dibuka —
     * sangat membantu untuk screenshot lampiran LKPD. Mencakup 4 skenario
     * status (pending, approved, rejected, returned) sesuai activity diagram.
     */
    public function run(): void
    {
        $admin = User::where('role', 'petugas_perkap')->first();
        $peminjam1 = User::where('username', 'peminjam1')->first();
        $siti = User::where('username', 'siti_n')->first();
        $rizky = User::where('username', 'rizky_r')->first();

        if (! $admin || ! $peminjam1) {
            return; // jaga-jaga jika UserSeeder belum jalan
        }

        // 1) Skenario PENDING — menunggu keputusan admin
        $p1 = PengajuanPeminjaman::create([
            'user_id' => $siti->id,
            'tanggal_pinjam' => now()->addDays(2)->toDateString(),
            'tanggal_kembali' => now()->addDays(4)->toDateString(),
            'keperluan' => 'Latihan rutin regu Penggalang, Sabtu pagi.',
            'status_pengajuan' => 'pending',
        ]);
        $this->tambahDetail($p1, 'Tenda Regu', 1);
        $this->tambahDetail($p1, 'Kompor', 1);

        // 2) Skenario APPROVED — sudah disetujui, alat sedang dipinjam (stok dikurangi)
        $p2 = PengajuanPeminjaman::create([
            'user_id' => $rizky->id,
            'petugas_id' => $admin->id,
            'tanggal_pinjam' => now()->subDays(1)->toDateString(),
            'tanggal_kembali' => now()->addDays(2)->toDateString(),
            'keperluan' => 'Perkemahan Jumat-Minggu tingkat sekolah.',
            'status_pengajuan' => 'approved',
            'catatan_admin' => 'Disetujui, harap kembalikan tepat waktu.',
            'approved_at' => now()->subHours(20),
        ]);
        $this->tambahDetail($p2, 'Hasduk', 5, kurangiStok: true);
        $this->tambahDetail($p2, 'Tongkat Pramuka', 10, kurangiStok: true);

        // 3) Skenario REJECTED — ditolak admin (stok tidak berubah)
        $p3 = PengajuanPeminjaman::create([
            'user_id' => $peminjam1->id,
            'petugas_id' => $admin->id,
            'tanggal_pinjam' => now()->subDays(3)->toDateString(),
            'tanggal_kembali' => now()->subDays(1)->toDateString(),
            'keperluan' => 'Dokumentasi pribadi, bukan kegiatan resmi.',
            'status_pengajuan' => 'rejected',
            'catatan_admin' => 'Ditolak, peminjaman hanya untuk kegiatan resmi Pramuka.',
        ]);
        $this->tambahDetail($p3, 'Baret Baru', 1);

        // 4) Skenario RETURNED (baik) — siklus penuh, stok sudah kembali
        $p4 = PengajuanPeminjaman::create([
            'user_id' => $peminjam1->id,
            'petugas_id' => $admin->id,
            'tanggal_pinjam' => now()->subDays(10)->toDateString(),
            'tanggal_kembali' => now()->subDays(7)->toDateString(),
            'keperluan' => 'Lomba tingkat (LT) antar sekolah.',
            'status_pengajuan' => 'returned',
            'status_akhir' => 'good',
            'catatan_admin' => 'Disetujui.',
            'catatan_pengembalian' => 'Semua kondisi baik, lengkap.',
            'approved_at' => now()->subDays(10)->addHours(3),
            'returned_at' => now()->subDays(6),
        ]);
        $this->tambahDetail($p4, 'Semaphore Baru', 4, kembaliBaik: true);

        // 5) Skenario RETURNED (rusak) — memicu notifikasi peringatan
        $p5 = PengajuanPeminjaman::create([
            'user_id' => $siti->id,
            'petugas_id' => $admin->id,
            'tanggal_pinjam' => now()->subDays(14)->toDateString(),
            'tanggal_kembali' => now()->subDays(12)->toDateString(),
            'keperluan' => 'Materi tali-temali, latihan mingguan.',
            'status_pengajuan' => 'returned',
            'status_akhir' => 'damaged',
            'approved_at' => now()->subDays(14)->addHours(2),
            'returned_at' => now()->subDays(11),
            'catatan_pengembalian' => '1 tali komando putus saat latihan simpul.',
        ]);
        $this->tambahDetail($p5, 'Tali Komando Hitam', 1, kondisiKembali: 'damaged');

        Notifikasi::create([
            'user_id' => $siti->id,
            'judul' => 'Peringatan Pengembalian',
            'pesan' => "Terdapat item bermasalah (rusak) pada pengembalian {$p5->kode_pengajuan}.",
            'tipe' => 'danger',
        ]);
        Notifikasi::create([
            'user_id' => $admin->id,
            'judul' => 'Pengajuan Baru Menunggu',
            'pesan' => "{$siti->nama} mengajukan peminjaman {$p1->kode_pengajuan}.",
            'tipe' => 'info',
        ]);
    }

    private function tambahDetail(
        PengajuanPeminjaman $pengajuan,
        string $namaAlat,
        int $jumlah,
        bool $kurangiStok = false,
        bool $kembaliBaik = false,
        ?string $kondisiKembali = null
    ): void {
        $alat = Alat::where('nama_alat', $namaAlat)->first();
        if (! $alat) {
            return;
        }

        $pengajuan->details()->create([
            'alat_id' => $alat->id,
            'jumlah' => $jumlah,
            'harga_saat_pinjam' => $alat->harga_sewa,
            'kondisi_saat_kembali' => $kondisiKembali ?? ($kembaliBaik ? 'good' : null),
        ]);

        if ($kurangiStok) {
            $alat->decrement('stok_tersedia', $jumlah);
            $alat->update(['status' => $alat->fresh()->stok_tersedia > 0 ? 'tersedia' : 'dipinjam']);
        }

        if ($kondisiKembali === 'damaged' || $kondisiKembali === 'lost') {
            $alat->decrement('stok_total', $jumlah);
        }
    }
}
