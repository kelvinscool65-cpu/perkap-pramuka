<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotifikasiController extends Controller
{
    /** Daftar semua notifikasi milik user yang sedang login (admin maupun peminjam) */
    public function index(Request $request)
    {
        $notifikasi = $request->user()->notifikasi()->paginate(15);

        return view('notifikasi.index', compact('notifikasi'));
    }

    public function tandaiDibaca(Request $request, int $id): RedirectResponse
    {
        $request->user()->notifikasi()->where('id', $id)->update(['is_read' => true]);

        return back();
    }

    public function tandaiSemuaDibaca(Request $request): RedirectResponse
    {
        $request->user()->notifikasi()->belumDibaca()->update(['is_read' => true]);

        return back();
    }
}
