<?php

namespace App\Support;

use App\Models\User;
use Carbon\Carbon;

/**
 * Kuota pendaftaran akun per alamat IP dalam jendela waktu bergulir
 * (bawaan: maksimal 3 akun per 24 jam — lihat config/perkap.php).
 *
 * Yang dihitung adalah akun yang BERHASIL dibuat dari IP tersebut, termasuk akun yang kemudian
 * dihapus admin (withTrashed): menghapus akun tidak boleh membebaskan kuota, kalau tidak
 * pelaku bisa mendaftar–dihapus–mendaftar lagi tanpa batas.
 */
final class BatasPendaftaran
{
    /**
     * @return array{batas:int, jendelaJam:int, terpakai:int, sisa:int, tercapai:bool, bebasPada:?Carbon, dikecualikan:bool, kunci:string}
     */
    public static function untuk(?string $ip): array
    {
        $batas = max(1, (int) config('perkap.batas_daftar_per_ip'));
        $jam = max(1, (int) config('perkap.jendela_daftar_jam'));
        $kunci = Identitas::kunciIp($ip);

        $dasar = [
            'batas' => $batas, 'jendelaJam' => $jam, 'terpakai' => 0, 'sisa' => $batas,
            'tercapai' => false, 'bebasPada' => null, 'dikecualikan' => false, 'kunci' => $kunci,
        ];

        if (Identitas::ipDikecualikan($ip)) {
            return ['dikecualikan' => true] + $dasar;
        }

        $waktu = User::withTrashed()
            ->where('ip_daftar', $kunci)
            ->where('created_at', '>=', now()->subHours($jam))
            ->orderBy('created_at')
            ->pluck('created_at')
            ->map(fn ($t) => Carbon::parse($t))
            ->values();

        $terpakai = $waktu->count();
        $tercapai = $terpakai >= $batas;

        return [
            'terpakai' => $terpakai,
            'sisa' => max(0, $batas - $terpakai),
            'tercapai' => $tercapai,
            // Slot kosong lagi saat pendaftaran terlama yang "menahan" kuota keluar dari jendela.
            'bebasPada' => $tercapai ? $waktu[$terpakai - $batas]->copy()->addHours($jam) : null,
        ] + $dasar;
    }

    /** Pesan untuk pengguna yang kuotanya habis. */
    public static function pesan(array $kuota): string
    {
        $pesan = "Batas pendaftaran dari jaringan ini sudah tercapai (maksimal {$kuota['batas']} akun per {$kuota['jendelaJam']} jam).";

        if ($kuota['bebasPada']) {
            $pesan .= ' Silakan coba lagi pada '
                .$kuota['bebasPada']->copy()->locale('id')->isoFormat('dddd, D MMMM YYYY [pukul] HH.mm').' WIB';
        }

        $pesan .= '. Bila ini keliru (mis. banyak siswa mendaftar dari Wi-Fi sekolah yang sama), hubungi petugas Perkap via WhatsApp '
            .Identitas::formatNoHp(config('perkap.kontak_wa')).'.';

        // Petunjuk untuk pengembang, hanya di mode lokal (APP_ENV=local + APP_DEBUG=true): orang yang sedang
        // menguji pendaftaran di komputernya sendiri tidak mengira batas ini sebagai kerusakan.
        // Di server produksi (APP_ENV=production) kalimat ini tidak pernah tampil.
        if (app()->isLocal() && config('app.debug')) {
            $pesan .= ' [Mode lokal] Untuk uji coba di komputer sendiri, tambahkan PERKAP_IP_DIKECUALIKAN=127.0.0.1,::1 '
                .'di file .env, lalu jalankan: php artisan config:clear';
        }

        return $pesan;
    }
}
