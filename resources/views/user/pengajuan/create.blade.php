@extends('layouts.user', ['title' => 'Ajukan Peminjaman'])

@section('content')
<div class="mx-auto max-w-2xl">
    <div class="mb-6 flex items-center gap-3">
        <a href="{{ route('user.pengajuan.index') }}" class="flex h-9 w-9 items-center justify-center rounded-full bg-surface dark:bg-white/10">
            <x-icon name="arrow-left" class="h-4 w-4"/>
        </a>
        <div>
            <h1 class="text-lg font-bold">Ajukan Peminjaman</h1>
            <p class="text-xs text-secondary/50 dark:text-white/40">Periksa keranjang, lalu lengkapi jadwal peminjaman.</p>
        </div>
    </div>

    <template x-if="$store.cart.items.length === 0">
        <div class="card-base py-12 text-center dark:border-white/10 dark:bg-white/[0.03]">
            <x-icon name="cart" class="mx-auto h-10 w-10 text-secondary/20"/>
            <p class="mt-3 text-sm text-secondary/50 dark:text-white/40">Keranjang masih kosong.</p>
            <a href="{{ route('user.dashboard') }}" class="btn-primary mt-4 inline-flex">Jelajahi Katalog</a>
        </div>
    </template>

    <form x-show="$store.cart.items.length > 0" x-cloak x-data="{ loading: false }" @submit="loading = true" method="POST" action="{{ route('user.pengajuan.store') }}" class="space-y-5">
        @csrf

        <div class="card-base dark:border-white/10 dark:bg-white/[0.03]">
            <div class="mb-1 flex items-center justify-between">
                <h2 class="text-sm font-semibold">Keranjang Peminjaman</h2>
                <button type="button" @click="$store.cart.kosongkan()" class="text-xs text-secondary/40 hover:text-danger">Kosongkan</button>
            </div>
            <template x-for="(item, index) in $store.cart.items" :key="item.alat_id">
                <div class="flex items-center justify-between gap-3 border-b border-secondary/5 py-3 last:border-0 dark:border-white/5">
                    <input type="hidden" :name="`items[${index}][alat_id]`" :value="item.alat_id">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium" x-text="item.nama_alat"></p>
                        <p class="text-xs text-secondary/40 dark:text-white/30">
                            <span x-text="item.kode_alat"></span> · maks <span x-text="item.stok_tersedia"></span> <span x-text="item.satuan"></span>
                        </p>
                        <p class="mt-0.5 text-xs font-semibold text-accent">
                            Rp <span x-text="(item.harga_sewa || 0).toLocaleString('id-ID')"></span> ×
                            <span x-text="item.jumlah"></span> =
                            Rp <span x-text="((item.harga_sewa || 0) * item.jumlah).toLocaleString('id-ID')"></span>
                        </p>
                    </div>
                    <div class="flex flex-shrink-0 items-center gap-2">
                        <div class="flex items-center rounded-full border border-secondary/10 dark:border-white/10">
                            <button type="button" @click="$store.cart.ubahJumlah(item.alat_id, item.jumlah - 1)" class="px-2 py-1 text-sm">−</button>
                            <input type="number" :name="`items[${index}][jumlah]`" x-model.number="item.jumlah" :max="item.stok_tersedia" min="1"
                                   @change="$store.cart.ubahJumlah(item.alat_id, item.jumlah)" class="w-9 border-0 bg-transparent text-center text-sm focus:outline-none focus:ring-0">
                            <button type="button" @click="$store.cart.ubahJumlah(item.alat_id, item.jumlah + 1)" class="px-2 py-1 text-sm">+</button>
                        </div>
                        <button type="button" @click="$store.cart.hapus(item.alat_id)" class="text-secondary/30 transition hover:text-danger">
                            <x-icon name="trash" class="h-3.5 w-3.5"/>
                        </button>
                    </div>
                </div>
            </template>

            <div class="flex items-center justify-between rounded-xl bg-gradient-to-r from-accent/10 to-violet-500/10 px-3.5 py-2.5 mt-1">
                <span class="text-sm font-semibold">Total Biaya Sewa</span>
                <span class="text-base font-bold text-accent">Rp <span x-text="$store.cart.totalHarga.toLocaleString('id-ID')"></span></span>
            </div>
        </div>

        <div class="card-base grid grid-cols-1 gap-4 dark:border-white/10 dark:bg-white/[0.03] sm:grid-cols-2">
            <div>
                <label class="label-base">Tanggal Pinjam</label>
                <input type="date" name="tanggal_pinjam" required min="{{ now()->toDateString() }}" value="{{ old('tanggal_pinjam') }}" class="input-base">
                @error('tanggal_pinjam') <p class="mt-1.5 text-xs text-danger">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label-base">Tanggal Kembali</label>
                <input type="date" name="tanggal_kembali" required min="{{ now()->toDateString() }}" value="{{ old('tanggal_kembali') }}" class="input-base">
                @error('tanggal_kembali') <p class="mt-1.5 text-xs text-danger">{{ $message }}</p> @enderror
            </div>
            <div class="sm:col-span-2">
                <label class="label-base">Tujuan Penggunaan / Catatan</label>
                <textarea name="keperluan" rows="3" required class="input-base" placeholder="mis. Latihan rutin regu Penggalang, Sabtu pagi.">{{ old('keperluan') }}</textarea>
                @error('keperluan') <p class="mt-1.5 text-xs text-danger">{{ $message }}</p> @enderror
            </div>
        </div>

        <p class="text-center text-xs text-secondary/40 dark:text-white/30">
            Biaya sewa dibayarkan langsung ke petugas saat pengambilan alat, bukan lewat aplikasi ini.
        </p>

        <x-btn-submit loading-label="Mengirim pengajuan...">Ajukan Peminjaman</x-btn-submit>
    </form>
</div>
@endsection
