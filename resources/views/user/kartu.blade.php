@extends('layouts.user', ['title' => 'Kartu Anggota'])

@section('content')
@php $urlVerifikasi = route('verifikasi.cek', $user->kode_anggota); @endphp

<div class="mx-auto grid max-w-5xl grid-cols-1 gap-6 lg:grid-cols-5">
    {{-- Kartu + tindakan --}}
    <div class="lg:col-span-3">
        <x-kartu-anggota :user="$user" :baru="$baruDaftar" class="animate-in"/>

        @if($baruDaftar)
            <div class="mt-4 flex items-start gap-3 rounded-2xl bg-success/10 px-4 py-3 text-sm text-success">
                <x-icon name="check-circle" class="mt-0.5 h-5 w-5 flex-shrink-0"/>
                <p>Akun siap dipakai. Ini ID Anggota Anda — catat atau tunjukkan kartu ini saat mengambil alat. ID tidak dapat diubah.</p>
            </div>
        @endif

        <div class="mt-4 flex flex-wrap gap-2">
            <div x-data="salinTeks(@js($urlVerifikasi))">
                <button type="button" @click="salin()" class="btn-outline">
                    <x-icon name="link" class="h-4 w-4" x-show="!tersalin"/>
                    <x-icon name="check" class="h-4 w-4 text-success" x-show="tersalin" x-cloak/>
                    <span x-text="tersalin ? 'Tautan tersalin' : 'Salin tautan verifikasi'">Salin tautan verifikasi</span>
                </button>
            </div>
            <a href="{{ $urlVerifikasi }}" class="btn-outline">
                <x-icon name="shield-check" class="h-4 w-4"/> Lihat tampilan verifikasi
            </a>
        </div>
    </div>

    {{-- Penjelasan & catatan --}}
    <div class="space-y-5 lg:col-span-2">
        <div class="card-base space-y-3.5 dark:border-white/10 dark:bg-white/[0.03]">
            <h2 class="text-sm font-semibold">Tentang ID Anggota</h2>

            <div class="flex gap-3">
                <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-xl bg-accent/10 text-accent"><x-icon name="lock" class="h-4 w-4"/></span>
                <p class="text-sm text-secondary/70 dark:text-white/60"><span class="font-semibold text-secondary dark:text-white">Permanen.</span> Dibuat otomatis saat akun dibuat dan tidak bisa diubah oleh siapa pun, termasuk petugas.</p>
            </div>
            <div class="flex gap-3">
                <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-xl bg-info/10 text-info"><x-icon name="id-card" class="h-4 w-4"/></span>
                <p class="text-sm text-secondary/70 dark:text-white/60"><span class="font-semibold text-secondary dark:text-white">Satu anggota, satu ID.</span> Tidak ada dua anggota dengan ID yang sama, dan ID tidak dipakai ulang walau akunnya dihapus.</p>
            </div>
            <div class="flex gap-3">
                <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-xl bg-success/10 text-success"><x-icon name="shield-check" class="h-4 w-4"/></span>
                <p class="text-sm text-secondary/70 dark:text-white/60"><span class="font-semibold text-secondary dark:text-white">Bisa diverifikasi.</span> Karakter pemeriksa di ujung ID membuat salah ketik langsung ketahuan, dan petugas dapat memeriksa keasliannya di halaman Verifikasi.</p>
            </div>
        </div>

        <div class="card-base dark:border-white/10 dark:bg-white/[0.03]">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-sm font-semibold">Catatan pelanggaran Anda</h2>
                <span @class(['badge-danger' => $penipuan >= $batas, 'badge-warning' => $penipuan > 0 && $penipuan < $batas, 'badge-success' => $penipuan === 0])>
                    Penipuan {{ $penipuan }} dari {{ $batas }}
                </span>
            </div>

            <div class="mt-3 flex gap-1.5" role="img" aria-label="Penipuan {{ $penipuan }} dari {{ $batas }}">
                @for($i = 1; $i <= $batas; $i++)
                    <span @class(['h-2 flex-1 rounded-full', 'bg-gradient-to-r from-danger to-red-600' => $i <= $penipuan, 'bg-surface dark:bg-white/10' => $i > $penipuan])></span>
                @endfor
            </div>
            <p class="mt-2 text-xs text-secondary/55 dark:text-white/45">
                Bila tercatat melakukan penipuan peminjaman sebanyak {{ $batas }} kali, akun dapat diblokir oleh petugas.
            </p>

            <div class="mt-4 divide-y divide-secondary/5 dark:divide-white/5">
                @forelse($pelanggaran as $p)
                    <div class="py-3 first:pt-0 last:pb-0">
                        <div class="flex items-center justify-between gap-2">
                            <span class="badge-{{ $p->warna_jenis }}">{{ $p->label_jenis }}</span>
                            <span class="text-[11px] text-secondary/40 dark:text-white/30">{{ $p->created_at->locale('id')->isoFormat('D MMM YYYY') }}</span>
                        </div>
                        <p class="mt-1.5 text-sm text-secondary/70 dark:text-white/60">{{ $p->keterangan }}</p>
                    </div>
                @empty
                    <p class="py-1 text-sm text-secondary/55 dark:text-white/45">Belum ada catatan pelanggaran. Pertahankan, ya.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
