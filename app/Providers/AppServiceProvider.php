<?php

namespace App\Providers;

use App\Support\Identitas;
use Carbon\Carbon;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Nama hari/bulan pada tanggal berbahasa Indonesia (mis. "Sabtu, 24 Oktober 2026")
        Carbon::setLocale(config('app.locale', 'id'));

        /*
         * Pembatas permintaan. Kuncinya memakai Identitas::kunciIp(): IPv6 dihitung per blok /64
         * supaya tidak bisa disiasati dengan berganti-ganti alamat dalam satu jaringan.
         */

        // Percobaan mengirim formulir DAFTAR (berhasil maupun gagal) per IP per jam — menahan spam/bot.
        // Batas JUMLAH AKUN per IP (3 per 24 jam) ada terpisah di App\Support\BatasPendaftaran.
        RateLimiter::for('daftar', fn (Request $request) => Limit::perHour(max(1, (int) config('perkap.batas_percobaan_daftar')))
            ->by(Identitas::kunciIp($request->ip())));

        // Halaman publik Verifikasi ID Anggota: cegah penebakan ID secara massal.
        RateLimiter::for('verifikasi', fn (Request $request) => Limit::perMinute(30)
            ->by(Identitas::kunciIp($request->ip())));
    }
}
