<?php

namespace Tests\Unit;

use App\Support\KodeAnggota;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class KodeAnggotaTest extends TestCase
{
    #[Test]
    public function id_yang_dibuat_berbentuk_benar_dan_lolos_pemeriksaan(): void
    {
        for ($i = 0; $i < 200; $i++) {
            $kode = KodeAnggota::buat();

            $this->assertMatchesRegularExpression('/^PRK-\d{2}-[0-9A-HJKMNP-TV-Z]{6}-[0-9A-HJKMNP-TV-Z]$/', $kode);
            $this->assertTrue(KodeAnggota::valid($kode), "ID {$kode} seharusnya valid");
        }
    }

    #[Test]
    public function setiap_salah_ketik_satu_karakter_selalu_terdeteksi(): void
    {
        // Luhn mod 32 menjamin semua kesalahan satu karakter terdeteksi. Diuji menyeluruh:
        // tiap posisi badan ID (tahun, acak, pemeriksa) diganti tiap karakter alfabet lainnya.
        $lolos = [];

        for ($i = 0; $i < 25; $i++) {
            $kode = KodeAnggota::buat();
            $bagian = explode('-', $kode);
            $badan = $bagian[1].$bagian[2].$bagian[3]; // 2 + 6 + 1 karakter

            for ($posisi = 0; $posisi < strlen($badan); $posisi++) {
                foreach (str_split(KodeAnggota::ALFABET) as $ganti) {
                    if ($ganti === $badan[$posisi]) {
                        continue;
                    }
                    $salah = $badan;
                    $salah[$posisi] = $ganti;
                    $ketik = $bagian[0].'-'.substr($salah, 0, 2).'-'.substr($salah, 2, 6).'-'.substr($salah, 8, 1);

                    if (KodeAnggota::valid($ketik)) {
                        $lolos[] = "{$kode} -> {$ketik}";
                    }
                }
            }
        }

        $this->assertSame([], $lolos, 'Ada salah ketik satu karakter yang tidak terdeteksi');
    }

    #[Test]
    public function penulisan_longgar_tetap_dikenali_dan_dibakukan(): void
    {
        $kode = KodeAnggota::buat();
        $polos = str_replace('-', '', $kode);

        $this->assertSame($kode, KodeAnggota::baku(strtolower($kode)));
        $this->assertSame($kode, KodeAnggota::baku($polos));
        $this->assertSame($kode, KodeAnggota::baku('  '.strtolower($polos).'  '));
        $this->assertSame($kode, KodeAnggota::baku(implode(' ', explode('-', $kode))));
    }

    #[Test]
    public function huruf_mirip_angka_pada_badan_dikoreksi(): void
    {
        // Pada badan, "O" dibaca 0 dan "I"/"L" dibaca 1 (alfabet ID memang tidak memuat huruf itu).
        $c = KodeAnggota::pemeriksa('20100000');
        $kanonik = "PRK-20-100000-{$c}";

        $this->assertSame($kanonik, KodeAnggota::baku($kanonik));
        $this->assertSame($kanonik, KodeAnggota::baku("PRK-2O-IOOOOO-{$c}"));
        $this->assertSame($kanonik, KodeAnggota::baku("prk-2o-looooo-{$c}"));
    }

    #[Test]
    public function bentuk_yang_bukan_id_ditolak(): void
    {
        foreach (['', '   ', 'abc', 'PRK-26-K7M2QH', 'PRK-26-K7M2QH-99', '12-26-K7M2QH-9', 'PRK-XX-K7M2QH-9', 'PRK-26-K7M2UH-9'] as $masukan) {
            $this->assertNull(KodeAnggota::baku($masukan), "'{$masukan}' seharusnya ditolak");
        }
        $this->assertNull(KodeAnggota::urai(null));
    }

    #[Test]
    public function tahun_mengikuti_waktu_pembuatan(): void
    {
        $this->assertStringContainsString('-31-', KodeAnggota::buat(Carbon::create(2031, 5, 1)));
        $this->assertStringContainsString('-'.now()->format('y').'-', KodeAnggota::buat());
    }

    #[Test]
    public function awalan_mengikuti_konfigurasi_dengan_cadangan_aman(): void
    {
        config(['perkap.prefix_id_anggota' => 'smkn']);
        $this->assertStringStartsWith('SMKN-', KodeAnggota::buat());

        config(['perkap.prefix_id_anggota' => 'ab-12']);
        $this->assertStringStartsWith('AB-', KodeAnggota::buat());

        config(['perkap.prefix_id_anggota' => '1']);
        $this->assertStringStartsWith('PRK-', KodeAnggota::buat());

        // ID dengan awalan berbeda tetap bisa diuraikan (ID lama tetap berlaku bila awalan diganti)
        config(['perkap.prefix_id_anggota' => 'PRK']);
        $lama = 'SMKN-'.substr(KodeAnggota::buat(), 4);
        $this->assertTrue(KodeAnggota::valid($lama));
    }
}
