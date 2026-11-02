<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alat;
use App\Models\PengajuanPeminjaman;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // ==== 4 kartu statistik utama ====
        $totalJenisPerlengkapan = Alat::count();

        $sedangDipinjam = DB::table('pengajuan_detail')
            ->join('pengajuan_peminjaman', 'pengajuan_detail.pengajuan_id', '=', 'pengajuan_peminjaman.id')
            ->where('pengajuan_peminjaman.status_pengajuan', 'approved')
            ->sum('pengajuan_detail.jumlah');

        $pengajuanPending = PengajuanPeminjaman::where('status_pengajuan', 'pending')->count();
        $totalDikembalikan = PengajuanPeminjaman::where('status_pengajuan', 'returned')->count();

        // ==== Pengajuan terbaru ====
        $pengajuanTerbaru = PengajuanPeminjaman::with('user')->latest()->take(5)->get();

        // ==== Stok rendah ====
        $stokRendah = Alat::stokRendah()->orderBy('stok_tersedia')->take(6)->get();

        // ==== Stok per kategori (untuk bar chart Chart.js) ====
        $stokPerKategori = Alat::select('kategori', DB::raw('SUM(stok_total) as total'))
            ->groupBy('kategori')
            ->orderBy('kategori')
            ->get();

        // ==== Ringkasan status pengajuan ====
        $statusPengajuan = [
            'pending' => PengajuanPeminjaman::where('status_pengajuan', 'pending')->count(),
            'approved' => PengajuanPeminjaman::where('status_pengajuan', 'approved')->count(),
            'returned' => PengajuanPeminjaman::where('status_pengajuan', 'returned')->count(),
            'rejected' => PengajuanPeminjaman::where('status_pengajuan', 'rejected')->count(),
        ];

        // ==== Anggota: ringkasan akun peminjam + yang layak ditinjau untuk diblokir ====
        $anggota = [
            'aktif' => User::peminjam()->aktif()->count(),
            'diblokir' => User::peminjam()->diblokir()->count(),
            'tinjau' => User::jumlahLayakDitinjau(),
            'batas' => (int) config('perkap.batas_penipuan', 3),
        ];

        return view('admin.dashboard', compact(
            'totalJenisPerlengkapan', 'sedangDipinjam', 'pengajuanPending', 'totalDikembalikan',
            'pengajuanTerbaru', 'stokRendah', 'stokPerKategori', 'statusPengajuan', 'anggota'
        ));
    }
}
