<?php

namespace Database\Seeders;

use App\Models\Notifikasi;
use App\Models\Pelanggaran;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoPenggunaSeeder extends Seeder
{
    /**
     * DATA CONTOH untuk fitur ID Anggota, pelanggaran, dan blokir akun — supaya halaman admin
     * "Pengguna" tidak kosong saat pertama dibuka (berguna untuk screenshot LKPD).
     * Semua akun memakai password "password". Aman dihapus panggilannya di DatabaseSeeder.
     *
     *  - Dimas Prakoso   : 3× penipuan, masih aktif  → muncul di tab "Perlu ditinjau"
     *  - Putri Maharani  : 1× penipuan + 1× terlambat → peringatan dini (belum mencapai batas)
     *  - Bayu Setiawan   : sudah DIBLOKIR (3× penipuan)
     *  - Rendra Aditya   : akun yang DIHAPUS (identitasnya tetap terpesan)
     *
     * Dimas & Bayu sengaja diberi IP daftar yang sama (203.0.113.21, rentang dokumentasi) agar petunjuk
     * "akun lain dari IP yang sama" terlihat di halaman detail.
     */
    public function run(): void
    {
        $admin = User::where('role', 'petugas_perkap')->first();
        if (! $admin) {
            return; // UserSeeder belum jalan
        }

        $dimas = $this->akun('Dimas Prakoso', 'dimas_p', 'dimas@perkap.test', '081311112201', 21, '203.0.113.21');
        if ($dimas) {
            $this->catat($dimas, $admin, 'penipuan', 'Tenda regu tidak dikembalikan sampai batas waktu dan sulit dihubungi.', 18);
            $this->catat($dimas, $admin, 'penipuan', 'Meminjam atas nama adik kelas tanpa sepengetahuan yang bersangkutan.', 11);
            $this->catat($dimas, $admin, 'penipuan', 'Tongkat Pramuka 6 buah dilaporkan hilang, tetapi ditemukan dipinjamkan ke sekolah lain.', 3);
            $this->beriTahuPetugas($admin, $dimas);
        }

        $putri = $this->akun('Putri Maharani', 'putri_m', 'putri@perkap.test', '081311112202', 14, '198.51.100.8');
        if ($putri) {
            $this->catat($putri, $admin, 'penipuan', 'Data keperluan pada pengajuan tidak sesuai dengan kegiatan yang sebenarnya.', 9);
            $this->catat($putri, $admin, 'terlambat', 'Terlambat 3 hari mengembalikan kompor dan tanpa kabar.', 5);
        }

        $bayu = $this->akun('Bayu Setiawan', 'bayu_s', 'bayu@perkap.test', '081311112203', 40, '203.0.113.21');
        if ($bayu) {
            $this->catat($bayu, $admin, 'penipuan', 'Tidak mengembalikan tenda regu yang dipinjam.', 30);
            $this->catat($bayu, $admin, 'penipuan', 'Tidak mengembalikan 4 buah hasduk.', 24);
            $this->catat($bayu, $admin, 'penipuan', 'Tidak mengembalikan tali komando dan menolak dihubungi petugas.', 17);

            $bayu->forceFill([
                'status_akun' => User::STATUS_DIBLOKIR,
                'diblokir_pada' => now()->subDays(15),
                'alasan_blokir' => 'Tercatat tiga kali tidak mengembalikan barang pinjaman (tenda regu, hasduk, tali komando).',
                'diblokir_oleh' => $admin->id,
            ])->save();
        }

        $rendra = $this->akun('Rendra Aditya', 'rendra_a', 'rendra@perkap.test', '081311112204', 60, '192.0.2.77');
        $rendra?->delete();
    }

    /** Buat satu akun contoh (dilewati bila username-nya sudah ada, termasuk yang dihapus). */
    private function akun(string $nama, string $username, string $email, string $noHp, int $hariLalu, string $ip): ?User
    {
        if (User::withTrashed()->where('username', $username)->exists()) {
            return null;
        }

        $user = new User([
            'nama' => $nama,
            'username' => $username,
            'email' => $email,
            'password' => Hash::make('password'),
            'no_hp' => $noHp,
            'role' => 'peminjam',
        ]);

        // Kolom milik sistem tidak boleh lewat mass-assignment → forceFill.
        $user->forceFill([
            'ip_daftar' => $ip,
            'syarat_disetujui_pada' => now()->subDays($hariLalu),
            'created_at' => now()->subDays($hariLalu),
            'updated_at' => now()->subDays($hariLalu),
        ])->save();

        return $user;
    }

    private function catat(User $user, User $admin, string $jenis, string $keterangan, int $hariLalu): void
    {
        $catatan = new Pelanggaran([
            'user_id' => $user->id,
            'jenis' => $jenis,
            'keterangan' => $keterangan,
            'dicatat_oleh' => $admin->id,
        ]);
        $catatan->created_at = now()->subDays($hariLalu);
        $catatan->updated_at = now()->subDays($hariLalu);
        $catatan->save();
    }

    private function beriTahuPetugas(User $admin, User $target): void
    {
        Notifikasi::create([
            'user_id' => $admin->id,
            'judul' => 'Akun Layak Diblokir',
            'pesan' => "{$target->nama} ({$target->kode_anggota}) telah dicatat melakukan penipuan peminjaman 3 kali. Silakan tinjau dan putuskan pemblokiran di menu Pengguna.",
            'tipe' => 'warning',
        ]);
    }
}
