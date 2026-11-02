@extends('layouts.user', ['title' => 'Katalog'])

@section('content')
    <div class="mb-6">
        <h1 class="text-xl font-bold">Katalog Perlengkapan</h1>
        <p class="text-sm text-secondary/60 dark:text-white/50">Pilih alat yang ingin dipinjam, lalu ajukan lewat keranjang.</p>
    </div>

    <form method="GET" class="mb-6 flex flex-col gap-3 sm:flex-row">
        <div class="relative flex-1">
            <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-secondary/40"/>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama atau kode alat..." class="input-base pl-10">
        </div>
        <select name="kategori" class="input-base sm:w-48" onchange="this.form.submit()">
            <option value="">Semua Kategori</option>
            @foreach($kategoriList as $kat)
                <option value="{{ $kat }}" @selected(request('kategori') === $kat)>{{ $kat }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn-outline sm:hidden">Terapkan</button>
    </form>

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
        @forelse($alats as $alat)
            <x-alat-card :alat="$alat"/>
        @empty
            <p class="col-span-full py-12 text-center text-sm text-secondary/40">Tidak ada alat yang cocok dengan pencarian.</p>
        @endforelse
    </div>

    <div class="mt-6">{{ $alats->links() }}</div>
@endsection
