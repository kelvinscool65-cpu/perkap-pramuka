<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notifikasi;
use App\Models\PengajuanPeminjaman;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PeminjamanController extends Controller
{
    public function index(Request $request)
    {
        $pengajuan = PengajuanPeminjaman::with(['user', 'details.alat'])
            ->status($request->query('status'))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.peminjaman.index', compact('pengajuan'));
    }

    public function show(PengajuanPeminjaman $peminjaman)
    {
        $peminjaman->load(['user', 'petugas', 'details.alat']);

        return view('admin.peminjaman.show', ['pengajuan' => $peminjaman]);
    }

    /**
     * <<include>> "Status APPROVED & Mengambil Alat" -> Memproses Persetujuan.
     * Stok baru dikurangi DI SINI (bukan saat pengajuan dibuat), sesuai alur
     * pada brief. Dicek ulang ketersediaan stok saat ini untuk jaga-jaga kalau
     * ada perubahan stok sejak pengajuan diajukan (mis. permintaan lain disetujui duluan).
     */
    public function approve(Request $request, PengajuanPeminjaman $peminjaman): RedirectResponse
    {
        if ($peminjaman->status_pengajuan !== 'pending') {
            return back()->with('gagal', 'Pengajuan ini sudah diproses sebelumnya.');
        }

        // Akun yang diblokir/dihapus tidak boleh mendapat barang. (Pengajuan miliknya yang masih
        // menunggu sebenarnya sudah ditolak otomatis saat diblokir; ini jaring pengaman tambahan.)
        if ($peminjaman->user?->isDiblokir() || $peminjaman->user?->trashed()) {
            return back()->with('gagal', 'Pengajuan tidak dapat disetujui karena akun peminjam sedang diblokir atau sudah dihapus. Tolak pengajuan ini, atau buka blokir akunnya lebih dulu di menu Pengguna.');
        }

        $peminjaman->load('details.alat');

        foreach ($peminjaman->details as $detail) {
            if ($detail->jumlah > $detail->alat->stok_tersedia) {
                return back()->with('gagal', "Stok {$detail->alat->nama_alat} tidak lagi mencukupi ({$detail->alat->stok_tersedia} tersisa).");
            }
        }

        DB::transaction(function () use ($peminjaman, $request) {
            foreach ($peminjaman->details as $detail) {
                $detail->alat->decrement('stok_tersedia', $detail->jumlah);
                $detail->alat->update([
                    'status' => $detail->alat->fresh()->stok_tersedia > 0 ? 'tersedia' : 'dipinjam',
                ]);
            }

            $peminjaman->update([
                'status_pengajuan' => 'approved',
                'petugas_id' => $request->user()->id,
                'catatan_admin' => $request->input('catatan_admin'),
                'approved_at' => now(),
            ]);

            Notifikasi::create([
                'user_id' => $peminjaman->user_id,
                'judul' => 'Pengajuan Disetujui',
                'pesan' => "Pengajuan {$peminjaman->kode_pengajuan} telah disetujui. Silakan ambil alat di sekretariat.",
                'tipe' => 'success',
            ]);
        });

        return back()->with('sukses', 'Pengajuan berhasil disetujui.');
    }

    /**
     * <<extend>> "Memproses Persetujuan" -> Status REJECTED. Stok tidak berubah
     * karena belum pernah dikurangi.
     */
    public function reject(Request $request, PengajuanPeminjaman $peminjaman): RedirectResponse
    {
        if ($peminjaman->status_pengajuan !== 'pending') {
            return back()->with('gagal', 'Pengajuan ini sudah diproses sebelumnya.');
        }

        $request->validate(['catatan_admin' => ['required', 'string', 'max:500']], [
            'catatan_admin.required' => 'Alasan penolakan wajib diisi.',
        ]);

        $peminjaman->update([
            'status_pengajuan' => 'rejected',
            'petugas_id' => $request->user()->id,
            'catatan_admin' => $request->input('catatan_admin'),
        ]);

        Notifikasi::create([
            'user_id' => $peminjaman->user_id,
            'judul' => 'Pengajuan Ditolak',
            'pesan' => "Pengajuan {$peminjaman->kode_pengajuan} ditolak: {$request->input('catatan_admin')}",
            'tipe' => 'danger',
        ]);

        return back()->with('sukses', 'Pengajuan telah ditolak.');
    }

    /**
     * "Mengembalikan Alat & Pengecekan Kondisi" -> "Pencatatan Status Akhir".
     * Item kondisi 'good' mengembalikan stok_tersedia; 'damaged'/'lost' mengurangi
     * stok_total permanen (barang keluar dari peredaran) dan memicu notifikasi peringatan.
     */
    public function kembalikan(Request $request, PengajuanPeminjaman $peminjaman): RedirectResponse
    {
        if ($peminjaman->status_pengajuan !== 'approved') {
            return back()->with('gagal', 'Hanya pengajuan berstatus disetujui yang bisa diproses pengembaliannya.');
        }

        $validated = $request->validate([
            'items' => ['required', 'array'],
            'items.*.detail_id' => ['required', 'integer', 'exists:pengajuan_detail,id'],
            'items.*.kondisi' => ['required', 'in:good,damaged,lost'],
            'catatan_pengembalian' => ['nullable', 'string', 'max:500'],
        ]);

        $adaMasalah = false;

        DB::transaction(function () use ($peminjaman, $validated, &$adaMasalah) {
            foreach ($validated['items'] as $item) {
                $detail = $peminjaman->details()->with('alat')->findOrFail($item['detail_id']);
                $detail->update(['kondisi_saat_kembali' => $item['kondisi']]);

                $alat = $detail->alat;

                if ($item['kondisi'] === 'good') {
                    $alat->increment('stok_tersedia', $detail->jumlah);
                } else {
                    // Rusak / hilang: barang keluar permanen dari total inventaris
                    $alat->decrement('stok_total', $detail->jumlah);
                    $adaMasalah = true;
                }

                $alat->update(['status' => $alat->fresh()->stok_tersedia > 0 ? 'tersedia' : 'dipinjam']);
            }

            $statusAkhir = collect($validated['items'])->contains(fn ($i) => $i['kondisi'] === 'lost')
                ? 'lost'
                : (collect($validated['items'])->contains(fn ($i) => $i['kondisi'] === 'damaged') ? 'damaged' : 'good');

            $peminjaman->update([
                'status_pengajuan' => 'returned',
                'status_akhir' => $statusAkhir,
                'catatan_pengembalian' => $validated['catatan_pengembalian'] ?? null,
                'returned_at' => now(),
            ]);
        });

        // Decision "Ada Masalah?" -> Kirim Notifikasi Peringatan (sesuai activity diagram)
        Notifikasi::create([
            'user_id' => $peminjaman->user_id,
            'judul' => $adaMasalah ? 'Peringatan Pengembalian' : 'Pengembalian Berhasil',
            'pesan' => $adaMasalah
                ? "Terdapat item rusak/hilang pada pengembalian {$peminjaman->kode_pengajuan}. Silakan hubungi petugas."
                : "Pengembalian {$peminjaman->kode_pengajuan} telah dikonfirmasi. Terima kasih!",
            'tipe' => $adaMasalah ? 'danger' : 'success',
        ]);

        return redirect()->route('admin.peminjaman.index')->with('sukses', 'Pengembalian berhasil dicatat.');
    }
}
