<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Middleware role-based sesuai brief. Dipakai di routes sbg:
     * ->middleware('role:peminjam')  atau  ->middleware('role:petugas_perkap')
     *
     * CATATAN FIX: brief menyebut shorthand "role:admin", tapi nilai enum di
     * database adalah 'petugas_perkap' — jadi middleware ini dipakai dengan
     * nilai enum asli agar konsisten dan tidak membingungkan (role:petugas_perkap).
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user() || ! in_array($request->user()->role, $roles, true)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
