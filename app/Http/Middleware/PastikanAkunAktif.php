<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Akun yang diblokir petugas langsung dikeluarkan pada permintaan berikutnya, di semua halaman.
 *
 * Ini lapisan yang berlaku untuk SEMUA driver session. Saat session memakai database, pemblokiran
 * juga menghapus baris session pengguna (lihat ManajemenAkun) sehingga efeknya seketika; middleware
 * ini menjamin hasil yang sama untuk driver lain (file/cookie/redis) maupun cookie "ingat saya".
 *
 * Akun yang DIHAPUS (soft delete) tidak perlu ditangani di sini: provider auth Laravel tidak lagi
 * menemukan pengguna terhapus, sehingga ia otomatis dianggap belum login.
 */
class PastikanAkunAktif
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isDiblokir()) {
            $pesan = $user->pesanBlokir();

            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['login' => $pesan]);
        }

        return $next($request);
    }
}
