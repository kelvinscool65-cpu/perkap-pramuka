<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Alat;
use Illuminate\Http\Request;

class KatalogController extends Controller
{
    /**
     * Dashboard sekaligus katalog utama peminjam (halaman katalog terpisah
     * dihapus atas permintaan — cukup 1 halaman: ringkasan + katalog lengkap).
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();

        $ringkasan = [
            'aktif' => $user->pengajuan()->whereIn('status_pengajuan', ['pending', 'approved'])->count(),
            'selesai' => $user->pengajuan()->where('status_pengajuan', 'returned')->count(),
        ];

        $alats = Alat::query()
            ->cari($request->query('q'))
            ->kategori($request->query('kategori'))
            ->orderBy('kategori')
            ->orderBy('nama_alat')
            ->paginate(12)
            ->withQueryString();

        return view('user.dashboard', [
            'alats' => $alats,
            'kategoriList' => Alat::KATEGORI,
            'ringkasan' => $ringkasan,
        ]);
    }
}
