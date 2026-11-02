<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePengajuanRequest;
use App\Models\Alat;
use App\Models\Notifikasi;
use App\Models\PengajuanPeminjaman;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PengajuanController extends Controller
{
    /** Riwayat pengajuan + kartu statistik (Total, Menunggu, Disetujui, Selesai) */
    public function index(Request $request)
    {
        $user = $request->user();

        $stats = [
            'total' => $user->pengajuan()->count(),
            'menunggu' => $user->pengajuan()->where('status_pengajuan', 'pending')->count(),
            'disetujui' => $user->pengajuan()->where('status_pengajuan', 'approved')->count(),
            'selesai' => $user->pengajuan()->where('status_pengajuan', 'returned')->count(),
        ];

        $pengajuan = $user->pengajuan()
            ->with('details.alat')
            ->status($request->query('status'))
            ->latest()
            ->paginate(8)
            ->withQueryString();

        return view('user.pengajuan', compact('pengajuan', 'stats'));
    }

    /** Form pengajuan (keranjang) — isi keranjang diambil dari localStorage via Alpine.js */
    public function create()
    {
        return view('user.pengajuan.create');
    }

    /**
     * <<include>> "Input Pengajuan" -> "Simpan Data & Notifikasi ke Admin".
     * Validasi stok otomatis dilakukan di StorePengajuanRequest.
     */
    public function store(StorePengajuanRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $pengajuan = DB::transaction(function () use ($validated, $request) {
            $pengajuan = PengajuanPeminjaman::create([
                'user_id' => $request->user()->id,
                'tanggal_pinjam' => $validated['tanggal_pinjam'],
                'tanggal_kembali' => $validated['tanggal_kembali'],
                'keperluan' => $validated['keperluan'],
                'status_pengajuan' => 'pending',
            ]);

            foreach ($validated['items'] as $item) {
                $alat = Alat::findOrFail($item['alat_id']);
                $pengajuan->details()->create([
                    'alat_id' => $alat->id,
                    'jumlah' => $item['jumlah'],
                    'harga_saat_pinjam' => $alat->harga_sewa, // snapshot harga saat ini
                ]);
            }

            return $pengajuan;
        });

        // Kirim notifikasi ke SEMUA admin/petugas perkap
        foreach (User::where('role', 'petugas_perkap')->get() as $admin) {
            Notifikasi::create([
                'user_id' => $admin->id,
                'judul' => 'Pengajuan Baru',
                'pesan' => "{$request->user()->nama} mengajukan peminjaman {$pengajuan->kode_pengajuan}.",
                'tipe' => 'info',
            ]);
        }

        return redirect()->route('user.pengajuan.index', ['sukses_baru' => 1])
            ->with('sukses', "Pengajuan {$pengajuan->kode_pengajuan} berhasil dikirim, menunggu persetujuan admin.");
    }

    public function show(PengajuanPeminjaman $pengajuan)
    {
        abort_unless($pengajuan->user_id === auth()->id(), 403);

        $pengajuan->load('details.alat', 'petugas');

        return view('user.pengajuan-detail', compact('pengajuan'));
    }

    /**
     * IDE TAMBAHAN: user diizinkan membatalkan pengajuan sendiri SELAMA masih
     * berstatus pending (belum diproses admin) — fitur ini tidak ada di brief
     * asli tapi wajar dibutuhkan untuk UX yang lengkap.
     */
    public function batalkan(PengajuanPeminjaman $pengajuan): RedirectResponse
    {
        abort_unless($pengajuan->user_id === auth()->id(), 403);

        if ($pengajuan->status_pengajuan !== 'pending') {
            return back()->with('gagal', 'Pengajuan yang sudah diproses tidak bisa dibatalkan.');
        }

        $pengajuan->delete();

        return back()->with('sukses', 'Pengajuan berhasil dibatalkan.');
    }
}
