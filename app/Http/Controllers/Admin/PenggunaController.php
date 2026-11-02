<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pelanggaran;
use App\Models\User;
use App\Services\ManajemenAkun;
use App\Support\KodeAnggota;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Kelola akun peminjam: cari/verifikasi, catat pelanggaran, blokir, buka blokir, hapus, pulihkan.
 * Seluruh aturan bisnisnya ada di App\Services\ManajemenAkun; controller ini hanya menerjemahkan
 * permintaan HTTP dan pesan hasilnya.
 */
class PenggunaController extends Controller
{
    public function __construct(private readonly ManajemenAkun $akun) {}

    public function index(Request $request)
    {
        $cari = trim((string) $request->query('q'));
        $status = in_array($request->query('status'), ['aktif', 'diblokir', 'tinjau', 'dihapus'], true)
            ? $request->query('status')
            : 'semua';
        $batas = (int) config('perkap.batas_penipuan', 3);

        $pengguna = User::query()
            ->peminjam()
            ->withCount([
                'pelanggaran',
                'pelanggaran as penipuan_count' => fn ($q) => $q->where('jenis', 'penipuan'),
                'pengajuan',
                // dipakai jendela "Blokir": berapa pengajuan yang akan ikut ditolak otomatis
                'pengajuan as menunggu_count' => fn ($q) => $q->where('status_pengajuan', 'pending'),
            ])
            ->when($status === 'dihapus', fn ($q) => $q->onlyTrashed())
            ->when($status === 'aktif', fn ($q) => $q->aktif())
            ->when($status === 'diblokir', fn ($q) => $q->diblokir())
            ->when($status === 'tinjau', fn ($q) => $q->layakDitinjau())
            // "semua" memuat juga akun yang dihapus supaya petugas tidak kehilangan jejak
            ->when($status === 'semua', fn ($q) => $q->withTrashed())
            ->when($cari !== '', fn ($q) => $this->terapkanPencarian($q, $cari))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        $statistik = [
            'total' => User::peminjam()->count(),
            'aktif' => User::peminjam()->aktif()->count(),
            'diblokir' => User::peminjam()->diblokir()->count(),
            'tinjau' => User::jumlahLayakDitinjau(),
            'dihapus' => User::onlyTrashed()->peminjam()->count(),
        ];

        return view('admin.pengguna.index', compact('pengguna', 'statistik', 'cari', 'status', 'batas'));
    }

    public function show(User $pengguna)
    {
        abort_unless($pengguna->isPeminjam(), 404);

        // Satu kali hitung → badge "Penipuan N/batas" dan perluDitinjau() tidak menambah query lagi
        $pengguna->loadCount([
            'pelanggaran',
            'pelanggaran as penipuan_count' => fn ($q) => $q->where('jenis', 'penipuan'),
            'pengajuan',
        ]);

        return view('admin.pengguna.show', [
            'pengguna' => $pengguna,
            'pelanggaran' => $pengguna->pelanggaran()->with(['pencatat', 'pengajuan'])->latest()->get(),
            'riwayat' => $pengguna->pengajuan()->with('details.alat')->latest()->take(8)->get(),
            'opsiPengajuan' => $pengguna->pengajuan()->latest()->take(30)->get(['id', 'kode_pengajuan', 'tanggal_pinjam', 'status_pengajuan']),
            'sedangDipinjam' => $pengguna->pengajuan()->where('status_pengajuan', 'approved')->count(),
            'menunggu' => $pengguna->pengajuan()->where('status_pengajuan', 'pending')->count(),
            'batas' => (int) config('perkap.batas_penipuan', 3),
            'pengajuanTerpilih' => (int) request()->query('pengajuan'),
        ]);
    }

    public function blokir(Request $request, User $pengguna): RedirectResponse
    {
        $data = $request->validate([
            'alasan' => ['required', 'string', 'min:10', 'max:500'],
        ], [
            'alasan.required' => 'Alasan pemblokiran wajib diisi.',
            'alasan.min' => 'Alasan pemblokiran minimal 10 karakter agar jelas bagi pemilik akun.',
        ]);

        return $this->jalankan(
            fn () => $this->akun->blokir($pengguna, $request->user(), $data['alasan']),
            "Akun {$pengguna->nama} berhasil diblokir. Sesi yang sedang aktif diputus dan pengajuan yang menunggu ditolak otomatis."
        );
    }

