@props(['alat'])

@php
    /*
     * Data alat yang dikirim ke keranjang (Alpine store 'cart').
     *
     * Semua nilai DIPAKSA ke tipe yang pasti (int/string) supaya JavaScript yang dihasilkan
     * SELALU valid. Catatan masalah nyata yang pernah terjadi: bila kolom `harga_sewa` belum ada
     * di database (skema lama), nilainya kosong dan tercetak sebagai "harga_sewa: }" -> SyntaxError
     * di Alpine -> tombol "Ajukan" tidak bereaksi sama sekali.
     *
     * Catatan penting tentang @js(): hasilnya literal berkutip TUNGGAL ('...' atau JSON.parse('...')),
     * dengan tanda kutip ganda di-escape. Karena itu @js() WAJIB dipakai di dalam atribut HTML
     * berkutip GANDA (x-data="..."), BUKAN di atribut berkutip tunggal.
     */
    $payload = [
        'alat_id' => (int) $alat->id,
        'nama_alat' => (string) $alat->nama_alat,
        'kode_alat' => (string) $alat->kode_alat,
        'satuan' => (string) ($alat->satuan ?: 'Pcs'),
        'stok_tersedia' => (int) $alat->stok_tersedia,
        'harga_sewa' => (int) ($alat->harga_sewa ?? 0),
    ];
    $habis = $payload['stok_tersedia'] < 1;
@endphp

