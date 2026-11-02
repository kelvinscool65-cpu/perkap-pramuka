@extends('layouts.guest', ['title' => 'Verifikasi ID Anggota'])

@section('content')
    <h1 class="text-2xl font-bold">Verifikasi ID Anggota</h1>
    <p class="mt-1.5 text-sm text-secondary/60">Masukkan ID dari Kartu Anggota untuk memastikan kartu itu asli dan akunnya masih aktif.</p>

    <form method="GET" action="{{ route('verifikasi') }}" class="mt-6 space-y-3" x-data="{ loading: false }" @submit="loading = true">
        <div>
            <label for="kode" class="label-base">ID Anggota</label>
            <input id="kode" type="text" name="kode" value="{{ $masukan }}" required autofocus
                   autocomplete="off" autocapitalize="characters" spellcheck="false" maxlength="40"
                   class="input-base font-mono uppercase tracking-wider" placeholder="PRK-26-K7M2QH-9">
        </div>
        <x-btn-submit loading-label="Memeriksa...">
            <x-icon name="shield-check" class="h-4 w-4"/> Periksa ID
        </x-btn-submit>
    </form>

    @if($hasil)
        @php
            $status = $hasil['status'];
            $tampil = match ($status) {
                'valid' => ['success', 'shield-check', 'ID Anggota sah', 'Kartu ini asli dan akunnya aktif.'],
                'tidak_aktif' => ['warning', 'ban', 'Akun tidak aktif', 'ID ini dikenali, tetapi akunnya sedang tidak aktif. Jangan serahkan barang; hubungi petugas Perkap.'],
                'tidak_ditemukan' => ['danger', 'x', 'ID tidak terdaftar', 'Bentuk ID benar, tetapi tidak ada anggota dengan ID ini. Kartu mungkin palsu.'],
                'salah_ketik' => ['warning', 'help', 'ID kemungkinan salah ketik', 'Karakter pemeriksa tidak cocok. Periksa lagi ID di kartu, terutama huruf dan angka yang mirip.'],
                default => ['warning', 'help', 'Bentuk ID tidak dikenali', 'ID Anggota berbentuk seperti PRK-26-K7M2QH-9.'],
            };
            [$warna, $ikon, $judul, $uraian] = $tampil;
        @endphp

        <div role="status" @class([
            'mt-6 rounded-2xl border p-4',
            'border-success/30 bg-success/10' => $warna === 'success',
            'border-warning/30 bg-warning/10' => $warna === 'warning',
            'border-danger/30 bg-danger/10' => $warna === 'danger',
        ])>
            <div class="flex items-start gap-3">
                <span @class([
                    'flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl text-white shadow-sm',
                    'bg-gradient-to-br from-success to-emerald-600' => $warna === 'success',
                    'bg-gradient-to-br from-warning to-orange-500' => $warna === 'warning',
                    'bg-gradient-to-br from-danger to-red-600' => $warna === 'danger',
                ])><x-icon :name="$ikon" class="h-5 w-5"/></span>
                <div class="min-w-0">
                    <p class="text-sm font-bold">{{ $judul }}</p>
                    <p class="mt-0.5 text-sm text-secondary/70">{{ $uraian }}</p>
                </div>
            </div>

            @if($status === 'valid')
                <dl class="mt-4 space-y-2 border-t border-success/20 pt-3 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-secondary/55">ID Anggota</dt><dd class="font-mono font-semibold">{{ $hasil['kode'] }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-secondary/55">Nama</dt><dd class="font-medium">{{ $hasil['nama'] }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-secondary/55">Peran</dt><dd class="font-medium">{{ $hasil['peran'] }}</dd></div>
                    @if($hasil['sejak'])
                        <div class="flex justify-between gap-3"><dt class="text-secondary/55">Anggota sejak</dt><dd class="font-medium">{{ $hasil['sejak'] }}</dd></div>
                    @endif
                </dl>
                <p class="mt-3 text-xs text-secondary/50">Nama disamarkan demi privasi. Cocokkan dengan nama di kartu dan kartu pelajar pemegangnya.</p>
            @endif
        </div>
    @endif

    <p class="mt-6 text-center text-sm text-secondary/60">
        <a href="{{ route('login') }}" class="font-semibold text-accent hover:underline">Kembali ke halaman masuk</a>
    </p>
@endsection
