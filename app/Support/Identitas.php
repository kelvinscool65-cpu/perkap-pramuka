<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Normalisasi data identitas supaya "unik" benar-benar berarti unik.
 *
 * Tanpa normalisasi, satu orang bisa mendaftar berkali-kali dengan trik sederhana:
 *   - nomor sama ditulis "0812…", "+62 812…", "62812…"
 *   - Gmail sama ditulis "nama@gmail.com", "n.a.m.a@gmail.com", "nama+2@gmail.com"
 *   - IPv6 yang berganti-ganti alamat di dalam satu blok /64
 * Semua bentuk di atas dipetakan ke SATU nilai baku, lalu dijaga oleh indeks UNIQUE di database.
 */
final class Identitas
{
    /**
     * Nomor HP/WhatsApp → bentuk baku "62…" (kode negara, tanpa 0 di depan, hanya angka).
     * Contoh: "0812-3456-7890", "+62 812 3456 7890", "62812…", "0062812…" → "6281234567890".
     */
    public static function normalisasiNoHp(?string $nomor): ?string
    {
        $angka = (string) preg_replace('/\D+/', '', (string) $nomor);
        if ($angka === '') {
            return null;
        }

        if (str_starts_with($angka, '0062')) {
            $angka = substr($angka, 2);
        }

        if (str_starts_with($angka, '62')) {
            // "+62 0812…" (nol sisa setelah kode negara) → buang nolnya
            if (str_starts_with($angka, '620')) {
                $angka = '62'.substr($angka, 3);
            }
        } elseif (str_starts_with($angka, '0')) {
            $angka = '62'.substr($angka, 1);
        } elseif (str_starts_with($angka, '8')) {
            $angka = '62'.$angka;
        }

        return $angka;
    }

    /** Nomor seluler Indonesia yang masuk akal: 628 + 8–11 angka (total 11–14 digit). */
    public static function noHpValid(?string $nomor): bool
    {
        return (bool) preg_match('/^628\d{8,11}$/', (string) self::normalisasiNoHp($nomor));
    }

    /** Tampilan ramah: "085877708909" → "0858-7770-8909". */
    public static function formatNoHp(?string $nomor): string
    {
        $angka = (string) preg_replace('/\D+/', '', (string) $nomor);
        if (strlen($angka) < 9) {
            return (string) $nomor;
        }

        return substr($angka, 0, 4).'-'.substr($angka, 4, 4).'-'.substr($angka, 8);
    }

    /**
     * Email → bentuk kanonik untuk pengecekan duplikat. Alamat yang ditampilkan/dipakai login
     * tetap apa adanya (huruf kecil); yang dibakukan hanya kunci pembanding:
     *   - huruf kecil
     *   - bagian "+label" dibuang (nama+tugas@x.com → nama@x.com)
     *   - Gmail: titik di nama diabaikan dan googlemail.com = gmail.com
     */
    public static function kanonikEmail(?string $email): ?string
    {
        $email = strtolower(trim((string) $email));
        $at = strrpos($email, '@');
        if ($email === '' || $at === false || $at === 0) {
            return $email === '' ? null : $email;
        }

        $lokal = substr($email, 0, $at);
        $domain = substr($email, $at + 1);

        $tanpaLabel = explode('+', $lokal, 2)[0];
        if ($tanpaLabel !== '') {
            $lokal = $tanpaLabel;
        }

        if (in_array($domain, ['gmail.com', 'googlemail.com'], true)) {
            $lokal = str_replace('.', '', $lokal);
            $domain = 'gmail.com';
        }

        return $lokal.'@'.$domain;
    }

    /**
     * Kunci IP untuk pembatasan pendaftaran. IPv4 apa adanya; IPv4-mapped IPv6 dikembalikan ke
     * IPv4; IPv6 diringkas ke blok /64 (satu pelanggan rumahan lazimnya menerima seluruh /64,
     * jadi menghitung per alamat penuh akan mudah disiasati dengan berganti alamat).
     */
    public static function kunciIp(?string $ip): string
    {
        $ip = trim((string) $ip);
        if ($ip === '' || ! filter_var($ip, FILTER_VALIDATE_IP)) {
            return 'tidak-diketahui';
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $ip;
        }

        $biner = inet_pton($ip);
        if ($biner === false) {
            return 'tidak-diketahui';
        }

        // ::ffff:a.b.c.d  → a.b.c.d
        if (substr($biner, 0, 12) === "\0\0\0\0\0\0\0\0\0\0\xff\xff") {
            return (string) inet_ntop(substr($biner, 12));
        }

        return inet_ntop(substr($biner, 0, 8).str_repeat("\0", 8)).'/64';
    }

    /** IP yang dikecualikan dari batas pendaftaran (mis. IP publik sekolah). Mendukung CIDR. */
    public static function ipDikecualikan(?string $ip): bool
    {
        $daftar = array_values(array_filter(array_map('trim', (array) config('perkap.ip_dikecualikan', []))));
        if ($daftar === [] || ! filter_var((string) $ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        return IpUtils::checkIp((string) $ip, $daftar);
    }

    /** Nama samaran untuk halaman publik: "Ahmad Fauzi" → "Ahmad F****". */
    public static function samarkanNama(?string $nama): string
    {
        $kata = preg_split('/\s+/u', trim((string) $nama), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        if ($kata === []) {
            return '—';
        }

        $hasil = [array_shift($kata)];
        foreach ($kata as $k) {
            $hasil[] = mb_substr($k, 0, 1).str_repeat('*', min(4, max(2, mb_strlen($k) - 1)));
        }

        return implode(' ', $hasil);
    }
}