<div class="card-hover group relative flex flex-col overflow-hidden rounded-2xl border border-secondary/5 bg-white !p-0 shadow-sm shadow-secondary/5 dark:border-white/10 dark:bg-white/[0.03]"
     x-data="{ modalTerbuka: false, alat: @js($payload) }">

    {{-- Area gambar & info: bisa DIKLIK untuk membuka pop-up deskripsi (tombol Ajukan ada di luar area ini) --}}
    <div role="button" tabindex="0" aria-haspopup="dialog"
         @click="modalTerbuka = true"
         @keydown.enter.prevent="modalTerbuka = true"
         @keydown.space.prevent="modalTerbuka = true"
         class="block w-full cursor-pointer text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-accent/40"
         title="Lihat detail {{ $alat->nama_alat }}">
        <div @class([
            'relative aspect-[4/3] w-full overflow-hidden',
            'bg-surface dark:bg-white/5' => $alat->gambar,
            'wash-info' => ! $alat->gambar && $alat->warna_ikon === 'info',
            'wash-accent' => ! $alat->gambar && $alat->warna_ikon === 'accent',
            'wash-success' => ! $alat->gambar && $alat->warna_ikon === 'success',
            'wash-warning' => ! $alat->gambar && $alat->warna_ikon === 'warning',
            'wash-rose' => ! $alat->gambar && $alat->warna_ikon === 'rose',
        ])>
            @if($alat->gambar)
                <img src="{{ $alat->gambar_url }}" alt="{{ $alat->nama_alat }}" class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105" loading="lazy">
            @else
                <x-alat-icon :ikon="$alat->ikon" class="h-full w-full p-6 transition-transform duration-300 group-hover:scale-110"/>
            @endif
            <span class="absolute right-2 top-2 rounded-full bg-white/90 px-2.5 py-1 text-[11px] font-bold text-accent shadow-sm backdrop-blur dark:bg-secondary/90">
                {{ $alat->harga_sewa_format }}
            </span>
            <span class="absolute left-2 top-2 flex h-6 w-6 items-center justify-center rounded-full bg-white/90 text-secondary/60 opacity-0 shadow-sm backdrop-blur transition-opacity group-hover:opacity-100 dark:bg-secondary/90 dark:text-white/60">
                <x-icon name="search" class="h-3 w-3"/>
            </span>
            @if($habis)
                <span class="absolute bottom-2 left-2 rounded-full bg-danger px-2.5 py-1 text-[11px] font-bold text-white shadow-sm">Stok Habis</span>
            @endif
        </div>

        <div class="flex flex-col gap-2.5 p-3.5 pb-0">
            <div>
                <x-kategori-badge :kategori="$alat->kategori"/>
                <h3 class="mt-2 line-clamp-1 text-sm font-semibold">{{ $alat->nama_alat }}</h3>
                <p class="text-xs text-secondary/40 dark:text-white/30">{{ $alat->kode_alat }}</p>
            </div>

            <div>
                <div class="mb-1 flex items-center justify-between text-xs text-secondary/60 dark:text-white/50">
                    <span>Tersedia</span>
                    <span class="font-medium">{{ $alat->stok_tersedia }}/{{ $alat->stok_total }} {{ $alat->satuan }}</span>
                </div>
                <div class="h-1.5 w-full overflow-hidden rounded-full bg-surface dark:bg-white/10">
                    <div class="h-full rounded-full bg-gradient-to-r from-success to-emerald-400" style="width: {{ $alat->persentase_tersedia }}%"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="p-3.5 pt-2.5">
        {{-- Tidak ada interpolasi Blade di dalam handler: datanya diambil dari `alat` (lihat x-data di atas). --}}
        <button
            type="button"
            x-data="{ added: false }"
            @click="if ($store.cart.tambah(alat)) { added = true; setTimeout(() => added = false, 1500) }"
            {{ $habis ? 'disabled' : '' }}
            class="btn-primary w-full !py-1.5 text-xs disabled:bg-secondary/20 disabled:shadow-none">
            <template x-if="!added">
                <span class="flex items-center gap-1.5"><x-icon name="plus" class="h-3.5 w-3.5"/> {{ $habis ? 'Stok Habis' : 'Ajukan' }}</span>
            </template>
            <template x-if="added">
                <span class="flex items-center gap-1.5"><x-icon name="check-circle" class="h-3.5 w-3.5"/> Ditambahkan</span>
            </template>
        </button>
    </div>

    {{-- Pop-up detail alat: dipicu klik gambar/nama di atas. Deskripsi diatur admin lewat
         Perlengkapan > Edit (field "Deskripsi Singkat").
         x-teleport memindahkan pop-up ke <body>: kartu ini punya transform (hover & animasi
         masuk) dan overflow-hidden, yang kalau tidak dipindah akan "memenjarakan" elemen
         position:fixed sehingga pop-up terpotong/salah posisi. --}}
    <template x-teleport="body">
        <div x-show="modalTerbuka" x-cloak
             x-effect="document.body.classList.toggle('overflow-hidden', modalTerbuka)"
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
             x-transition.opacity
             role="dialog" aria-modal="true" aria-label="Detail {{ $alat->nama_alat }}"
             @click="modalTerbuka = false"
             @keydown.escape.window="modalTerbuka = false">
            <div @click.stop x-show="modalTerbuka" x-transition
                 class="max-h-[85vh] w-full max-w-sm overflow-y-auto rounded-2xl bg-white text-secondary shadow-xl dark:bg-secondary dark:text-white">

                <div @class([
                    'relative aspect-[4/3] w-full overflow-hidden rounded-t-2xl',
                    'bg-surface dark:bg-white/5' => $alat->gambar,
                    'wash-info' => ! $alat->gambar && $alat->warna_ikon === 'info',
                    'wash-accent' => ! $alat->gambar && $alat->warna_ikon === 'accent',
                    'wash-success' => ! $alat->gambar && $alat->warna_ikon === 'success',
                    'wash-warning' => ! $alat->gambar && $alat->warna_ikon === 'warning',
                    'wash-rose' => ! $alat->gambar && $alat->warna_ikon === 'rose',
                ])>
                    @if($alat->gambar)
                        <img src="{{ $alat->gambar_url }}" alt="{{ $alat->nama_alat }}" class="h-full w-full object-cover">
                    @else
                        <x-alat-icon :ikon="$alat->ikon" class="h-full w-full p-10"/>
                    @endif
                    <button type="button" @click="modalTerbuka = false" aria-label="Tutup" class="absolute right-3 top-3 flex h-8 w-8 items-center justify-center rounded-full bg-white/90 text-secondary shadow-sm backdrop-blur hover:bg-white dark:bg-secondary/90 dark:text-white">
                        <x-icon name="x" class="h-4 w-4"/>
                    </button>
                </div>

                <div class="space-y-4 p-5">
                    <div>
                        <div class="flex items-center justify-between">
                            <x-kategori-badge :kategori="$alat->kategori"/>
                            <span class="text-base font-bold text-accent">{{ $alat->harga_sewa_format }}</span>
                        </div>
                        <h3 class="mt-2 text-lg font-bold">{{ $alat->nama_alat }}</h3>
                        <p class="text-xs text-secondary/40 dark:text-white/30">{{ $alat->kode_alat }}</p>
                    </div>

                    <div>
                        <div class="mb-1 flex items-center justify-between text-xs text-secondary/60 dark:text-white/50">
                            <span>Ketersediaan</span>
                            <span class="font-medium">{{ $alat->stok_tersedia }}/{{ $alat->stok_total }} {{ $alat->satuan }}</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-surface dark:bg-white/10">
                            <div class="h-full rounded-full bg-gradient-to-r from-success to-emerald-400" style="width: {{ $alat->persentase_tersedia }}%"></div>
                        </div>
                    </div>

                    <div class="rounded-xl bg-surface p-3.5 dark:bg-white/5">
                        <p class="text-xs font-semibold text-secondary/70 dark:text-white/60">Deskripsi</p>
                        @if($alat->deskripsi)
                            <p class="mt-1 whitespace-pre-line text-sm text-secondary/80 dark:text-white/70">{{ $alat->deskripsi }}</p>
                        @else
                            <p class="mt-1 text-sm italic text-secondary/40 dark:text-white/30">Belum ada catatan dari admin untuk alat ini.</p>
                        @endif
                    </div>

                    <button
                        type="button"
                        x-data="{ added: false }"
                        @click="if ($store.cart.tambah(alat)) { added = true; setTimeout(() => { added = false; modalTerbuka = false }, 900) }"
                        {{ $habis ? 'disabled' : '' }}
                        class="btn-primary w-full disabled:bg-secondary/20 disabled:shadow-none">
                        <template x-if="!added">
                            <span class="flex items-center gap-1.5"><x-icon name="plus" class="h-4 w-4"/> {{ $habis ? 'Stok Habis' : 'Ajukan Peminjaman' }}</span>
                        </template>
                        <template x-if="added">
                            <span class="flex items-center gap-1.5"><x-icon name="check-circle" class="h-4 w-4"/> Ditambahkan ke Keranjang</span>
                        </template>
                    </button>
                </div>
            </div>
        </div>
    </template>
</div>
