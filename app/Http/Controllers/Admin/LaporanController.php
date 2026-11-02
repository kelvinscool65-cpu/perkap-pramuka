<?php

namespace App\Http\Controllers\Admin;

use App\Exports\LaporanPeminjamanExport;
use App\Http\Controllers\Controller;
use App\Models\Notifikasi;
use App\Models\PengajuanPeminjaman;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class LaporanController extends Controller
{
    public function index(Request $request)
    {
        $dari = $request->query('dari', now()->startOfMonth()->toDateString());
        $sampai = $request->query('sampai', now()->endOfMonth()->toDateString());

        $pengajuan = PengajuanPeminjaman::with(['user', 'details.alat'])
            ->rentangTanggal($dari, $sampai)
            ->status($request->query('status'))
            ->latest('tanggal_pinjam')
            ->paginate(15)
            ->withQueryString();

        $ringkasan = [
            'total' => $pengajuan->total(),
            'approved' => PengajuanPeminjaman::rentangTanggal($dari, $sampai)->where('status_pengajuan', 'approved')->count(),
            'returned' => PengajuanPeminjaman::rentangTanggal($dari, $sampai)->where('status_pengajuan', 'returned')->count(),
            'rejected' => PengajuanPeminjaman::rentangTanggal($dari, $sampai)->where('status_pengajuan', 'rejected')->count(),
        ];

        return view('admin.laporan.index', compact('pengajuan', 'ringkasan', 'dari', 'sampai'));
    }

    private function ambilDataLaporan(Request $request)
    {
        $dari = $request->query('dari', now()->startOfMonth()->toDateString());
        $sampai = $request->query('sampai', now()->endOfMonth()->toDateString());

        return PengajuanPeminjaman::with(['user', 'details.alat'])
            ->rentangTanggal($dari, $sampai)
            ->status($request->query('status'))
            ->latest('tanggal_pinjam')
            ->get();
    }

    public function exportPdf(Request $request)
    {
        $pengajuan = $this->ambilDataLaporan($request);
        $dari = $request->query('dari', now()->startOfMonth()->toDateString());
        $sampai = $request->query('sampai', now()->endOfMonth()->toDateString());

        $pdf = Pdf::loadView('admin.laporan.pdf', compact('pengajuan', 'dari', 'sampai'))->setPaper('a4', 'landscape');

        return $pdf->download("laporan-peminjaman-{$dari}-sd-{$sampai}.pdf");
    }

    public function exportExcel(Request $request)
    {
        $pengajuan = $this->ambilDataLaporan($request);
        $dari = $request->query('dari', now()->startOfMonth()->toDateString());
        $sampai = $request->query('sampai', now()->endOfMonth()->toDateString());

        return Excel::download(new LaporanPeminjamanExport($pengajuan), "laporan-peminjaman-{$dari}-sd-{$sampai}.xlsx");
    }

    /**
     * FITUR BARU: admin mengirim notifikasi peringatan manual ke peminjam
     * langsung dari halaman Laporan — dipakai saat ditemukan masalah pada
     * suatu peminjaman (barang rusak/hilang, terlambat dikembalikan, dll).
     * Berbeda dari notifikasi otomatis saat proses pengembalian (yang
     * sudah ada di PeminjamanController@kembalikan): ini untuk kasus admin
     * ingin menegur/mengingatkan secara manual dengan pesan bebas.
     */
    public function kirimPeringatan(Request $request, PengajuanPeminjaman $pengajuan)
    {
        $validated = $request->validate([
            'pesan' => ['required', 'string', 'max:500'],
        ], [
            'pesan.required' => 'Pesan peringatan wajib diisi.',
        ]);

        Notifikasi::create([
            'user_id' => $pengajuan->user_id,
            'judul' => 'Peringatan: '.$pengajuan->kode_pengajuan,
            'pesan' => $validated['pesan'],
            'tipe' => 'danger',
        ]);

        return back()->with('sukses', "Notifikasi peringatan berhasil dikirim ke {$pengajuan->user->nama}.");
    }
}
