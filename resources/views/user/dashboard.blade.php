@extends('layouts.user', ['title' => 'Dashboard'])

@section('content')
    @php
        $penipuanSaya = auth()->user()->jumlahPelanggaran('penipuan');
        $batasPenipuan = (int) config('perkap.batas_penipuan', 3);
    @endphp

    {{-- Peringatan dini: anggota perlu tahu sebelum akunnya sampai diblokir --}}
    @if($penipuanSaya > 0)
        <div class="mb-4 flex flex-col gap-2 rounded-2xl border border-warning/30 bg-warning/10 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3">
                <x-icon name="alert" class="mt-0.5 h-5 w-5 flex-shrink-0 text-warning"/>
                <p class="text-sm">Anda tercatat melakukan penipuan peminjaman <span class="font-semibold">{{ $penipuanSaya }} dari {{ $batasPenipuan }} kali</span>. Pada {{ $batasPenipuan }} kali, akun dapat diblokir oleh petugas.</p>
            </div>
            <a href="{{ route('user.kartu') }}" class="btn-outline whitespace-nowrap text-xs !border-warning/40 !text-warning hover:!bg-warning/10">Lihat catatan</a>
        </div>
    @endif

    <div class="animate-in relative mb-6 overflow-hidden rounded-3xl bg-gradient-to-br from-accent via-violet-600 to-info p-6 text-white shadow-lg shadow-accent/20 sm:p-7">
        <div class="pointer-events-none absolute -right-10 -top-16 h-52 w-52 rounded-full bg-white/10 blur-2xl"></div>
        <div class="pointer-events-none absolute -bottom-16 left-1/3 h-40 w-40 rounded-full bg-white/10 blur-2xl"></div>
        <div class="relative flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold">Halo, {{ explode(' ', auth()->user()->nama)[0] }} 👋</h1>
                <p class="mt-1 text-sm text-white/75">Perkap Pramuka SMK Negeri 5 Surakarta</p>
                <a href="{{ route('user.kartu') }}" class="mt-3 inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1.5 text-xs backdrop-blur transition hover:bg-white/25 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/60">
                    <x-icon name="id-card" class="h-3.5 w-3.5"/>
                    <span class="font-mono font-semibold tracking-wide">{{ auth()->user()->kode_anggota }}</span>
                    <span class="text-white/60">Lihat kartu</span>
                </a>
            </div>
            <div class="flex gap-3">
                <div class="rounded-2xl bg-white/15 px-4 py-3 text-center backdrop-blur">
                    <p class="text-xl font-bold"><x-count-up :angka="$ringkasan['aktif']"/></p>
                    <p class="text-[11px] text-white/70">Pengajuan Aktif</p>
                </div>
                <div class="rounded-2xl bg-white/15 px-4 py-3 text-center backdrop-blur">
                    <p class="text-xl font-bold"><x-count-up :angka="$ringkasan['selesai']"/></p>
                    <p class="text-[11px] text-white/70">Selesai</p>
                </div>
            </div>
        </div>
    </div>

    <form method="GET" class="mb-6 flex flex-col gap-3 sm:flex-row">
        <div class="relative flex-1">
            <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-secondary/40"/>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari alat, mis. tenda, hasduk..." class="input-base pl-10">
        </div>
        <select name="kategori" class="input-base sm:w-48" onchange="this.form.submit()">
            <option value="">Semua Kategori</option>
            @foreach($kategoriList as $kat)
                <option value="{{ $kat }}" @selected(request('kategori') === $kat)>{{ $kat }}</option>
            @endforeach
        </select>
    </form>

    <h2 class="mb-3 text-sm font-semibold text-secondary/70 dark:text-white/50">Katalog Perlengkapan</h2>

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
        @forelse($alats as $alat)
            <div class="animate-in" style="--delay: {{ min($loop->index, 11) * 40 }}ms">
                <x-alat-card :alat="$alat"/>
            </div>
        @empty
            <p class="col-span-full py-12 text-center text-sm text-secondary/40">Tidak ada alat yang cocok dengan pencarian.</p>
        @endforelse
    </div>

    <div class="mt-6">{{ $alats->links() }}</div>
@endsection
