<?php

namespace Tests;

use App\Models\Alat;
use App\Models\PengajuanPeminjaman;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    private static int $urutan = 0;

    protected function setUp(): void
    {
        parent::setUp();

        // Tema memakai @vite; di lingkungan tes aset tidak dibuild, jadi tag-nya dinonaktifkan.
        $this->withoutVite();
    }

    protected function peminjam(array $atribut = []): User
    {
        return User::factory()->create($atribut);
    }

    protected function admin(array $atribut = []): User
    {
        return User::factory()->admin()->create($atribut);
    }

    /** Data pendaftaran yang VALID dan unik pada tiap pemanggilan; $timpa mengganti sebagian isian. */
    protected function dataDaftar(array $timpa = []): array
    {
        $n = ++self::$urutan;

        return array_merge([
            'nama' => 'Siswa Percobaan',
            'username' => 'siswa_'.$n.'_'.Str::lower(Str::random(4)),
            'email' => "siswa{$n}.".Str::lower(Str::random(5)).'@example.org',
            'no_hp' => '0812'.str_pad((string) $n, 8, '0', STR_PAD_LEFT),
            'password' => 'RahasiaKu123',
            'password_confirmation' => 'RahasiaKu123',
            'setuju' => '1',
        ], $timpa);
    }

    /**
     * Kirim formulir daftar dari alamat IP tertentu. Sesi & guard direset dulu supaya pendaftaran
     * sebelumnya (yang otomatis login) tidak membuat middleware `guest` mengalihkan permintaan ini.
     */
    protected function daftar(array $timpa = [], string $ip = '10.0.0.1')
    {
        $this->keluar();

        return $this->withServerVariables(['REMOTE_ADDR' => $ip])->post('/register', $this->dataDaftar($timpa));
    }

    protected function keluar(): void
    {
        $this->app['auth']->forgetGuards();
        $this->flushSession();
    }

    /** Pengajuan milik $user dengan satu barang. Alat dibuat sekali lalu dipakai ulang. */
    protected function pengajuan(User $user, string $status = 'pending', int $jumlah = 1): PengajuanPeminjaman
    {
        $alat = Alat::first() ?? Alat::create([
            'kode_alat' => 'TDA-001', 'nama_alat' => 'Tenda Regu', 'kategori' => 'Tenda', 'satuan' => 'Pcs',
            'stok_total' => 10, 'stok_tersedia' => 10, 'harga_sewa' => 5000,
        ]);

        $pengajuan = PengajuanPeminjaman::create([
            'user_id' => $user->id,
            'tanggal_pinjam' => now()->addDay()->toDateString(),
            'tanggal_kembali' => now()->addDays(3)->toDateString(),
            'keperluan' => 'Latihan rutin regu',
            'status_pengajuan' => $status,
        ]);
        $pengajuan->details()->create(['alat_id' => $alat->id, 'jumlah' => $jumlah, 'harga_saat_pinjam' => $alat->harga_sewa]);

        return $pengajuan;
    }
}
