<?php

namespace App\Services;

use App\Models\Notifikasi;
use App\Models\Pelanggaran;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Semua tindakan petugas terhadap akun peminjam: blokir, buka blokir, hapus, pulihkan, dan catat
 * pelanggaran. Dipusatkan di sini (bukan di controller) supaya aturannya konsisten dan mudah diuji.
 *
 * Setiap pelanggaran aturan dilaporkan sebagai DomainException berpesan Bahasa Indonesia yang
 * langsung layak ditampilkan ke petugas.
 */
class ManajemenAkun
{
    /** Pengajuan yang berarti barang MASIH di tangan peminjam. */
    private const STATUS_BARANG_DIPINJAM = 'approved';

    public function blokir(User $target, User $admin, string $alasan): void
    {
        $this->pastikanBisaDikelola($target, $admin);

        if ($target->trashed()) {
            throw new DomainException('Akun ini sudah dihapus. Pulihkan dulu bila ingin memblokirnya.');
        }
        if ($target->isDiblokir()) {
            throw new DomainException('Akun ini sudah dalam keadaan diblokir.');
        }

        DB::transaction(function () use ($target, $admin, $alasan) {
            $target->forceFill([
                'status_akun' => User::STATUS_DIBLOKIR,
                'diblokir_pada' => now(),
                'alasan_blokir' => $alasan,
                'diblokir_oleh' => $admin->id,
                // cookie "ingat saya" yang sudah beredar jadi tidak berlaku
                'remember_token' => null,
            ])->save();

            // Pengajuan yang masih menunggu tidak mungkin disetujui lagi → tolak otomatis.
            $this->tolakPengajuanMenunggu($target, $admin, 'Ditolak otomatis: akun peminjam diblokir oleh petugas.');
        });

        $this->akhiriSesi($target);
        Log::info("Akun #{$target->id} ({$target->kode_anggota}) diblokir oleh petugas #{$admin->id}: {$alasan}");
    }

    public function bukaBlokir(User $target, User $admin): void
    {
        $this->pastikanBisaDikelola($target, $admin);

        if (! $target->isDiblokir()) {
            throw new DomainException('Akun ini tidak sedang diblokir.');
        }

        // diblokir_pada / alasan_blokir / diblokir_oleh SENGAJA dipertahankan sebagai jejak
        // "blokir terakhir"; yang menentukan akses hanyalah status_akun.
        $target->forceFill(['status_akun' => User::STATUS_AKTIF])->save();

        Notifikasi::create([
            'user_id' => $target->id,
            'judul' => 'Blokir Akun Dicabut',
            'pesan' => 'Petugas telah membuka kembali akun Anda. Mohon patuhi tata tertib peminjaman agar akun tidak diblokir lagi.',
            'tipe' => 'info',
        ]);

        Log::info("Akun #{$target->id} ({$target->kode_anggota}) dibuka blokirnya oleh petugas #{$admin->id}");
    }

    /**
     * "Hapus" = soft delete. Akun hilang dari daftar dan tidak bisa masuk lagi, tetapi riwayat
     * peminjaman tetap utuh untuk laporan, dan identitasnya (ID Anggota, email, HP, username)
     * tetap TERPESAN sehingga tidak bisa dipakai mendaftar ulang.
     */
    public function hapus(User $target, User $admin): void
    {
        $this->pastikanBisaDikelola($target, $admin);

        if ($target->trashed()) {
            throw new DomainException('Akun ini sudah dihapus.');
        }

        $dipinjam = $target->pengajuan()->where('status_pengajuan', self::STATUS_BARANG_DIPINJAM)->count();
        if ($dipinjam > 0) {
            throw new DomainException(
                "Akun belum bisa dihapus: masih ada {$dipinjam} peminjaman aktif (barang belum dikembalikan). "
                .'Proses pengembaliannya dulu di menu Peminjaman agar stok tidak ikut hilang.'
            );
        }

        DB::transaction(function () use ($target, $admin) {
            $this->tolakPengajuanMenunggu($target, $admin, 'Ditolak otomatis: akun peminjam dihapus oleh petugas.');
            $target->delete();
        });

        $this->akhiriSesi($target);
        Log::info("Akun #{$target->id} ({$target->kode_anggota}) dihapus oleh petugas #{$admin->id}");
    }

