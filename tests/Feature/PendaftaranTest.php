<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\BatasPendaftaran;
use App\Support\Identitas;
use App\Support\KodeAnggota;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PendaftaranTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------------- formulir & alur sukses

    #[Test]
    public function formulir_menampilkan_persetujuan_dan_penjelasan_id(): void
    {
        $this->get('/register')
            ->assertOk()
            ->assertSee('name="setuju"', false)
            ->assertSee('ID Anggota unik')
            ->assertSee('maksimal 3 akun dalam 24 jam');
    }

    #[Test]
    public function pendaftaran_berhasil_memberi_id_anggota_dan_langsung_ke_kartu(): void
    {
        $data = $this->dataDaftar(['nama' => '  budi    santoso ']);

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
            ->post('/register', $data)
            ->assertRedirect(route('user.kartu'))
            ->assertSessionHas('baru_daftar', true);

        $user = User::where('email', $data['email'])->firstOrFail();

        $this->assertTrue(KodeAnggota::valid($user->kode_anggota));
        $this->assertSame('budi santoso', $user->nama);
        $this->assertSame('peminjam', $user->role);
        $this->assertSame('aktif', $user->status_akun);
        $this->assertSame('10.0.0.1', $user->ip_daftar);
        $this->assertNotNull($user->syarat_disetujui_pada);
        $this->assertSame(Identitas::normalisasiNoHp($data['no_hp']), $user->no_hp_normal);
        $this->assertSame(Identitas::kanonikEmail($data['email']), $user->email_kanonik);
        $this->assertAuthenticatedAs($user);

        // kartu langsung bisa dibuka dan menampilkan ID tersebut
        $this->get(route('user.kartu'))->assertOk()->assertSee($user->kode_anggota);
    }

    #[Test]
    public function peran_status_id_dan_ip_tidak_bisa_diatur_dari_formulir(): void
    {
        $data = $this->dataDaftar([
            'role' => 'petugas_perkap', 'status_akun' => 'diblokir', 'kode_anggota' => 'PRK-26-AAAAAA-0',
            'ip_daftar' => '1.2.3.4', 'diblokir_oleh' => 1, 'alasan_blokir' => 'x',
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.9'])->post('/register', $data)->assertRedirect(route('user.kartu'));

        $user = User::where('email', $data['email'])->firstOrFail();
        $this->assertSame('peminjam', $user->role);
        $this->assertSame('aktif', $user->status_akun);
        $this->assertNotSame('PRK-26-AAAAAA-0', $user->kode_anggota);
        $this->assertSame('10.0.0.9', $user->ip_daftar);
        $this->assertNull($user->diblokir_oleh);
        $this->assertNull($user->alasan_blokir);
    }

    // ---------------------------------------------------------------- validasi isian

    public static function isianTidakValid(): array
    {
        return [
            'tanpa persetujuan' => [['setuju' => null], 'setuju'],
            'persetujuan 0' => [['setuju' => '0'], 'setuju'],
            'kolom jebakan bot terisi' => [['website' => 'http://spam.test'], 'website'],
            'nama berisi angka' => [['nama' => 'Budi123'], 'nama'],
            'nama terlalu pendek' => [['nama' => 'Bu'], 'nama'],
            'username terlalu pendek' => [['username' => 'abc'], 'username'],
            'username dilarang' => [['username' => 'Admin'], 'username'],
            'username berspasi' => [['username' => 'budi santoso'], 'username'],
            'email salah' => [['email' => 'bukan-email'], 'email'],
            'telepon rumah' => [['no_hp' => '0211234567'], 'no_hp'],
            'nomor asing' => [['no_hp' => '+1 202 555 0100'], 'no_hp'],
            'password terlalu pendek' => [['password' => 'abc123', 'password_confirmation' => 'abc123'], 'password'],
            'konfirmasi password beda' => [['password_confirmation' => 'LainLagi123'], 'password'],
        ];
    }

    #[Test]
    #[DataProvider('isianTidakValid')]
    public function isian_tidak_valid_ditolak_tanpa_membuat_akun(array $timpa, string $kolom): void
    {
        $this->daftar($timpa)->assertSessionHasErrors($kolom);

        $this->assertSame(0, User::count());
        $this->assertGuest();
    }

    // ---------------------------------------------------------------- keunikan identitas

    public static function emailKembar(): array
    {
        return [
            'sama persis' => ['budi.santoso@gmail.com'],
            'huruf besar' => ['BUDI.SANTOSO@gmail.com'],
            'tanpa titik' => ['budisantoso@gmail.com'],
            'titik di tempat lain' => ['b.udi.santoso@gmail.com'],
            'label plus' => ['budisantoso+tugas@gmail.com'],
            'googlemail' => ['budi.santoso@googlemail.com'],
        ];
    }

    #[Test]
    #[DataProvider('emailKembar')]
    public function email_kembar_dalam_bentuk_apa_pun_ditolak(string $email): void
    {
        $this->peminjam(['email' => 'budi.santoso@gmail.com']);

        $this->daftar(['email' => $email])->assertSessionHasErrors('email');

        $this->assertStringContainsString('sudah terdaftar', session('errors')->first('email'));
        $this->assertSame(1, User::count());
    }

    public static function nomorKembar(): array
    {
        return [
            'sama persis' => ['081234567890'],
            'bertanda hubung' => ['0812-3456-7890'],
            'plus 62' => ['+62 812 3456 7890'],
            '62 tanpa plus' => ['6281234567890'],
            '0062' => ['0062 812 3456 7890'],
            'tanpa nol depan' => ['812 3456 7890'],
        ];
    }

    #[Test]
    #[DataProvider('nomorKembar')]
    public function nomor_kembar_dalam_format_apa_pun_ditolak(string $nomor): void
    {
        $this->peminjam(['no_hp' => '081234567890']);

        $this->daftar(['no_hp' => $nomor])->assertSessionHasErrors('no_hp');

        $this->assertStringContainsString('sudah terdaftar', session('errors')->first('no_hp'));
        $this->assertSame(1, User::count());
    }

    #[Test]
    public function username_tidak_peka_huruf_besar_kecil(): void
    {
        $this->peminjam(['username' => 'siti_n']);

        $this->daftar(['username' => 'SITI_N'])->assertSessionHasErrors('username');
        $this->assertSame(1, User::count());
    }

    #[Test]
    public function identitas_akun_yang_dihapus_tetap_terpesan(): void
    {
        $lama = $this->peminjam(['email' => 'lama@example.org', 'no_hp' => '081299998888', 'username' => 'akun_lama']);
        $lama->delete();

        $this->daftar(['email' => 'LAMA@example.org'])->assertSessionHasErrors('email');
        $this->daftar(['no_hp' => '+62 812 9999 8888'])->assertSessionHasErrors('no_hp');
        $this->daftar(['username' => 'akun_lama'])->assertSessionHasErrors('username');

        $this->assertSame(0, User::count());
    }

    // ---------------------------------------------------------------- batas pendaftaran per IP

    #[Test]
    public function maksimal_tiga_akun_per_ip_dalam_24_jam(): void
    {
        foreach (range(1, 3) as $_) {
            $this->daftar([], '10.1.1.1')->assertRedirect(route('user.kartu'));
        }
        $this->assertSame(3, User::where('ip_daftar', '10.1.1.1')->count());

        $this->daftar(['username' => 'ke_empat'], '10.1.1.1')->assertSessionHasErrors('batas_ip');
        $this->assertDatabaseMissing('users', ['username' => 'ke_empat']);

        // pesan jelas: batas, jendela, kapan boleh lagi, dan kontak petugas
        $pesan = session('errors')->first('batas_ip');
        $this->assertStringContainsString('maksimal 3 akun per 24 jam', $pesan);
        $this->assertStringContainsString('Silakan coba lagi pada', $pesan);
        $this->assertStringContainsString('0858-7770-8909', $pesan);

        // IP lain tidak terpengaruh
        $this->daftar([], '10.1.1.2')->assertRedirect(route('user.kartu'));
    }

    #[Test]
    public function pendaftaran_gagal_validasi_tidak_menghabiskan_kuota(): void
    {
        foreach (range(1, 5) as $_) {
            $this->daftar(['nama' => 'Bu'], '10.1.2.1')->assertSessionHasErrors('nama');
        }

        foreach (range(1, 3) as $_) {
            $this->daftar([], '10.1.2.1')->assertRedirect(route('user.kartu'));
        }
        $this->assertSame(3, User::count());
    }

    #[Test]
    public function jendela_waktu_bergulir_membebaskan_slot_satu_per_satu(): void
    {
        $this->daftar([], '10.4.4.4')->assertRedirect(route('user.kartu')); // T0

        $this->travel(12)->hours();
        $this->daftar([], '10.4.4.4')->assertRedirect(route('user.kartu')); // T0+12j
        $this->daftar([], '10.4.4.4')->assertRedirect(route('user.kartu'));
        $this->daftar([], '10.4.4.4')->assertSessionHasErrors('batas_ip');  // sudah 3 dalam 24 jam

        $this->travel(13)->hours();                                         // T0+25j: pendaftaran pertama keluar jendela
        $this->daftar([], '10.4.4.4')->assertRedirect(route('user.kartu')); // tepat satu slot
        $this->daftar([], '10.4.4.4')->assertSessionHasErrors('batas_ip');  // yang ke-2,3,4 masih di dalam jendela

        $this->travel(13)->hours();                                         // semuanya sudah lewat
        $this->daftar([], '10.4.4.4')->assertRedirect(route('user.kartu'));
    }

    #[Test]
    public function akun_yang_dihapus_tetap_menghabiskan_kuota(): void
    {
        foreach (range(1, 3) as $_) {
            $this->daftar([], '10.3.3.3');
        }
        User::where('ip_daftar', '10.3.3.3')->firstOrFail()->delete();

        $this->daftar([], '10.3.3.3')->assertSessionHasErrors('batas_ip');
    }

    #[Test]
    public function ip_yang_dikecualikan_bebas_dari_batas(): void
    {
        config(['perkap.ip_dikecualikan' => ['10.9.9.0/24']]);

        foreach (range(1, 5) as $_) {
            $this->daftar([], '10.9.9.7')->assertRedirect(route('user.kartu'));
        }
        $this->assertSame(5, User::where('ip_daftar', '10.9.9.7')->count());

        // alamat di luar blok tetap dibatasi
        foreach (range(1, 3) as $_) {
            $this->daftar([], '10.9.8.7');
        }
        $this->daftar([], '10.9.8.7')->assertSessionHasErrors('batas_ip');
    }

    #[Test]
    public function batas_dan_jendela_bisa_diubah_lewat_konfigurasi(): void
    {
        config(['perkap.batas_daftar_per_ip' => 1, 'perkap.jendela_daftar_jam' => 1]);

        $this->daftar([], '10.6.6.6')->assertRedirect(route('user.kartu'));
        $this->daftar([], '10.6.6.6')->assertSessionHasErrors('batas_ip');

        $this->travel(61)->minutes();
        $this->daftar([], '10.6.6.6')->assertRedirect(route('user.kartu'));
    }

    #[Test]
    public function satu_blok_ipv6_dihitung_sebagai_satu_sumber(): void
    {
        $this->daftar([], '2001:db8:1:2::1')->assertRedirect(route('user.kartu'));
        $this->daftar([], '2001:db8:1:2:ffff::9')->assertRedirect(route('user.kartu'));
        $this->daftar([], '2001:db8:1:2:abcd:1234::7')->assertRedirect(route('user.kartu'));
        $this->daftar([], '2001:db8:1:2:aaaa::5')->assertSessionHasErrors('batas_ip');

        $this->assertSame('2001:db8:1:2::/64', User::first()->ip_daftar);

        // blok /64 lain adalah sumber berbeda
        $this->daftar([], '2001:db8:1:3::1')->assertRedirect(route('user.kartu'));
    }

    #[Test]
    public function ipv4_dan_bentuk_ipv6_pemetaannya_dihitung_sama(): void
    {
        $this->daftar([], '10.5.5.5');
        $this->daftar([], '::ffff:10.5.5.5');

        $this->assertSame(2, User::where('ip_daftar', '10.5.5.5')->count());
    }

    #[Test]
    public function header_x_forwarded_for_palsu_diabaikan_tanpa_proxy_tepercaya(): void
    {
        $data = $this->dataDaftar();

        $this->withHeaders(['X-Forwarded-For' => '9.9.9.9'])
            ->withServerVariables(['REMOTE_ADDR' => '10.8.8.8'])
            ->post('/register', $data)
            ->assertRedirect(route('user.kartu'));

        $this->assertSame('10.8.8.8', User::where('email', $data['email'])->value('ip_daftar'));
    }

    #[Test]
    public function header_x_forwarded_for_dipakai_bila_proxy_dipercaya(): void
    {
        config(['trustedproxy.proxies' => '*']);
        $data = $this->dataDaftar();

        $this->withHeaders(['X-Forwarded-For' => '9.9.9.9'])
            ->withServerVariables(['REMOTE_ADDR' => '10.8.8.8'])
            ->post('/register', $data)
            ->assertRedirect(route('user.kartu'));

        $this->assertSame('9.9.9.9', User::where('email', $data['email'])->value('ip_daftar'));
    }

    #[Test]
    public function halaman_daftar_memberi_tahu_bila_kuota_jaringan_habis(): void
    {
        foreach (range(1, 3) as $_) {
            $this->daftar([], '10.2.2.2');
        }
        $this->keluar();

        $this->withServerVariables(['REMOTE_ADDR' => '10.2.2.2'])->get('/register')
            ->assertOk()
            ->assertSee('Batas pendaftaran dari jaringan ini sudah tercapai')
            ->assertSee('Pendaftaran dibatasi sementara');

        // jaringan lain melihat formulir normal
        $this->withServerVariables(['REMOTE_ADDR' => '10.2.2.3'])->get('/register')
            ->assertOk()
            ->assertDontSee('Pendaftaran dibatasi sementara');
    }

    #[Test]
    public function terlalu_banyak_percobaan_kirim_formulir_ditahan_dengan_429(): void
    {
        config(['perkap.batas_percobaan_daftar' => 2]);

        foreach (range(1, 2) as $_) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.7.7.7'])->post('/register', [])->assertSessionHasErrors();
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.7.7.7'])->post('/register', $this->dataDaftar())->assertStatus(429);
        $this->assertSame(0, User::count());

        // halaman galat 429 berbahasa Indonesia
        $this->withServerVariables(['REMOTE_ADDR' => '10.7.7.7'])->post('/register', $this->dataDaftar())
            ->assertStatus(429)
            ->assertSee('Terlalu banyak permintaan');
    }

    #[Test]
    public function petunjuk_uji_lokal_hanya_tampil_di_mode_lokal_dengan_debug_menyala(): void
    {
        $kuota = ['batas' => 3, 'jendelaJam' => 24, 'bebasPada' => null];

        // lingkungan uji / produksi: tidak ada petunjuk pengembang
        $this->assertStringNotContainsString('PERKAP_IP_DIKECUALIKAN', BatasPendaftaran::pesan($kuota));

        // lokal tetapi debug mati: tetap tidak ada
        $this->app->detectEnvironment(fn () => 'local');
        config(['app.debug' => false]);
        $this->assertStringNotContainsString('PERKAP_IP_DIKECUALIKAN', BatasPendaftaran::pesan($kuota));

        // lokal + debug menyala: petunjuk muncul, pesan pokoknya tetap ada
        config(['app.debug' => true]);
        $pesan = BatasPendaftaran::pesan($kuota);
        $this->assertStringContainsString('PERKAP_IP_DIKECUALIKAN=127.0.0.1,::1', $pesan);
        $this->assertStringContainsString('maksimal 3 akun per 24 jam', $pesan);
    }

    #[Test]
    public function ip_loopback_yang_dikecualikan_tidak_terkena_batas_pendaftaran(): void
    {
        config(['perkap.ip_dikecualikan' => ['127.0.0.1', '::1']]);

        foreach (range(1, 5) as $_) {
            $this->daftar([], '127.0.0.1')->assertRedirect(route('user.kartu'));
            $this->keluar();
        }
        $this->daftar([], '::1')->assertRedirect(route('user.kartu'));

        $this->assertSame(5, User::where('ip_daftar', '127.0.0.1')->count());
    }
}
