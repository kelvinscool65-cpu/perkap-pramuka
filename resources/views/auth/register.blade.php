@extends('layouts.guest', ['title' => 'Daftar'])

@section('content')
@php
    $penuh = $kuota['tercapai'];
    $batasPenipuan = (int) config('perkap.batas_penipuan', 3);
@endphp

<h1 class="text-2xl font-bold">Selamat Datang</h1>
<p class="mt-1.5 text-sm text-secondary/60">Buat akun peminjam untuk mulai mengajukan peminjaman alat.</p>

{{-- Pemberitahuan ID Anggota: pengguna tahu apa yang akan didapat sebelum mengisi --}}
<div class="mt-5 flex items-start gap-3 rounded-2xl bg-accent/10 px-3.5 py-3">
    <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-accent to-violet-600 text-white shadow-sm shadow-accent/30">
        <x-icon name="id-card" class="h-4 w-4"/>
    </span>
    <p class="text-xs leading-relaxed text-secondary/70">
        <span class="font-semibold text-secondary">Anda akan mendapat ID Anggota unik.</span>
        Dibuat otomatis setelah mendaftar, tidak bisa diubah, dan tidak akan sama dengan anggota lain.
    </p>
</div>

{{-- Kuota pendaftaran per IP sudah habis: beri tahu sebelum pengguna capek mengisi formulir --}}
@if($penuh)
    <div role="alert" class="mt-4 flex items-start gap-3 rounded-2xl border border-warning/30 bg-warning/10 px-4 py-3 text-sm">
        <x-icon name="clock" class="mt-0.5 h-5 w-5 flex-shrink-0 text-warning"/>
        <p class="min-w-0 [overflow-wrap:anywhere]">{{ \App\Support\BatasPendaftaran::pesan($kuota) }}</p>
    </div>
@endif

<form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4" x-data="{ loading: false }" @submit="loading = true">
    @csrf

    {{-- Kolom jebakan untuk bot: disembunyikan dari manusia & pembaca layar, harus tetap kosong --}}
    <div class="absolute -left-[9999px] top-auto h-0 w-0 overflow-hidden" aria-hidden="true">
        <label for="website">Situs web (jangan diisi)</label>
        <input type="text" id="website" name="website" tabindex="-1" autocomplete="off" value="">
    </div>

    <div>
        <label for="nama" class="label-base">Nama Lengkap</label>
        <input id="nama" type="text" name="nama" value="{{ old('nama') }}" required autofocus autocomplete="name" maxlength="100" class="input-base" placeholder="Nama sesuai identitas">
        @error('nama') <p class="mt-1.5 text-xs text-danger">{{ $message }}</p> @enderror
    </div>

    <div class="grid grid-cols-2 gap-3">
        <div>
            <label for="username" class="label-base">Username</label>
            <input id="username" type="text" name="username" value="{{ old('username') }}" required minlength="4" maxlength="30"
                   autocomplete="username" autocapitalize="none" spellcheck="false" class="input-base" placeholder="username">
            @error('username') <p class="mt-1.5 text-xs text-danger">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="no_hp" class="label-base">No. WhatsApp</label>
            <input id="no_hp" type="tel" inputmode="tel" name="no_hp" value="{{ old('no_hp') }}" required autocomplete="tel" maxlength="20" class="input-base" placeholder="0812xxxxxxxx">
            @error('no_hp') <p class="mt-1.5 text-xs text-danger">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label for="email" class="label-base">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" maxlength="150" class="input-base" placeholder="nama@email.com">
        @error('email') <p class="mt-1.5 text-xs text-danger">{{ $message }}</p> @enderror
        <p class="mt-1.5 text-[11px] text-secondary/50">Email dan nomor WhatsApp hanya bisa dipakai satu akun.</p>
    </div>

    <div x-data="{ show: false }">
        <label for="password" class="label-base">Password</label>
        <div class="relative">
            <input :type="show ? 'text' : 'password'" id="password" name="password" required autocomplete="new-password" class="input-base pr-11" placeholder="Minimal 8 karakter">
            <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex w-10 items-center justify-center text-secondary/40 hover:text-secondary" :aria-label="show ? 'Sembunyikan password' : 'Tampilkan password'">
                <x-icon name="eye" class="h-4 w-4" x-show="!show"/>
                <x-icon name="eye-off" class="h-4 w-4" x-show="show" x-cloak/>
            </button>
        </div>
        @error('password') <p class="mt-1.5 text-xs text-danger">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="password_confirmation" class="label-base">Konfirmasi Password</label>
        <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password" class="input-base" placeholder="Ulangi password">
    </div>

    {{-- Persetujuan tata tertib (wajib) --}}
    <div x-data="{ buka: false }" class="rounded-xl border border-secondary/10 bg-surface/60 p-3">
        <label class="flex cursor-pointer items-start gap-2.5">
            <input type="checkbox" name="setuju" value="1" required @checked(old('setuju')) class="mt-0.5 h-4 w-4 flex-shrink-0 cursor-pointer rounded border-secondary/25 text-accent focus:ring-accent/30">
            <span class="text-xs leading-relaxed text-secondary/70">
                Saya menyatakan data di atas benar dan menyetujui
                <button type="button" @click.prevent="buka = !buka" :aria-expanded="buka" aria-controls="tata-tertib" class="font-semibold text-accent hover:underline">tata tertib peminjaman</button>.
            </span>
        </label>

        <ol id="tata-tertib" x-show="buka" x-cloak x-transition class="mt-3 list-decimal space-y-1.5 border-t border-secondary/10 pt-3 pl-4 text-xs leading-relaxed text-secondary/65">
            <li>Gunakan data diri yang benar. Satu orang hanya boleh memiliki satu akun.</li>
            <li>ID Anggota dibuat otomatis, bersifat permanen, dan tidak dapat diubah atau dipindahkan.</li>
            <li>Kembalikan barang tepat waktu dan dalam kondisi baik. Barang rusak atau hilang wajib diganti.</li>
            <li>Penipuan peminjaman (tidak mengembalikan barang, data palsu, atau meminjam atas nama orang lain) dicatat petugas. Setelah {{ $batasPenipuan }} kali, akun dapat diblokir.</li>
            <li>Dari satu jaringan internet hanya bisa dibuat maksimal {{ $kuota['batas'] }} akun dalam {{ $kuota['jendelaJam'] }} jam.</li>
        </ol>
        @error('setuju') <p class="mt-2 text-xs text-danger">{{ $message }}</p> @enderror
    </div>

    @if($penuh)
        <button type="button" disabled class="btn-primary w-full">Pendaftaran dibatasi sementara</button>
    @else
        <x-btn-submit loading-label="Mendaftarkan...">Daftar</x-btn-submit>
    @endif
</form>

<p class="mt-6 text-center text-sm text-secondary/60">
    Sudah punya akun?
    <a href="{{ route('login') }}" class="font-semibold text-accent hover:underline">Masuk di sini</a>
</p>
<p class="mt-2 text-center text-xs text-secondary/45">
    <a href="{{ route('verifikasi') }}" class="hover:text-accent hover:underline">Periksa keaslian ID Anggota</a>
</p>
@endsection
