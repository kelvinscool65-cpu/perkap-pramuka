<?php

namespace App\Http\Middleware;

use App\Support\SkemaDatabase;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Middleware GLOBAL (dijalankan paling awal, sebelum session) yang memastikan database siap.
 *
 *  - Database tidak bisa dihubungi  -> halaman petunjuk "periksa koneksi/.env".
 *  - Ada migrasi belum dijalankan   -> halaman petunjuk "jalankan php artisan migrate".
 *
 * Tanpa ini, skema lama membuat fitur gagal diam-diam. Bisa dimatikan lewat
 * PERKAP_PERIKSA_MIGRASI=false di .env.
 */
class PeriksaMigrasi
{
    public function handle(Request $request, Closure $next): Response
    {
        // /up = endpoint health-check Laravel; tidak boleh ikut diblokir.
        if (! config('perkap.periksa_migrasi') || $request->is('up')) {
            return $next($request);
        }

        try {
            $tertunda = SkemaDatabase::migrasiTertunda();
        } catch (Throwable $e) {
            report($e);

            return response()->view('errors.database', [
                'detail' => config('app.debug') ? $e->getMessage() : null,
            ], 503);
        }

        if ($tertunda !== []) {
            return response()->view('errors.migrasi', ['tertunda' => $tertunda], 503);
        }

        return $next($request);
    }
}