    public function pulihkan(User $target, User $admin): void
    {
        $this->pastikanBisaDikelola($target, $admin);

        if (! $target->trashed()) {
            throw new DomainException('Akun ini tidak dalam keadaan dihapus.');
        }

        $target->restore();
        Log::info("Akun #{$target->id} ({$target->kode_anggota}) dipulihkan oleh petugas #{$admin->id}");
    }

    /**
     * Catat pelanggaran, beri tahu peminjam, dan — bila jumlah "penipuan" tepat mencapai batas —
     * beri tahu seluruh petugas bahwa akun ini layak ditinjau/diblokir.
     *
     * @param  array{jenis:string, keterangan:string, pengajuan_id?:int|null}  $data
     */
    public function catatPelanggaran(User $target, User $admin, array $data): Pelanggaran
    {
        $this->pastikanBisaDikelola($target, $admin);

        if (! in_array($data['jenis'], Pelanggaran::daftarJenis(), true)) {
            throw new DomainException('Jenis pelanggaran tidak dikenal.');
        }

        $pelanggaran = Pelanggaran::create([
            'user_id' => $target->id,
            'pengajuan_id' => $data['pengajuan_id'] ?? null,
            'jenis' => $data['jenis'],
            'keterangan' => $data['keterangan'],
            'dicatat_oleh' => $admin->id,
        ]);

        $batas = (int) config('perkap.batas_penipuan', 3);
        $label = $pelanggaran->label_jenis;

        $pesan = "Petugas mencatat pelanggaran \"{$label}\" pada akun Anda: {$data['keterangan']}";
        if ($data['jenis'] === 'penipuan') {
            $jumlah = $target->pelanggaran()->where('jenis', 'penipuan')->count();
            $pesan .= " (penipuan ke-{$jumlah} dari batas {$batas}). Pelanggaran penipuan sebanyak {$batas} kali dapat berujung pada pemblokiran akun.";
        }

        Notifikasi::create([
            'user_id' => $target->id,
            'judul' => 'Catatan Pelanggaran',
            'pesan' => $pesan,
            'tipe' => 'danger',
        ]);

        // Akun yang sudah diblokir tidak perlu "ditinjau" lagi: jangan menyalakan alarm yang sudah tidak relevan.
        if ($data['jenis'] === 'penipuan' && ($jumlah ?? 0) === $batas && ! $target->isDiblokir()) {
            foreach (User::where('role', 'petugas_perkap')->get() as $petugas) {
                Notifikasi::create([
                    'user_id' => $petugas->id,
                    'judul' => 'Akun Layak Diblokir',
                    'pesan' => "{$target->nama} ({$target->kode_anggota}) telah dicatat melakukan penipuan peminjaman {$batas} kali. Silakan tinjau dan putuskan pemblokiran di menu Pengguna.",
                    'tipe' => 'warning',
                ]);
            }
        }

        return $pelanggaran;
    }

    public function hapusPelanggaran(Pelanggaran $pelanggaran, User $admin): void
    {
        if (! $admin->isAdmin()) {
            throw new DomainException('Hanya petugas yang dapat menghapus catatan pelanggaran.');
        }

        $pelanggaran->delete();
    }

    // ----- internal -----

    /** Hanya akun PEMINJAM yang boleh dikelola, dan petugas tidak boleh menyasar dirinya sendiri. */
    private function pastikanBisaDikelola(User $target, User $admin): void
    {
        if (! $admin->isAdmin()) {
            throw new DomainException('Hanya petugas Perkap yang dapat mengelola akun pengguna.');
        }
        if ($target->is($admin)) {
            throw new DomainException('Anda tidak dapat mengelola akun Anda sendiri.');
        }
        if (! $target->isPeminjam()) {
            throw new DomainException('Hanya akun peminjam yang dapat dikelola di sini. Akun petugas tidak dapat diblokir atau dihapus.');
        }
    }

    private function tolakPengajuanMenunggu(User $target, User $admin, string $catatan): void
    {
        $target->pengajuan()->where('status_pengajuan', 'pending')->update([
            'status_pengajuan' => 'rejected',
            'petugas_id' => $admin->id,
            'catatan_admin' => $catatan,
        ]);
    }

    /** Putuskan sesi aktif pemilik akun (driver session "database"); driver lain ditangani middleware. */
    private function akhiriSesi(User $target): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        try {
            DB::table(config('session.table', 'sessions'))->where('user_id', $target->id)->delete();
        } catch (\Throwable $e) {
            Log::warning('Gagal memutus sesi akun #'.$target->id.': '.$e->getMessage());
        }
    }
}
