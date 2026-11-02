<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\KodeAnggota;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VerifikasiTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function halaman_verifikasi_terbuka_untuk_umum(): void
    {
        $this->get('/verifikasi')->assertOk()->assertSee('Verifikasi ID Anggota');
    }

    #[Test]
    public function id_sah_menampilkan_nama_tersamarkan_tanpa_data_pribadi(): void
    {
        $user = $this->peminjam(['nama' => 'Ahmad Fauzi', 'email' => 'ahmad.rahasia@example.org', 'no_hp' => '081355559999']);

        $this->get('/verifikasi?kode='.$user->kode_anggota)
            ->assertOk()
            ->assertSee('ID Anggota sah')
            ->assertSee('Ahmad F****')
            ->assertSee('Peminjam')
            ->assertDontSee('Fauzi')
            ->assertDontSee('ahmad.rahasia@example.org')
            ->assertDontSee('0813');
    }

    #[Test]
    public function penulisan_longgar_dan_tautan_langsung_tetap_dikenali(): void
    {
        $user = $this->peminjam(['nama' => 'Siti Nurhaliza']);
        $polos = strtolower(str_replace('-', '', $user->kode_anggota));

        $this->get('/verifikasi/'.$polos)->assertOk()->assertSee('ID Anggota sah');
        $this->get('/verifikasi?kode='.urlencode(strtolower($user->kode_anggota)))->assertOk()->assertSee('ID Anggota sah');
        $this->get('/verifikasi?kode='.urlencode('  '.$user->kode_anggota.'  '))->assertOk()->assertSee('ID Anggota sah');
    }

    #[Test]
    public function id_yang_bentuknya_benar_tetapi_tidak_terdaftar_dinyatakan_demikian(): void
    {
        $kode = KodeAnggota::buat();

        $this->get('/verifikasi/'.$kode)->assertOk()->assertSee('ID tidak terdaftar');
    }

    #[Test]
    public function salah_ketik_dibedakan_dari_id_tidak_terdaftar(): void
    {
        $user = $this->peminjam();
        $salah = substr($user->kode_anggota, 0, -1).(substr($user->kode_anggota, -1) === '0' ? '1' : '0');

        $this->get('/verifikasi/'.$salah)->assertOk()->assertSee('ID kemungkinan salah ketik')->assertDontSee('ID Anggota sah');
    }

    #[Test]
    public function masukan_asal_ditolak_dengan_sopan(): void
    {
        $this->get('/verifikasi?kode=halo-dunia')->assertOk()->assertSee('Bentuk ID tidak dikenali');
        $this->get('/verifikasi/xyz')->assertOk()->assertSee('Bentuk ID tidak dikenali');
        $this->get('/verifikasi?kode=%3Cscript%3Ealert(1)%3C/script%3E')->assertOk()->assertDontSee('<script>alert(1)</script>', false);
    }

    #[Test]
    public function akun_diblokir_atau_dihapus_hanya_disebut_tidak_aktif_tanpa_alasan(): void
    {
        $diblokir = $this->peminjam(['nama' => 'Beni Blokir']);
        $diblokir->forceFill(['status_akun' => 'diblokir', 'alasan_blokir' => 'Alasan rahasia pemblokiran'])->save();
        $dihapus = $this->peminjam(['nama' => 'Hana Hapus']);
        $dihapus->delete();

        foreach ([$diblokir, $dihapus] as $user) {
            $this->get('/verifikasi/'.$user->kode_anggota)
                ->assertOk()
                ->assertSee('Akun tidak aktif')
                ->assertDontSee('ID Anggota sah')
                ->assertDontSee('Alasan rahasia pemblokiran')
                ->assertDontSee(explode(' ', $user->nama)[0]);
        }
    }

    #[Test]
    public function penebakan_massal_dibatasi_30_permintaan_per_menit(): void
    {
        foreach (range(1, 30) as $_) {
            $this->get('/verifikasi/'.KodeAnggota::buat())->assertOk();
        }

        $this->get('/verifikasi/'.KodeAnggota::buat())->assertStatus(429);
        $this->assertSame(0, User::count());
    }
}
