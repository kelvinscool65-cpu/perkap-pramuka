<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAlatRequest;
use App\Http\Requests\UpdateAlatRequest;
use App\Models\Alat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class AlatController extends Controller
{
    public function index()
    {
        $alats = Alat::query()
            ->cari(request('q'))
            ->kategori(request('kategori'))
            ->kondisiFilter(request('kondisi'))
            ->orderBy('kategori')
            ->orderBy('nama_alat')
            ->paginate(12)
            ->withQueryString();

        return view('admin.perlengkapan.index', [
            'alats' => $alats,
            'kategoriList' => Alat::KATEGORI,
        ]);
    }

    public function create()
    {
        return view('admin.perlengkapan.create', ['kategoriList' => Alat::KATEGORI]);
    }

    public function store(StoreAlatRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['kode_alat'] = Alat::generateKodeAlat($data['kategori']);
        $data['stok_tersedia'] = $data['stok_total']; // stok tersedia = stok total saat alat baru dibuat
        $data['status'] = 'tersedia';

        if ($request->hasFile('gambar')) {
            $data['gambar'] = $request->file('gambar')->store('alat', 'public');
        }

        Alat::create($data);

        return redirect()->route('admin.perlengkapan.index')->with('sukses', 'Alat baru berhasil ditambahkan.');
    }

    public function edit(Alat $alat)
    {
        return view('admin.perlengkapan.edit', ['alat' => $alat, 'kategoriList' => Alat::KATEGORI]);
    }

    /**
     * Saat update, selisih stok_total lama vs baru otomatis ditambahkan/dikurangkan
     * ke stok_tersedia supaya jumlah yang sedang dipinjam tetap konsisten.
     */
    public function update(UpdateAlatRequest $request, Alat $alat): RedirectResponse
    {
        $data = $request->validated();

        $selisih = $data['stok_total'] - $alat->stok_total;
        $data['stok_tersedia'] = max(0, $alat->stok_tersedia + $selisih);

        if ($request->hasFile('gambar')) {
            if ($alat->gambar) {
                Storage::disk('public')->delete($alat->gambar);
            }
            $data['gambar'] = $request->file('gambar')->store('alat', 'public');
        }

        $alat->update($data);

        return redirect()->route('admin.perlengkapan.index')->with('sukses', 'Data alat berhasil diperbarui.');
    }

    public function destroy(Alat $alat): RedirectResponse
    {
        $alat->delete(); // soft delete — data tetap ada di riwayat pengajuan lama

        return back()->with('sukses', "Alat \"{$alat->nama_alat}\" berhasil dihapus.");
    }
}
