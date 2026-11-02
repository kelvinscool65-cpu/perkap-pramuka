@extends('layouts.admin', ['title' => 'Perlengkapan'])

@section('content')
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <form method="GET" class="flex flex-1 flex-col gap-3 sm:flex-row">
            <div class="relative flex-1 sm:max-w-xs">
                <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-secondary/40"/>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama atau kode alat..." class="input-base pl-10">
            </div>
            <select name="kategori" class="input-base sm:w-44" onchange="this.form.submit()">
                <option value="">Semua Kategori</option>
                @foreach($kategoriList as $kat)
                    <option value="{{ $kat }}" @selected(request('kategori') === $kat)>{{ $kat }}</option>
                @endforeach
            </select>
            <select name="kondisi" class="input-base sm:w-40" onchange="this.form.submit()">
                <option value="">Semua Kondisi</option>
                <option value="good" @selected(request('kondisi') === 'good')>Baik</option>
                <option value="damaged" @selected(request('kondisi') === 'damaged')>Rusak</option>
                <option value="lost" @selected(request('kondisi') === 'lost')>Hilang</option>
            </select>
            <button type="submit" class="btn-outline sm:hidden">Terapkan</button>
        </form>

        <a href="{{ route('admin.perlengkapan.create') }}" class="btn-primary whitespace-nowrap">
            <x-icon name="plus" class="h-4 w-4"/> Tambah Alat
        </a>
    </div>

    <div class="card-base mt-6 overflow-x-auto !p-0 dark:border-white/10 dark:bg-white/[0.03]">
        <table class="w-full min-w-[800px] text-left text-sm">
            <thead>
                <tr class="border-b border-secondary/5 text-xs text-secondary/50 dark:border-white/10 dark:text-white/40">
                    <th class="px-4 py-3 font-medium">Alat</th>
                    <th class="px-4 py-3 font-medium">Kategori</th>
                    <th class="px-4 py-3 font-medium">Stok</th>
                    <th class="px-4 py-3 font-medium">Harga Sewa</th>
                    <th class="px-4 py-3 font-medium">Kondisi</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 text-right font-medium">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-secondary/5 dark:divide-white/5">
                @forelse($alats as $alat)
                    <tr class="hover:bg-surface/60 dark:hover:bg-white/[0.02]">
                        <td class="px-4 py-2.5">
                            <div class="flex items-center gap-2.5">
                                @if($alat->gambar)
                                    <img src="{{ $alat->gambar_url }}" class="h-9 w-9 flex-shrink-0 rounded-lg object-cover" alt="">
                                @else
                                    <div @class([
                                        'flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-lg',
                                        'wash-info' => $alat->warna_ikon === 'info',
                                        'wash-accent' => $alat->warna_ikon === 'accent',
                                        'wash-success' => $alat->warna_ikon === 'success',
                                        'wash-warning' => $alat->warna_ikon === 'warning',
                                        'wash-rose' => $alat->warna_ikon === 'rose',
                                    ])>
                                        <x-alat-icon :ikon="$alat->ikon" class="h-6 w-6"/>
                                    </div>
                                @endif
                                <div>
                                    <p class="font-medium">{{ $alat->nama_alat }}</p>
                                    <p class="text-xs text-secondary/40 dark:text-white/30">{{ $alat->kode_alat }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-2.5"><x-kategori-badge :kategori="$alat->kategori"/></td>
                        <td class="px-4 py-2.5">
                            <span class="font-medium {{ $alat->stok_tersedia <= \App\Models\Alat::BATAS_STOK_RENDAH ? 'text-danger' : '' }}">{{ $alat->stok_tersedia }}</span>
                            <span class="text-secondary/40 dark:text-white/30">/ {{ $alat->stok_total }} {{ $alat->satuan }}</span>
                        </td>
                        <td class="px-4 py-2.5 font-medium text-accent">{{ $alat->harga_sewa_format }}</td>
                        <td class="px-4 py-2.5">
                            <span class="badge-{{ $alat->kondisi === 'good' ? 'success' : ($alat->kondisi === 'damaged' ? 'warning' : 'danger') }}">
                                {{ ['good' => 'Baik', 'damaged' => 'Rusak', 'lost' => 'Hilang'][$alat->kondisi] }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5">
                            <span class="badge-{{ $alat->status === 'tersedia' ? 'success' : ($alat->status === 'dipinjam' ? 'warning' : 'surface') }}">
                                {{ ['tersedia' => 'Tersedia', 'dipinjam' => 'Dipinjam', 'perawatan' => 'Perawatan'][$alat->status] }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5">
                            <div class="flex justify-end gap-1">
                                <a href="{{ route('admin.perlengkapan.edit', $alat) }}" class="flex h-7 w-7 items-center justify-center rounded-full text-secondary/50 transition hover:bg-surface hover:text-secondary dark:hover:bg-white/10">
                                    <x-icon name="pencil" class="h-3.5 w-3.5"/>
                                </a>
                                <form method="POST" action="{{ route('admin.perlengkapan.destroy', $alat) }}" onsubmit="return confirm('Hapus alat ini?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="flex h-7 w-7 items-center justify-center rounded-full text-secondary/50 transition hover:bg-danger/10 hover:text-danger">
                                        <x-icon name="trash" class="h-3.5 w-3.5"/>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-12 text-center text-secondary/40">Tidak ada alat yang cocok dengan pencarian.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $alats->links() }}</div>
@endsection
