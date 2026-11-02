<?php

namespace Tests\Unit;

use App\Support\Identitas;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IdentitasTest extends TestCase
{
    public static function bentukNomorYangSama(): array
    {
        return [
            'nol di depan' => ['081234567890'],
            'tanda hubung' => ['0812-3456-7890'],
            'spasi' => ['0812 3456 7890'],
            'plus 62' => ['+62 812 3456 7890'],
            '62 tanpa plus' => ['6281234567890'],
            '0062' => ['0062 812 3456 7890'],
            'tanpa nol/62' => ['812-3456-7890'],
            '+62 dengan 0 sisa' => ['+62 0812 3456 7890'],
            'tanda kurung' => ['(0812) 3456-7890'],
        ];
    }

    #[Test]
    #[DataProvider('bentukNomorYangSama')]
    public function semua_penulisan_nomor_menjadi_satu_nilai_baku(string $masukan): void
    {
        $this->assertSame('6281234567890', Identitas::normalisasiNoHp($masukan));
    }

    #[Test]
    public function nomor_kosong_atau_tanpa_angka_menjadi_null(): void
    {
        $this->assertNull(Identitas::normalisasiNoHp(null));
        $this->assertNull(Identitas::normalisasiNoHp(''));
        $this->assertNull(Identitas::normalisasiNoHp('abc'));
    }

    #[Test]
    public function hanya_nomor_seluler_indonesia_yang_valid(): void
    {
        foreach (['081234567890', '+6285877708909', '0858-7770-8909', '08123456789'] as $ok) {
            $this->assertTrue(Identitas::noHpValid($ok), "{$ok} seharusnya valid");
        }
        foreach (['0211234567', '12345', '0812', '081234', '+1 202 555 0100', '08123456789012345', 'abc', ''] as $salah) {
            $this->assertFalse(Identitas::noHpValid($salah), "{$salah} seharusnya tidak valid");
        }
    }

    #[Test]
    public function nomor_ditampilkan_ramah(): void
    {
        $this->assertSame('0858-7770-8909', Identitas::formatNoHp('085877708909'));
        $this->assertSame('123', Identitas::formatNoHp('123'));
    }

    public static function emailKembarGmail(): array
    {
        return [
            ['budi.santoso@gmail.com'],
            ['budisantoso@gmail.com'],
            ['BUDI.SANTOSO@Gmail.com'],
            ['b.u.d.i.s.a.n.t.o.s.o@gmail.com'],
            ['budisantoso+tugas@gmail.com'],
            ['budi.santoso+a+b@gmail.com'],
            ['budisantoso@googlemail.com'],
            ['  budisantoso@gmail.com  '],
        ];
    }

    #[Test]
    #[DataProvider('emailKembarGmail')]
    public function trik_email_gmail_dianggap_satu_alamat(string $email): void
    {
        $this->assertSame('budisantoso@gmail.com', Identitas::kanonikEmail($email));
    }

    #[Test]
    public function email_non_gmail_titiknya_dipertahankan_tetapi_label_plus_dibuang(): void
    {
        $this->assertSame('budi.santoso@smkn5.sch.id', Identitas::kanonikEmail('Budi.Santoso+x@SMKN5.sch.id'));
        $this->assertNotSame(Identitas::kanonikEmail('a.b@smkn5.sch.id'), Identitas::kanonikEmail('ab@smkn5.sch.id'));
        $this->assertNull(Identitas::kanonikEmail(''));
        $this->assertNull(Identitas::kanonikEmail(null));
        $this->assertSame('+label@x.com', Identitas::kanonikEmail('+label@x.com'));
    }

    #[Test]
    public function kunci_ip_menyeragamkan_bentuk_alamat(): void
    {
        $this->assertSame('203.0.113.5', Identitas::kunciIp('203.0.113.5'));
        $this->assertSame('203.0.113.5', Identitas::kunciIp('::ffff:203.0.113.5'));

        // satu blok /64 = satu sumber
        $this->assertSame('2001:db8:1:2::/64', Identitas::kunciIp('2001:db8:1:2::1'));
        $this->assertSame('2001:db8:1:2::/64', Identitas::kunciIp('2001:0db8:0001:0002:ffff:ffff:ffff:ffff'));
        $this->assertNotSame(Identitas::kunciIp('2001:db8:1:2::1'), Identitas::kunciIp('2001:db8:1:3::1'));

        foreach ([null, '', 'bukan-ip', '999.1.1.1'] as $buruk) {
            $this->assertSame('tidak-diketahui', Identitas::kunciIp($buruk));
        }
    }

    #[Test]
    public function ip_dikecualikan_mendukung_alamat_tunggal_dan_cidr(): void
    {
        config(['perkap.ip_dikecualikan' => ['203.0.113.10', '198.51.100.0/24']]);

        $this->assertTrue(Identitas::ipDikecualikan('203.0.113.10'));
        $this->assertTrue(Identitas::ipDikecualikan('198.51.100.77'));
        $this->assertFalse(Identitas::ipDikecualikan('203.0.113.11'));
        $this->assertFalse(Identitas::ipDikecualikan('198.51.101.1'));
        $this->assertFalse(Identitas::ipDikecualikan(null));
        $this->assertFalse(Identitas::ipDikecualikan('bukan-ip'));

        config(['perkap.ip_dikecualikan' => []]);
        $this->assertFalse(Identitas::ipDikecualikan('203.0.113.10'));
    }

    #[Test]
    public function nama_disamarkan_untuk_halaman_publik(): void
    {
        $this->assertSame('Ahmad F****', Identitas::samarkanNama('Ahmad Fauzi'));
        $this->assertSame('Siti N** H****', Identitas::samarkanNama('Siti Nur Haliza'));
        $this->assertSame('Budi', Identitas::samarkanNama('Budi'));
        $this->assertSame('—', Identitas::samarkanNama('   '));
        $this->assertStringNotContainsString('Fauzi', Identitas::samarkanNama('Ahmad Fauzi'));
    }
}
