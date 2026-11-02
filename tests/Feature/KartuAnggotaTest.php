<?php

namespace Tests\Feature;

use App\Models\Pelanggaran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KartuAnggotaTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function peminjam_melihat_kartu_dengan_id_dan_sandi_morse(): void
    {
        $user = $this->peminjam(['nama' => 'Dewi Lestari']);

        $this->actingAs($user)->get(route('user.kartu'))
            ->assertOk()
            ->assertSee($user->kode_anggota)
            ->assertSee('Kartu Anggota')
            ->assertSee('Permanen')
            ->assertSee('Dewi Lestari')
            ->assertSee(route('verifikasi.cek', $user->kode_anggota), false)
            ->assertSee('Belum ada catatan pelanggaran');
    }

    #[Test]
    public function kartu_menampilkan_catatan_pelanggaran_milik_sendiri_saja(): void
    {
        $saya = $this->peminjam();
        $orangLain = $this->peminjam();
        $petugas = $this->admin();
        Pelanggaran::create(['user_id' => $saya->id, 'jenis' => 'penipuan', 'keterangan' => 'Catatan milik saya', 'dicatat_oleh' => $petugas->id]);
        Pelanggaran::create(['user_id' => $orangLain->id, 'jenis' => 'penipuan', 'keterangan' => 'Catatan milik orang lain', 'dicatat_oleh' => $petugas->id]);

        $this->actingAs($saya)->get(route('user.kartu'))
            ->assertOk()
            ->assertSee('Catatan milik saya')
            ->assertSee('Penipuan 1 dari 3')
            ->assertDontSee('Catatan milik orang lain');
    }

    #[Test]
    public function kilau_hanya_muncul_pada_kartu_yang_baru_dibuat(): void
    {
        $user = $this->peminjam();

        $this->actingAs($user)->get(route('user.kartu'))->assertOk()->assertDontSee('kilau');
        $this->withSession(['baru_daftar' => true])->get(route('user.kartu'))->assertOk()->assertSee('kilau')->assertSee('Akun siap dipakai');
    }

    #[Test]
    public function kartu_hanya_untuk_peminjam_yang_sudah_masuk(): void
    {
        $this->get(route('user.kartu'))->assertRedirect(route('login'));
        $this->actingAs($this->admin())->get(route('user.kartu'))->assertForbidden();
    }

    #[Test]
    public function profil_menampilkan_id_hanya_baca_untuk_peminjam_dan_petugas(): void
    {
        $peminjam = $this->peminjam();
        $this->actingAs($peminjam)->get(route('user.profile.edit'))
            ->assertOk()
            ->assertSee($peminjam->kode_anggota)
            ->assertSee('Permanen')
            ->assertSee('Lihat kartu')
            ->assertDontSee('name="kode_anggota"', false);

        $petugas = $this->admin();
        $this->actingAs($petugas)->get(route('admin.profile.edit'))
            ->assertOk()
            ->assertSee($petugas->kode_anggota)
            ->assertDontSee('Lihat kartu')
            ->assertDontSee('name="kode_anggota"', false);
    }

    #[Test]
    public function menu_peminjam_menautkan_ke_kartu_anggota(): void
    {
        $this->actingAs($this->peminjam())->get(route('user.dashboard'))
            ->assertOk()
            ->assertSee(route('user.kartu'), false)
            ->assertSee('Kartu Anggota');
    }

    #[Test]
    public function halaman_galat_berbahasa_indonesia(): void
    {
        $this->get('/halaman-yang-tidak-ada')->assertNotFound()->assertSee('Halaman tidak ditemukan')->assertSee('Kode galat 404');
        $this->actingAs($this->peminjam())->get(route('admin.dashboard'))->assertForbidden()->assertSee('Akses ditolak');
    }
}
