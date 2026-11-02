<?php

namespace Tests\Feature;

use App\Models\Alat;
use App\Models\Notifikasi;
use App\Models\Pelanggaran;
use App\Models\User;
use App\Services\ManajemenAkun;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ManajemenAkunTest extends TestCase
{
    use RefreshDatabase;

    private User $petugas;

    protected function setUp(): void
    {
        parent::setUp();

        $this->petugas = $this->admin(['username' => 'petugas_uji', 'email' => 'petugas.uji@example.org']);
    }

    private function catatPenipuan(User $user, int $jumlah = 1, string $jenis = 'penipuan'): void
    {
        foreach (range(1, $jumlah) as $i) {
            Pelanggaran::create([
                'user_id' => $user->id, 'jenis' => $jenis, 'keterangan' => "Catatan uji {$i}", 'dicatat_oleh' => $this->petugas->id,
            ]);
        }
    }

    private function blokirLangsung(User $user, string $alasan = 'Tercatat menipu tiga kali dalam peminjaman.'): void
    {
        $user->forceFill([
            'status_akun' => 'diblokir', 'alasan_blokir' => $alasan, 'diblokir_pada' => now(), 'diblokir_oleh' => $this->petugas->id,
        ])->save();
    }

    // ---------------------------------------------------------------- hak akses

    #[Test]
    public function hanya_petugas_yang_boleh_membuka_halaman_pengguna(): void
    {
        $target = $this->peminjam();

        $this->get(route('admin.pengguna.index'))->assertRedirect(route('login'));
        $this->actingAs($this->peminjam())->get(route('admin.pengguna.index'))->assertForbidden();
        $this->actingAs($this->peminjam())->get(route('admin.pengguna.show', $target))->assertForbidden();
        $this->actingAs($this->peminjam())->post(route('admin.pengguna.blokir', $target), ['alasan' => 'Percobaan blokir tanpa hak.'])->assertForbidden();
        $this->actingAs($this->peminjam())->delete(route('admin.pengguna.destroy', $target))->assertForbidden();

        $this->assertFalse($target->fresh()->isDiblokir());
        $this->assertFalse($target->fresh()->trashed());
    }

    // ---------------------------------------------------------------- daftar, filter, pencarian, detail

    #[Test]
    public function daftar_dan_detail_tampil_untuk_semua_keadaan_akun(): void
    {
        $normal = $this->peminjam(['nama' => 'Andi Normal']);
        $tinjau = $this->peminjam(['nama' => 'Tono Tinjau']);
        $this->catatPenipuan($tinjau, 3);
        $blokir = $this->peminjam(['nama' => 'Beni Blokir']);
        $this->blokirLangsung($blokir, 'Alasan blokir khusus pengujian.');
        $hapus = $this->peminjam(['nama' => 'Hana Hapus']);
        $hapus->delete();

        $semua = $this->actingAs($this->petugas)->get(route('admin.pengguna.index'))->assertOk();
        foreach (['Andi Normal', 'Tono Tinjau', 'Beni Blokir', 'Hana Hapus'] as $nama) {
            $semua->assertSee($nama);
        }
        $semua->assertSee('Perlu ditinjau')->assertSee($normal->kode_anggota);

        $this->get(route('admin.pengguna.index', ['status' => 'aktif']))
            ->assertSee('Andi Normal')->assertSee('Tono Tinjau')->assertDontSee('Beni Blokir')->assertDontSee('Hana Hapus');
        $this->get(route('admin.pengguna.index', ['status' => 'tinjau']))
            ->assertSee('Tono Tinjau')->assertDontSee('Andi Normal')->assertDontSee('Beni Blokir');
        $this->get(route('admin.pengguna.index', ['status' => 'diblokir']))
            ->assertSee('Beni Blokir')->assertDontSee('Andi Normal')->assertDontSee('Tono Tinjau');
        $this->get(route('admin.pengguna.index', ['status' => 'dihapus']))
            ->assertSee('Hana Hapus')->assertDontSee('Andi Normal')->assertDontSee('Beni Blokir');

        $this->get(route('admin.pengguna.show', $normal))->assertOk()->assertSee('Blokir akun')->assertSee('Hapus akun')->assertSee($normal->kode_anggota);
        $this->get(route('admin.pengguna.show', $tinjau))->assertOk()->assertSee('Blokir sekarang')->assertSee('Penipuan 3 dari 3');
        $this->get(route('admin.pengguna.show', $blokir))->assertOk()->assertSee('Akun ini sedang diblokir')->assertSee('Alasan blokir khusus pengujian.')->assertSee('Buka blokir');
        $this->get(route('admin.pengguna.show', $hapus))->assertOk()->assertSee('Akun ini dihapus pada')->assertSee('Pulihkan akun');
    }

    #[Test]
    public function akun_petugas_tidak_muncul_di_daftar_dan_tidak_punya_halaman_detail(): void
    {
        $lain = $this->admin(['nama' => 'Petugas Lain']);

        $this->actingAs($this->petugas)->get(route('admin.pengguna.index'))->assertOk()->assertDontSee('Petugas Lain');
        $this->get(route('admin.pengguna.show', $lain))->assertNotFound();
    }

    #[Test]
    public function pencarian_menemukan_lewat_id_nomor_email_username_dan_nama(): void
    {
        $sasaran = $this->peminjam(['nama' => 'Cari Aku', 'username' => 'cari_aku', 'email' => 'cari.aku@example.org', 'no_hp' => '081377776666']);
        $this->peminjam(['nama' => 'Orang Lain', 'username' => 'orang_lain', 'email' => 'lain@example.org', 'no_hp' => '081211112222']);

        $cari = [
            strtolower(str_replace('-', '', $sasaran->kode_anggota)),
            $sasaran->kode_anggota,
            '0813-7777-6666',
            '+62 813 7777 6666',
            '6281377776666',
            'cari.aku@example',
            'cari_aku',
            'Cari Aku',
        ];

        foreach ($cari as $q) {
            $this->actingAs($this->petugas)->get(route('admin.pengguna.index', ['q' => $q]))
                ->assertOk()->assertSee('Cari Aku')->assertDontSee('Orang Lain');
        }

        $this->get(route('admin.pengguna.index', ['q' => 'tidak-ada-yang-cocok-xyz']))->assertOk()->assertSee('Tidak ada pengguna yang cocok');
    }

    #[Test]
    public function jendela_blokir_di_daftar_memuat_jumlah_pengajuan_menunggu_yang_benar(): void
    {
        $user = $this->peminjam(['nama' => 'Punya Antrean']);
        $this->pengajuan($user, 'pending');
        $this->pengajuan($user, 'pending');
        $this->pengajuan($user, 'approved'); // yang sudah disetujui tidak ikut dihitung

        // @js() menulis tanda kutip di dalam atribut HTML sebagai urutan 6 karakter: garis miring + u0022
        $kutip = chr(92).'u0022';
        $this->actingAs($this->petugas)->get(route('admin.pengguna.index'))
            ->assertOk()
            ->assertSee($kutip.'menunggu'.$kutip.':2', false);
    }

    #[Test]
    public function petunjuk_akun_lain_dari_ip_yang_sama_muncul_di_detail(): void
    {
        $a = $this->peminjam();
        $b = $this->peminjam();
        $a->forceFill(['ip_daftar' => '203.0.113.21'])->save();
        $b->forceFill(['ip_daftar' => '203.0.113.21'])->save();

        $this->actingAs($this->petugas)->get(route('admin.pengguna.show', $a))
            ->assertOk()->assertSee('1 akun lain mendaftar dari IP yang sama');
    }

    // ---------------------------------------------------------------- blokir

    #[Test]
    public function blokir_mengubah_status_menolak_pengajuan_menunggu_dan_membatalkan_ingat_saya(): void
    {
        $target = $this->peminjam(['remember_token' => 'token-lama']);
        $menunggu = $this->pengajuan($target, 'pending');
        $disetujui = $this->pengajuan($target, 'approved');

        $this->actingAs($this->petugas)
            ->post(route('admin.pengguna.blokir', $target), ['alasan' => 'Tidak mengembalikan tenda sebanyak tiga kali.'])
            ->assertSessionHas('sukses');

        $target->refresh();
        $this->assertTrue($target->isDiblokir());
        $this->assertSame('Tidak mengembalikan tenda sebanyak tiga kali.', $target->alasan_blokir);
        $this->assertSame($this->petugas->id, $target->diblokir_oleh);
        $this->assertNotNull($target->diblokir_pada);
        $this->assertNull($target->remember_token);

        $this->assertSame('rejected', $menunggu->fresh()->status_pengajuan);
        $this->assertSame($this->petugas->id, $menunggu->fresh()->petugas_id);
        $this->assertStringContainsString('diblokir', $menunggu->fresh()->catatan_admin);
        // barang yang sudah dipinjam tidak disentuh: pengembaliannya tetap bisa diproses
        $this->assertSame('approved', $disetujui->fresh()->status_pengajuan);
    }

    #[Test]
    public function alasan_blokir_wajib_dan_minimal_10_karakter(): void
    {
        $target = $this->peminjam();

        $this->actingAs($this->petugas)->post(route('admin.pengguna.blokir', $target), ['alasan' => ''])->assertSessionHasErrors('alasan');
        $this->post(route('admin.pengguna.blokir', $target), ['alasan' => 'pendek'])->assertSessionHasErrors('alasan');

        $this->assertFalse($target->fresh()->isDiblokir());
    }

    #[Test]
    public function memblokir_akun_yang_sudah_diblokir_ditolak_dengan_pesan(): void
    {
        $target = $this->peminjam();
        $this->blokirLangsung($target);

        $this->actingAs($this->petugas)
            ->post(route('admin.pengguna.blokir', $target), ['alasan' => 'Alasan kedua yang cukup panjang.'])
            ->assertSessionHas('gagal');

        $this->assertSame('Tercatat menipu tiga kali dalam peminjaman.', $target->fresh()->alasan_blokir);
    }

    #[Test]
    public function sesi_database_pemilik_akun_langsung_diputus(): void
    {
        config(['session.driver' => 'database']);
        $target = $this->peminjam();
        $lain = $this->peminjam();
        foreach ([['sesi-target-1', $target], ['sesi-target-2', $target], ['sesi-lain', $lain]] as [$id, $pemilik]) {
            DB::table('sessions')->insert([
                'id' => $id, 'user_id' => $pemilik->id, 'ip_address' => '10.0.0.1', 'user_agent' => 'uji', 'payload' => '', 'last_activity' => time(),
            ]);
        }

        app(ManajemenAkun::class)->blokir($target, $this->petugas, 'Alasan pengujian yang cukup panjang.');

        $this->assertDatabaseMissing('sessions', ['id' => 'sesi-target-1']);
        $this->assertDatabaseMissing('sessions', ['id' => 'sesi-target-2']);
        $this->assertDatabaseHas('sessions', ['id' => 'sesi-lain']);
    }

    #[Test]
    public function akun_yang_diblokir_dikeluarkan_pada_permintaan_berikutnya(): void
    {
        $user = $this->peminjam();
        $this->actingAs($user)->get(route('user.dashboard'))->assertOk();

        $this->blokirLangsung($user, 'Alasan blokir untuk pengujian middleware.');
        $this->actingAs(User::find($user->id));

        $this->get(route('user.dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('login');

        $this->assertStringContainsString('diblokir', session('errors')->first('login'));
        $this->assertStringContainsString('Alasan blokir untuk pengujian middleware.', session('errors')->first('login'));
        $this->assertGuest();
    }

    #[Test]
    public function login_akun_diblokir_menyebut_alasan_hanya_bila_password_benar(): void
    {
        $user = $this->peminjam(['username' => 'si_blokir', 'password' => 'RahasiaKu123']);
        $this->blokirLangsung($user, 'Alasan rahasia yang hanya untuk pemilik akun.');

        // password salah → pesan umum, status akun tidak bocor
        $this->post('/login', ['login' => 'si_blokir', 'password' => 'salah-total'])->assertSessionHasErrors('login');
        $this->assertSame('Username/email atau password salah.', session('errors')->first('login'));
        $this->assertGuest();

        // password benar → diberi tahu diblokir beserta alasan dan kontak petugas
        $this->post('/login', ['login' => 'si_blokir', 'password' => 'RahasiaKu123'])->assertSessionHasErrors('login');
        $pesan = session('errors')->first('login');
        $this->assertStringContainsString('diblokir', $pesan);
        $this->assertStringContainsString('Alasan rahasia yang hanya untuk pemilik akun.', $pesan);
        $this->assertStringContainsString('0858-7770-8909', $pesan);
        $this->assertGuest();
    }

    #[Test]
    public function buka_blokir_memulihkan_akses_dan_menyimpan_jejak_blokir_terakhir(): void
    {
        $user = $this->peminjam(['username' => 'si_blokir', 'password' => 'RahasiaKu123']);
        $this->blokirLangsung($user);

        $this->actingAs($this->petugas)->post(route('admin.pengguna.buka-blokir', $user))->assertSessionHas('sukses');

        $user->refresh();
        $this->assertFalse($user->isDiblokir());
        $this->assertSame('Tercatat menipu tiga kali dalam peminjaman.', $user->alasan_blokir); // jejak dipertahankan
        $this->assertDatabaseHas('notifikasis', ['user_id' => $user->id, 'judul' => 'Blokir Akun Dicabut']);

        $this->keluar();
        $this->post('/login', ['login' => 'si_blokir', 'password' => 'RahasiaKu123'])->assertRedirect(route('user.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    #[Test]
    public function membuka_blokir_akun_yang_tidak_diblokir_ditolak(): void
    {
        $user = $this->peminjam();

        $this->actingAs($this->petugas)->post(route('admin.pengguna.buka-blokir', $user))->assertSessionHas('gagal');
    }

    // ---------------------------------------------------------------- hapus & pulihkan

    #[Test]
    public function hapus_akun_menolak_pengajuan_menunggu_dan_menyimpan_riwayat(): void
    {
        $user = $this->peminjam(['username' => 'akan_dihapus', 'password' => 'RahasiaKu123', 'email' => 'hapus@example.org', 'no_hp' => '081244445555']);
        $menunggu = $this->pengajuan($user, 'pending');
        $selesai = $this->pengajuan($user, 'returned');

        $this->actingAs($this->petugas)->delete(route('admin.pengguna.destroy', $user))->assertSessionHas('sukses');

        $this->assertTrue(User::withTrashed()->find($user->id)->trashed());
        $this->assertSame('rejected', $menunggu->fresh()->status_pengajuan);
        $this->assertSame('returned', $selesai->fresh()->status_pengajuan);
        // riwayat tetap tampil lengkap dengan nama pemiliknya di sisi petugas
        $this->get(route('admin.peminjaman.show', $selesai))->assertOk()->assertSee($user->nama)->assertSee('Dihapus');

        // tidak bisa masuk lagi
        $this->keluar();
        $this->post('/login', ['login' => 'akan_dihapus', 'password' => 'RahasiaKu123'])->assertSessionHasErrors('login');
        $this->assertGuest();

        // identitasnya tetap terpesan
        $this->daftar(['email' => 'hapus@example.org'])->assertSessionHasErrors('email');
        $this->daftar(['no_hp' => '0812-4444-5555'])->assertSessionHasErrors('no_hp');
    }

    #[Test]
    public function akun_dengan_peminjaman_aktif_belum_bisa_dihapus(): void
    {
        $user = $this->peminjam();
        $dipinjam = $this->pengajuan($user, 'approved');

        $this->actingAs($this->petugas)->delete(route('admin.pengguna.destroy', $user))->assertSessionHas('gagal');
        $this->assertFalse($user->fresh()->trashed());
        $this->assertStringContainsString('peminjaman aktif', session('gagal'));

        // halaman detail pun tidak menawarkan tombol hapus yang aktif, dan alasannya tertulis jelas (bukan hanya tooltip)
        $this->get(route('admin.pengguna.show', $user))->assertOk()
            ->assertSee('Masih ada 1 peminjaman aktif')
            ->assertSee('belum bisa dihapus karena')
            ->assertSee('masih ada 1 peminjaman aktif');

        // setelah barang dikembalikan, baru boleh dihapus
        $dipinjam->update(['status_pengajuan' => 'returned']);
        $this->delete(route('admin.pengguna.destroy', $user))->assertSessionHas('sukses');
        $this->assertTrue(User::withTrashed()->find($user->id)->trashed());
    }

    #[Test]
    public function pulihkan_mengembalikan_akun_yang_dihapus(): void
    {
        $user = $this->peminjam(['username' => 'pulih_aku', 'password' => 'RahasiaKu123']);
        $user->delete();

        $this->actingAs($this->petugas)->post(route('admin.pengguna.pulihkan', $user->id))->assertSessionHas('sukses');
        $this->assertFalse($user->fresh()->trashed());

        $this->keluar();
        $this->post('/login', ['login' => 'pulih_aku', 'password' => 'RahasiaKu123'])->assertRedirect(route('user.dashboard'));
    }

    #[Test]
    public function akun_yang_dihapus_tidak_bisa_diblokir_dan_yang_belum_dihapus_tidak_bisa_dipulihkan(): void
    {
        $dihapus = $this->peminjam();
        $dihapus->delete();
        $aktif = $this->peminjam();

        $this->actingAs($this->petugas)
            ->post(route('admin.pengguna.blokir', $dihapus->id), ['alasan' => 'Alasan blokir yang cukup panjang.'])->assertSessionHas('gagal');
        $this->post(route('admin.pengguna.pulihkan', $aktif))->assertSessionHas('gagal');
    }

    #[Test]
    public function petugas_dan_diri_sendiri_tidak_bisa_diblokir_atau_dihapus(): void
    {
        $lain = $this->admin(['nama' => 'Petugas Lain']);

        $this->actingAs($this->petugas);
        foreach ([$lain, $this->petugas] as $sasaran) {
            $this->post(route('admin.pengguna.blokir', $sasaran), ['alasan' => 'Alasan blokir yang cukup panjang.'])->assertSessionHas('gagal');
            $this->delete(route('admin.pengguna.destroy', $sasaran))->assertSessionHas('gagal');
            $this->post(route('admin.pengguna.pelanggaran.store', $sasaran), ['jenis' => 'penipuan', 'keterangan' => 'Percobaan mencatat'])->assertSessionHas('gagal');
        }

        $this->assertFalse($lain->fresh()->isDiblokir() || $lain->fresh()->trashed());
        $this->assertFalse($this->petugas->fresh()->isDiblokir() || $this->petugas->fresh()->trashed());
        $this->assertSame(0, Pelanggaran::count());
    }

    // ---------------------------------------------------------------- pelanggaran

    #[Test]
    public function mencatat_pelanggaran_memberi_tahu_peminjam(): void
    {
        $user = $this->peminjam();
        $pengajuan = $this->pengajuan($user, 'returned');

        $this->actingAs($this->petugas)->post(route('admin.pengguna.pelanggaran.store', $user), [
            'jenis' => 'kerusakan', 'keterangan' => 'Tali komando putus karena kelalaian.', 'pengajuan_id' => $pengajuan->id,
        ])->assertSessionHas('sukses')->assertSessionHasNoErrors();

        $this->assertDatabaseHas('pelanggaran', [
            'user_id' => $user->id, 'jenis' => 'kerusakan', 'pengajuan_id' => $pengajuan->id, 'dicatat_oleh' => $this->petugas->id,
        ]);
        $this->assertDatabaseHas('notifikasis', ['user_id' => $user->id, 'judul' => 'Catatan Pelanggaran', 'tipe' => 'danger']);
        $this->get(route('admin.pengguna.show', $user))->assertOk()->assertSee('Tali komando putus karena kelalaian.');
    }

    #[Test]
    public function validasi_pencatatan_pelanggaran(): void
    {
        $user = $this->peminjam();
        $milikOrangLain = $this->pengajuan($this->peminjam(), 'returned');

        $this->actingAs($this->petugas);
        $this->post(route('admin.pengguna.pelanggaran.store', $user), ['jenis' => 'ngawur', 'keterangan' => 'Keterangan cukup'])->assertSessionHasErrors('jenis');
        $this->post(route('admin.pengguna.pelanggaran.store', $user), ['jenis' => 'penipuan', 'keterangan' => 'abc'])->assertSessionHasErrors('keterangan');
        $this->post(route('admin.pengguna.pelanggaran.store', $user), ['jenis' => 'penipuan', 'keterangan' => 'Keterangan cukup', 'pengajuan_id' => $milikOrangLain->id])
            ->assertSessionHasErrors('pengajuan_id');

        $this->assertSame(0, Pelanggaran::count());
    }

    #[Test]
    public function tiga_penipuan_menandai_layak_ditinjau_dan_memberi_tahu_semua_petugas_tepat_sekali(): void
    {
        $kedua = $this->admin(['nama' => 'Petugas Kedua']);
        $user = $this->peminjam();
        $akun = app(ManajemenAkun::class);
        $catat = fn (string $jenis) => $akun->catatPelanggaran($user, $this->petugas, ['jenis' => $jenis, 'keterangan' => 'Catatan pengujian']);
        $layak = fn () => Notifikasi::where('judul', 'Akun Layak Diblokir')->count();

        $catat('penipuan');
        $catat('kehilangan');   // bukan penipuan: tidak dihitung
        $catat('penipuan');
        $this->assertSame(0, $layak());
        $this->assertFalse($user->fresh()->perluDitinjau());
        $this->assertSame(0, User::jumlahLayakDitinjau());

        $catat('penipuan');     // yang ketiga
        $this->assertSame(2, $layak(), 'kedua petugas harus diberi tahu');
        $this->assertDatabaseHas('notifikasis', ['user_id' => $this->petugas->id, 'judul' => 'Akun Layak Diblokir']);
        $this->assertDatabaseHas('notifikasis', ['user_id' => $kedua->id, 'judul' => 'Akun Layak Diblokir']);
        $this->assertTrue($user->fresh()->perluDitinjau());
        $this->assertSame(1, User::jumlahLayakDitinjau());

        $catat('penipuan');     // yang keempat tidak membunyikan alarm lagi
        $this->assertSame(2, $layak());

        // sidebar admin menampilkan lencana jumlah akun yang perlu ditinjau
        $this->actingAs($this->petugas)->get(route('admin.dashboard'))->assertOk()->assertSee('1 akun layak ditinjau untuk diblokir');

        // setelah diblokir, tidak lagi dihitung sebagai "layak ditinjau"
        $akun->blokir($user, $this->petugas, 'Empat kali penipuan peminjaman tercatat.');
        $this->assertSame(0, User::jumlahLayakDitinjau());
    }

    #[Test]
    public function akun_yang_sudah_diblokir_tidak_memicu_notifikasi_layak_diblokir(): void
    {
        $user = $this->peminjam();
        $this->blokirLangsung($user);

        foreach (range(1, 3) as $_) {
            app(ManajemenAkun::class)->catatPelanggaran($user, $this->petugas, ['jenis' => 'penipuan', 'keterangan' => 'Catatan pengujian']);
        }

        $this->assertSame(0, Notifikasi::where('judul', 'Akun Layak Diblokir')->count());
        $this->assertSame(3, Pelanggaran::where('user_id', $user->id)->count());
    }

    #[Test]
    public function batas_penipuan_bisa_diubah_lewat_konfigurasi(): void
    {
        config(['perkap.batas_penipuan' => 2]);
        $user = $this->peminjam();
        $this->catatPenipuan($user, 2);

        $this->assertTrue($user->fresh()->perluDitinjau());
        $this->assertSame(1, User::jumlahLayakDitinjau());
    }

    #[Test]
    public function catatan_pelanggaran_bisa_dihapus_petugas(): void
    {
        $user = $this->peminjam();
        $this->catatPenipuan($user, 1);
        $catatan = Pelanggaran::firstOrFail();

        $this->actingAs($this->peminjam())->delete(route('admin.pelanggaran.destroy', $catatan))->assertForbidden();
        $this->assertSame(1, Pelanggaran::count());

        $this->actingAs($this->petugas)->delete(route('admin.pelanggaran.destroy', $catatan))->assertSessionHas('sukses');
        $this->assertSame(0, Pelanggaran::count());
    }

    // ---------------------------------------------------------------- pengajuan & akun yang tidak aktif

    #[Test]
    public function pengajuan_milik_akun_diblokir_tidak_bisa_disetujui(): void
    {
        $user = $this->peminjam();
        $pengajuan = $this->pengajuan($user, 'pending', 2);
        $stok = Alat::firstOrFail()->stok_tersedia;
        $this->blokirLangsung($user); // simulasi data lama: pengajuan masih "menunggu" padahal pemiliknya sudah diblokir

        $this->actingAs($this->petugas)->patch(route('admin.peminjaman.approve', $pengajuan))->assertSessionHas('gagal');

        $this->assertSame('pending', $pengajuan->fresh()->status_pengajuan);
        $this->assertSame($stok, Alat::firstOrFail()->stok_tersedia);
        $this->get(route('admin.peminjaman.show', $pengajuan))->assertOk()->assertSee('tidak bisa disetujui');
    }

    #[Test]
    public function halaman_detail_peminjaman_menautkan_ke_profil_dan_mengajak_mencatat_pelanggaran(): void
    {
        $user = $this->peminjam();
        $rusak = $this->pengajuan($user, 'returned');
        $rusak->update(['status_akhir' => 'damaged']);

        $this->actingAs($this->petugas)->get(route('admin.peminjaman.show', $rusak))
            ->assertOk()
            ->assertSee($user->kode_anggota)
            ->assertSee(route('admin.pengguna.show', $user), false)
            ->assertSee('Catat pelanggaran');

        // tautan "Catat pelanggaran" memilihkan pengajuan & jenisnya di formulir
        $this->get(route('admin.pengguna.show', ['pengguna' => $user, 'pengajuan' => $rusak->id, 'jenis' => 'kerusakan']))
            ->assertOk()
            ->assertSee('value="'.$rusak->id.'" selected', false);
    }

    #[Test]
    public function dashboard_peminjam_memperingatkan_catatan_penipuan(): void
    {
        $user = $this->peminjam();
        $this->actingAs($user)->get(route('user.dashboard'))->assertOk()->assertDontSee('tercatat melakukan penipuan');

        $this->catatPenipuan($user, 2);
        $this->get(route('user.dashboard'))->assertOk()->assertSee('2 dari 3 kali')->assertSee($user->kode_anggota);
    }

    #[Test]
    public function domain_exception_dipakai_untuk_semua_pelanggaran_aturan(): void
    {
        $this->expectException(DomainException::class);

        app(ManajemenAkun::class)->blokir($this->petugas, $this->petugas, 'Alasan blokir yang cukup panjang.');
    }
}
