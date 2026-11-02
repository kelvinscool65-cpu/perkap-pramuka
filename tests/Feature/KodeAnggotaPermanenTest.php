<?php

namespace Tests\Feature;

use App\Exceptions\IdentitasTidakBolehDiubah;
use App\Models\User;
use App\Support\KodeAnggota;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class KodeAnggotaPermanenTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function setiap_akun_baru_mendapat_id_unik_yang_valid(): void
    {
        $kode = collect(range(1, 40))->map(fn () => $this->peminjam()->kode_anggota);

        $this->assertCount(40, $kode->unique());
        $kode->each(fn ($k) => $this->assertTrue(KodeAnggota::valid($k), "{$k} tidak valid"));
    }

    #[Test]
    public function admin_juga_mendapat_id(): void
    {
        $this->assertTrue(KodeAnggota::valid($this->admin()->kode_anggota));
    }

    #[Test]
    public function id_tidak_bisa_diisi_lewat_mass_assignment(): void
    {
        $user = User::create([
            'nama' => 'Peretas', 'email' => 'peretas@example.org', 'username' => 'peretas',
            'password' => 'rahasia123', 'no_hp' => '081200000001', 'role' => 'peminjam',
            'kode_anggota' => 'HACK', 'status_akun' => 'aktif',
        ]);

        $this->assertNotSame('HACK', $user->kode_anggota);
        $this->assertTrue(KodeAnggota::valid($user->kode_anggota));
    }

    #[Test]
    public function model_menolak_perubahan_id(): void
    {
        $user = $this->peminjam();
        $asli = $user->kode_anggota;

        $user->kode_anggota = 'PRK-26-ZZZZZZ-0';
        try {
            $user->save();
            $this->fail('Perubahan ID Anggota seharusnya ditolak');
        } catch (IdentitasTidakBolehDiubah) {
            $this->addToAssertionCount(1);
        }

        $this->assertSame($asli, DB::table('users')->where('id', $user->id)->value('kode_anggota'));
    }

    #[Test]
    public function update_biasa_dan_penyimpanan_ulang_id_yang_sama_tetap_boleh(): void
    {
        $user = $this->peminjam();
        $asli = $user->kode_anggota;

        $user->update(['nama' => 'Nama Baru']);
        $user->forceFill(['kode_anggota' => $asli])->save();

        $this->assertSame('Nama Baru', $user->fresh()->nama);
        $this->assertSame($asli, $user->fresh()->kode_anggota);
    }

    #[Test]
    public function database_menolak_perubahan_id_walau_lewat_sql_langsung(): void
    {
        $user = $this->peminjam();

        try {
            DB::table('users')->where('id', $user->id)->update(['kode_anggota' => 'PRK-26-ABCDEF-1']);
            $this->fail('Pengaman database seharusnya menolak perubahan ID');
        } catch (QueryException $e) {
            $this->assertStringContainsString('permanen', $e->getMessage());
        }

        $this->assertSame($user->kode_anggota, $user->fresh()->kode_anggota);

        // menghapus nilai (set NULL) juga ditolak
        $this->expectException(QueryException::class);
        DB::table('users')->where('id', $user->id)->update(['kode_anggota' => null]);
    }

    #[Test]
    public function id_tidak_pernah_dipakai_dua_akun(): void
    {
        $a = $this->peminjam();

        $this->expectException(UniqueConstraintViolationException::class);
        User::factory()->make()->forceFill(['kode_anggota' => $a->kode_anggota])->save();
    }

    #[Test]
    public function pembuatan_id_diulang_bila_bentrok_termasuk_dengan_akun_terhapus(): void
    {
        $aktif = $this->peminjam();
        $terhapus = $this->peminjam();
        $terhapus->delete();

        $urutan = [$aktif->kode_anggota, $terhapus->kode_anggota, $aktif->kode_anggota, 'PRK-26-BARUUU-0'];
        $kode = User::kodeAnggotaBaru(function () use (&$urutan) {
            return array_shift($urutan);
        });

        $this->assertSame('PRK-26-BARUUU-0', $kode);
    }

    #[Test]
    public function pembuatan_id_menyerah_dengan_pesan_bila_terus_bentrok(): void
    {
        $user = $this->peminjam();

        $this->expectException(RuntimeException::class);
        User::kodeAnggotaBaru(fn () => $user->kode_anggota);
    }

    #[Test]
    public function no_hp_dan_email_baku_dijaga_indeks_unik_database(): void
    {
        $a = $this->peminjam(['no_hp' => '081234567890', 'email' => 'budi.santoso@gmail.com']);

        // trik penulisan lain pada tingkat model/seeder pun tidak lolos
        try {
            $this->peminjam(['no_hp' => '+62 812-3456-7890']);
            $this->fail('Nomor kembar seharusnya ditolak database');
        } catch (UniqueConstraintViolationException) {
            $this->addToAssertionCount(1);
        }

        try {
            $this->peminjam(['email' => 'BudiSantoso+tugas@gmail.com']);
            $this->fail('Email kembar seharusnya ditolak database');
        } catch (UniqueConstraintViolationException) {
            $this->addToAssertionCount(1);
        }

        $this->assertSame('6281234567890', $a->no_hp_normal);
        $this->assertSame('budisantoso@gmail.com', $a->email_kanonik);
    }

    #[Test]
    public function nilai_baku_ikut_diperbarui_saat_email_atau_nomor_diganti(): void
    {
        $user = $this->peminjam(['no_hp' => '081234567890', 'email' => 'lama@example.org']);

        $user->update(['no_hp' => '0813 1111 2222', 'email' => 'Baru.Sekali@Gmail.com']);

        $this->assertSame('6281311112222', $user->fresh()->no_hp_normal);
        $this->assertSame('barusekali@gmail.com', $user->fresh()->email_kanonik);
    }

    #[Test]
    public function formulir_profil_mengabaikan_id_anggota_yang_dikirim(): void
    {
        $user = $this->peminjam(['username' => 'uji_profil', 'email' => 'uji.profil@example.org', 'no_hp' => '081355550001']);
        $asli = $user->kode_anggota;

        $this->actingAs($user)->put(route('user.profile.update'), [
            'nama' => 'Nama Diubah', 'username' => 'uji_profil', 'email' => 'uji.profil@example.org', 'no_hp' => '081355550001',
            'kode_anggota' => 'PRK-26-ZZZZZZ-0', 'role' => 'petugas_perkap', 'status_akun' => 'diblokir',
        ])->assertSessionHasNoErrors()->assertSessionHas('sukses');

        $segar = $user->fresh();
        $this->assertSame('Nama Diubah', $segar->nama);
        $this->assertSame($asli, $segar->kode_anggota);
        $this->assertSame('peminjam', $segar->role);
        $this->assertSame('aktif', $segar->status_akun);
    }

    #[Test]
    public function profil_tidak_bisa_memakai_email_atau_nomor_milik_akun_lain(): void
    {
        $this->peminjam(['email' => 'budi.santoso@gmail.com', 'no_hp' => '081234567890']);
        $user = $this->peminjam(['username' => 'uji_profil', 'email' => 'uji.profil@example.org', 'no_hp' => '081355550001']);

        $this->actingAs($user)->put(route('user.profile.update'), [
            'nama' => 'Nama Diubah', 'username' => 'uji_profil',
            'email' => 'budisantoso+x@gmail.com', 'no_hp' => '+62 812-3456-7890',
        ])->assertSessionHasErrors(['email', 'no_hp']);

        $this->assertSame('uji.profil@example.org', $user->fresh()->email);
    }

    #[Test]
    public function profil_boleh_menyimpan_email_dan_nomor_miliknya_sendiri_dalam_penulisan_lain(): void
    {
        $user = $this->peminjam(['username' => 'uji_profil', 'email' => 'uji.profil@gmail.com', 'no_hp' => '081355550001']);

        $this->actingAs($user)->put(route('user.profile.update'), [
            'nama' => 'Nama Diubah', 'username' => 'uji_profil',
            'email' => 'ujiprofil@gmail.com', 'no_hp' => '+62 813-5555-0001',
        ])->assertSessionHasNoErrors();

        $this->assertSame('ujiprofil@gmail.com', $user->fresh()->email);
    }
}