    public function bukaBlokir(Request $request, User $pengguna): RedirectResponse
    {
        return $this->jalankan(
            fn () => $this->akun->bukaBlokir($pengguna, $request->user()),
            "Blokir akun {$pengguna->nama} berhasil dibuka."
        );
    }

    public function destroy(Request $request, User $pengguna): RedirectResponse
    {
        $hasil = $this->jalankan(
            fn () => $this->akun->hapus($pengguna, $request->user()),
            "Akun {$pengguna->nama} berhasil dihapus. Riwayat peminjamannya tetap tersimpan, dan identitasnya tidak bisa dipakai mendaftar ulang."
        );

        // Setelah dihapus, halaman detail tetap bisa dibuka (withTrashed) agar petugas bisa memulihkan.
        return $hasil;
    }

    public function pulihkan(Request $request, User $pengguna): RedirectResponse
    {
        return $this->jalankan(
            fn () => $this->akun->pulihkan($pengguna, $request->user()),
            "Akun {$pengguna->nama} berhasil dipulihkan."
        );
    }

    public function catatPelanggaran(Request $request, User $pengguna): RedirectResponse
    {
        $data = $request->validate([
            'jenis' => ['required', Rule::in(Pelanggaran::daftarJenis())],
            'keterangan' => ['required', 'string', 'min:5', 'max:500'],
            'pengajuan_id' => [
                'nullable', 'integer',
                Rule::exists('pengajuan_peminjaman', 'id')->where('user_id', $pengguna->id),
            ],
        ], [
            'jenis.required' => 'Pilih jenis pelanggaran.',
            'jenis.in' => 'Jenis pelanggaran tidak dikenal.',
            'keterangan.required' => 'Keterangan pelanggaran wajib diisi.',
            'keterangan.min' => 'Keterangan terlalu singkat (minimal 5 karakter).',
            'pengajuan_id.exists' => 'Pengajuan yang dipilih bukan milik pengguna ini.',
        ]);

        return $this->jalankan(
            fn () => $this->akun->catatPelanggaran($pengguna, $request->user(), $data),
            'Pelanggaran dicatat dan pemilik akun sudah diberi tahu lewat notifikasi.'
        );
    }

    public function hapusPelanggaran(Request $request, Pelanggaran $pelanggaran): RedirectResponse
    {
        return $this->jalankan(
            fn () => $this->akun->hapusPelanggaran($pelanggaran, $request->user()),
            'Catatan pelanggaran dihapus.'
        );
    }

    // ----- internal -----

    /** Jalankan tindakan; DomainException (aturan bisnis) ditampilkan sebagai pesan gagal. */
    private function jalankan(callable $tindakan, string $pesanSukses): RedirectResponse
    {
        try {
            $tindakan();
        } catch (DomainException $e) {
            return back()->with('gagal', $e->getMessage());
        }

        return back()->with('sukses', $pesanSukses);
    }

    /** Cari berdasarkan nama, username, email, nomor HP (bentuk apa pun), atau ID Anggota. */
    private function terapkanPencarian($query, string $cari)
    {
        $angka = preg_replace('/\D+/', '', $cari);
        $kode = KodeAnggota::urai($cari)['kode'] ?? null;

        return $query->where(function ($q) use ($cari, $angka, $kode) {
            $q->where('nama', 'like', "%{$cari}%")
                ->orWhere('username', 'like', "%{$cari}%")
                ->orWhere('email', 'like', "%{$cari}%")
                ->orWhere('kode_anggota', 'like', '%'.strtoupper($cari).'%');

            if ($kode) {
                $q->orWhere('kode_anggota', $kode);
            }
            if (strlen($angka) >= 4) {
                // nomor tersimpan dalam bentuk baku "62…": cocokkan tanpa 0 / +62 di depan
                $q->orWhere('no_hp_normal', 'like', '%'.ltrim(preg_replace('/^(62|0)/', '', $angka), '0').'%');
            }
        });
    }
}
