<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/** Kartu Anggota: menampilkan ID Anggota unik milik pengguna beserta catatan pelanggarannya sendiri. */
class KartuAnggotaController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        return view('user.kartu', [
            'user' => $user,
            'pelanggaran' => $user->pelanggaran()->latest()->get(),
            'penipuan' => $user->jumlahPelanggaran('penipuan'),
            'batas' => (int) config('perkap.batas_penipuan', 3),
            'baruDaftar' => (bool) session('baru_daftar'),
        ]);
    }
}
