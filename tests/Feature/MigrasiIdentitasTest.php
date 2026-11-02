<?php

namespace Tests\Feature;

use App\Support\KodeAnggota;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Migrasi identitas harus aman untuk database LAMA yang sudah berisi pengguna (kasus nyata: pengguna
 * yang meng-upgrade hanya menjalankan `php artisan migrate`, tanpa seed ulang).
 * Memakai DatabaseMigrations (bukan transaksi) karena tes ini mengubah skema.
 */
class MigrasiIdentitasTest extends TestCase
{
    use DatabaseMigrations;

    private object $migrasi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->migrasi = require database_path('migrations/2026_10_23_000001_add_identitas_dan_status_to_users_table.php');
    }

    private function pengguna(int $id, string $nama, string $email, ?string $noHp, string $dibuat, string $role = 'peminjam', ?string $dihapus = null): array
    {
        return [
            'id' => $id, 'nama' => $nama, 'email' => $email, 'username' => 'user'.$id, 'password' => 'x', 'no_hp' => $noHp,
            'role' => $role, 'created_at' => $dibuat, 'updated_at' => $dibuat, 'deleted_at' => $dihapus,
        ];
    }

    private function kembaliKeSkemaLama(): void
    {
        $this->migrasi->down();

        $this->assertFalse(Schema::hasColumn('users', 'kode_anggota'));
        $this->assertFalse(Schema::hasColumn('users', 'status_akun'));
    }

    #[Test]
    public function pengguna_lama_otomatis_mendapat_id_dan_nilai_baku_tanpa_membuat_migrasi_gagal(): void
    {
        $this->kembaliKeSkemaLama();

        DB::table('users')->insert([
            $this->pengguna(1, 'Admin Lama', 'admin@lama.test', '081234567890', '2025-03-01 08:00:00', 'petugas_perkap'),
            $this->pengguna(2, 'Budi', 'budi.s@gmail.com', '+62 812-3456-7891', '2025-06-01 08:00:00'),
            // kembar dengan #2 setelah dibakukan (nomor sama, Gmail sama): migrasi tidak boleh gagal
            $this->pengguna(3, 'Budi Kembar', 'budis+2@gmail.com', '081234567891', '2026-01-15 08:00:00'),
            $this->pengguna(4, 'Cici', 'cici@lama.test', null, '2026-02-01 08:00:00'),
            $this->pengguna(5, 'Dodo Terhapus', 'dodo@lama.test', '081299990000', '2024-12-31 08:00:00', 'peminjam', '2025-01-10 08:00:00'),
        ]);

        $this->migrasi->up();

        $baris = DB::table('users')->orderBy('id')->get()->keyBy('id');

        // semua dapat ID unik yang valid, tahunnya mengikuti tanggal akun dibuat, status 'aktif'
        $this->assertCount(5, $baris->pluck('kode_anggota')->unique());
        foreach ($baris as $b) {
            $this->assertTrue(KodeAnggota::valid($b->kode_anggota), "ID {$b->kode_anggota} tidak valid");
            $this->assertSame('aktif', $b->status_akun);
        }
        $this->assertSame('25', explode('-', $baris[1]->kode_anggota)[1]);
        $this->assertSame('26', explode('-', $baris[3]->kode_anggota)[1]);
        $this->assertSame('24', explode('-', $baris[5]->kode_anggota)[1]);

        // nilai baku: yang ber-id lebih kecil mempertahankan, yang kembar dibiarkan NULL
        $this->assertSame('6281234567890', $baris[1]->no_hp_normal);
        $this->assertSame('6281234567891', $baris[2]->no_hp_normal);
        $this->assertNull($baris[3]->no_hp_normal);
        $this->assertSame('budis@gmail.com', $baris[2]->email_kanonik);
        $this->assertNull($baris[3]->email_kanonik);
        $this->assertNull($baris[4]->no_hp_normal);
        $this->assertSame('cici@lama.test', $baris[4]->email_kanonik);
        $this->assertSame('6281299990000', $baris[5]->no_hp_normal);

        // pengaman database aktif setelah migrasi
        $this->expectException(QueryException::class);
        DB::table('users')->where('id', 1)->update(['kode_anggota' => 'PRK-25-ABCDEF-1']);
    }

    #[Test]
    public function migrasi_aman_dijalankan_ulang_dan_tidak_mengubah_id_yang_sudah_terbit(): void
    {
        $this->kembaliKeSkemaLama();
        DB::table('users')->insert([
            $this->pengguna(1, 'Satu', 'satu@lama.test', '081200000001', '2025-03-01 08:00:00'),
            $this->pengguna(2, 'Dua', 'dua@lama.test', '081200000002', '2025-04-01 08:00:00'),
        ]);

        $this->migrasi->up();
        $pertama = DB::table('users')->orderBy('id')->pluck('kode_anggota', 'id')->all();

        $this->migrasi->up(); // kedua kali: tidak error, tidak mengubah apa pun
        $kedua = DB::table('users')->orderBy('id')->pluck('kode_anggota', 'id')->all();

        $this->assertSame($pertama, $kedua);
    }

    #[Test]
    public function migrasi_bisa_dimundurkan_lalu_dimajukan_lagi(): void
    {
        $this->kembaliKeSkemaLama();
        $this->migrasi->up();

        $this->assertTrue(Schema::hasColumn('users', 'kode_anggota'));
        $this->assertTrue(Schema::hasColumn('users', 'email_kanonik'));
        $this->assertTrue(Schema::hasColumn('users', 'no_hp_normal'));
        $this->assertTrue(Schema::hasColumn('users', 'syarat_disetujui_pada'));
    }
}
