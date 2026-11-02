<?php

use App\Http\Middleware\PastikanAkunAktif;
use App\Http\Middleware\PeriksaMigrasi;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Pemeriksa kesiapan database — dijalankan paling awal (sebelum session) agar skema lama
        // atau database yang mati menampilkan petunjuk jelas, bukan fitur yang gagal diam-diam.
        $middleware->prepend(PeriksaMigrasi::class);

        // Proxy tepercaya (supaya $request->ip() benar di belakang Nginx/Cloudflare, penting untuk batas
        // pendaftaran per IP) diatur lewat TRUSTED_PROXIES di .env → lihat config/trustedproxy.php.
        // Sengaja BUKAN env() di sini: berkas ini dijalankan sebelum .env dimuat.

        // Akun yang diblokir petugas langsung dikeluarkan di SEMUA halaman web (setelah session aktif).
        $middleware->web(append: [PastikanAkunAktif::class]);

        // Daftarkan alias 'role' supaya bisa dipakai di routes: ->middleware('role:petugas_perkap')
        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
