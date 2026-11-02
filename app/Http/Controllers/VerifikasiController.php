<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Identitas;
use App\Support\KodeAnggota;
use Illuminate\Http\Request;

/**
 * Verifikasi keaslian ID Anggota — halaman PUBLIK (mis. dibuka petugas/guru saat peminjam
 * menunjukkan kartu anggotanya). Sengaja hemat informasi: hanya menyatakan sah/tidaknya ID,
 * nama yang disamarkan, dan bulan bergabung. Akun diblokir/dihapus cukup dinyatakan "tidak aktif"
 * tanpa alasan, supaya data yang sensitif tidak terbuka bagi siapa pun yang memegang sebuah ID.
 */
class VerifikasiController extends Controller
{
    public function index(Request $request)
    {
        $masukan = trim((string) $request->query('kode'));

        return view('verifikasi.index', [
            'masukan' => $masukan,
            'hasil' => $masukan !== '' ? $this->periksa($masukan) : null,
        ]);
    }

    public function show(string $kode)
    {
        return view('verifikasi.index', [
            'masukan' => $kode,
            'hasil' => $this->periksa($kode),
        ]);
    }

    /**
     * Status: format (bentuk bukan ID Anggota) | salah_ketik (karakter pemeriksa tidak cocok) |
     *         tidak_ditemukan | tidak_aktif | valid
     */
    private function periksa(string $masukan): array
    {
        $bagian = KodeAnggota::urai($masukan);

        if (! $bagian) {
            return ['status' => 'format'];
        }
        if (! $bagian['cocok']) {
            return ['status' => 'salah_ketik', 'kode' => $bagian['kode']];
        }

        $user = User::withTrashed()->where('kode_anggota', $bagian['kode'])->first();

        if (! $user) {
            return ['status' => 'tidak_ditemukan', 'kode' => $bagian['kode']];
        }
        if ($user->trashed() || $user->isDiblokir()) {
            return ['status' => 'tidak_aktif', 'kode' => $bagian['kode']];
        }

        return [
            'status' => 'valid',
            'kode' => $bagian['kode'],
            'nama' => Identitas::samarkanNama($user->nama),
            'peran' => $user->role_label,
            'sejak' => $user->created_at?->copy()->locale('id')->isoFormat('MMMM YYYY'),
        ];
    }
}
