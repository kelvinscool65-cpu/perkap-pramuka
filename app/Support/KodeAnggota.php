<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * ID Anggota — identitas unik & PERMANEN setiap akun.
 *
 * Bentuk:   PRK-26-K7M2QH-9
 *            │   │   │      └─ karakter pemeriksa (Luhn mod 32): salah ketik langsung ketahuan
 *            │   │   └──────── 6 karakter acak (alfabet Crockford base-32, tanpa I, L, O, U)
 *            │   └──────────── 2 digit tahun akun dibuat (angkatan)
 *            └──────────────── awalan (config/perkap.php → prefix_id_anggota)
 *
 * Mengapa acak, bukan nomor urut?
 *  - Tidak bisa ditebak / diurutkan, jadi orang lain tidak bisa "mengarang" ID anggota yang valid
 *    atau menebak jumlah anggota (halaman Verifikasi aman dari penebakan).
 *  - 32^6 ≈ 1,07 miliar kombinasi per tahun; tabrakan nyaris mustahil dan tetap dijaga oleh
 *    indeks UNIQUE di database + pembuatan ulang bila ada yang bentrok.
 *
 * Karakter pemeriksa memungkinkan sistem membedakan "ID salah ketik" dari "ID tidak terdaftar".
 */
final class KodeAnggota
{
    /** Alfabet Crockford base-32: tanpa I, L, O, U supaya tidak tertukar dengan 1, 0, dan V. */
    public const ALFABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    private const PANJANG_ACAK = 6;

    /** Panjang bagian setelah awalan: 2 (tahun) + 6 (acak) + 1 (pemeriksa). */
    private const PANJANG_BADAN = 9;

    /** Awalan dari konfigurasi (2–6 huruf), dengan nilai cadangan yang aman. */
    public static function awalan(): string
    {
        $awalan = strtoupper((string) preg_replace('/[^A-Za-z]/', '', (string) config('perkap.prefix_id_anggota', 'PRK')));

        return strlen($awalan) >= 2 ? substr($awalan, 0, 6) : 'PRK';
    }

    /** Buat satu ID Anggota baru (belum dicek ke database; pengecekan bentrok dilakukan pemanggil). */
    public static function buat(?CarbonInterface $waktu = null): string
    {
        $tahun = ($waktu ?? now())->format('y');

        $acak = '';
        for ($i = 0; $i < self::PANJANG_ACAK; $i++) {
            $acak .= self::ALFABET[random_int(0, 31)];
        }

        return self::awalan().'-'.$tahun.'-'.$acak.'-'.self::pemeriksa($tahun.$acak);
    }

    /**
     * Karakter pemeriksa Luhn mod 32 untuk badan ID (tahun + bagian acak).
     * Algoritma standar "Luhn mod N": mendeteksi semua kesalahan satu karakter dan
     * hampir semua penukaran dua karakter bersebelahan.
     */
    public static function pemeriksa(string $badan): string
    {
        $n = 32;
        $faktor = 2;
        $jumlah = 0;

        for ($i = strlen($badan) - 1; $i >= 0; $i--) {
            $titik = strpos(self::ALFABET, $badan[$i]);
            if ($titik === false) {
                throw new \InvalidArgumentException("Karakter '{$badan[$i]}' tidak ada dalam alfabet ID Anggota.");
            }

            $tambah = $faktor * $titik;
            $faktor = $faktor === 2 ? 1 : 2;
            $tambah = intdiv($tambah, $n) + ($tambah % $n);
            $jumlah += $tambah;
        }

        return self::ALFABET[($n - ($jumlah % $n)) % $n];
    }

    /**
     * Urai masukan bebas (huruf kecil, spasi, tanpa tanda hubung, "O" bukan nol, dsb.) menjadi
     * bagian-bagiannya. Mengembalikan null bila bentuknya tidak mungkin sebuah ID Anggota.
     *
     * @return array{awalan:string, tahun:string, acak:string, pemeriksa:string, kode:string, cocok:bool}|null
     */
    public static function urai(?string $masukan): ?array
    {
        $bersih = strtoupper((string) preg_replace('/[^0-9A-Za-z]/', '', (string) $masukan));
        $panjang = strlen($bersih);

        // awalan 2–6 huruf + 9 karakter badan
        if ($panjang < 2 + self::PANJANG_BADAN || $panjang > 6 + self::PANJANG_BADAN) {
            return null;
        }

        $awalan = substr($bersih, 0, $panjang - self::PANJANG_BADAN);
        // Toleransi salah baca yang umum: O→0, I/L→1 (hanya pada badan; awalan memang huruf)
        $badan = strtr(substr($bersih, -self::PANJANG_BADAN), ['O' => '0', 'I' => '1', 'L' => '1']);

        if (! preg_match('/^[A-Z]{2,6}$/', $awalan) || ! preg_match('/^[0-9A-HJKMNP-TV-Z]{9}$/', $badan)) {
            return null;
        }

        $tahun = substr($badan, 0, 2);
        if (! ctype_digit($tahun)) {
            return null;
        }

        $acak = substr($badan, 2, self::PANJANG_ACAK);
        $cek = $badan[8];

        return [
            'awalan' => $awalan,
            'tahun' => $tahun,
            'acak' => $acak,
            'pemeriksa' => $cek,
            'kode' => $awalan.'-'.$tahun.'-'.$acak.'-'.$cek,
            'cocok' => self::pemeriksa($tahun.$acak) === $cek,
        ];
    }

    /** ID dalam bentuk baku bila bentuk dan karakter pemeriksanya benar; selain itu null. */
    public static function baku(?string $masukan): ?string
    {
        $bagian = self::urai($masukan);

        return $bagian && $bagian['cocok'] ? $bagian['kode'] : null;
    }

    public static function valid(?string $masukan): bool
    {
        return self::baku($masukan) !== null;
    }
}
