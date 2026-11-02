<!DOCTYPE html>
<html lang="id" x-data="{ dark: localStorage.getItem('dark') === '1' }"
      x-init="$watch('dark', v => localStorage.setItem('dark', v ? '1' : '0'))" :class="{ dark: dark }">
<head>
    @include('components.head', ['title' => $title ?? 'Dashboard'])
</head>
<body class="min-h-screen bg-primary bg-gradient-to-br from-white via-white to-accent/[0.04] font-sans text-secondary antialiased dark:bg-secondary dark:from-secondary dark:via-secondary dark:to-accent/[0.06] dark:text-white">

    <nav x-data="{ mobileOpen: false }" class="sticky top-0 z-40 border-b border-secondary/5 bg-white/90 backdrop-blur dark:border-white/10 dark:bg-secondary/90">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-2.5 sm:px-6 lg:px-8">
            <div class="flex items-center gap-8">
                <a href="{{ route('user.dashboard') }}" class="flex items-center gap-2.5">
                    <div class="flex h-9 w-14 items-center justify-center rounded-xl bg-white p-1 shadow-sm ring-1 ring-secondary/10">
                        <img src="{{ asset('images/logo/gudep-gabungan.png') }}" alt="Logo Gugus Depan" class="h-full w-full object-contain">
                    </div>
                    <span class="hidden text-sm font-semibold leading-tight sm:block">
                        Perkap Pramuka<br><span class="text-[11px] font-normal text-secondary/50 dark:text-white/40">SMK Negeri 5 Surakarta</span>
                    </span>
                </a>

                <div class="hidden items-center gap-1 md:flex">
                    <a href="{{ route('user.dashboard') }}" class="rounded-full px-3.5 py-1.5 text-sm font-medium transition-all {{ request()->routeIs('user.dashboard') ? 'bg-gradient-to-r from-accent to-violet-600 text-white shadow-sm shadow-accent/25' : 'text-secondary/60 hover:bg-surface dark:text-white/50 dark:hover:bg-white/5' }}">Dashboard</a>
                    <a href="{{ route('user.pengajuan.index') }}" class="rounded-full px-3.5 py-1.5 text-sm font-medium transition-all {{ request()->routeIs('user.pengajuan.*') ? 'bg-gradient-to-r from-accent to-violet-600 text-white shadow-sm shadow-accent/25' : 'text-secondary/60 hover:bg-surface dark:text-white/50 dark:hover:bg-white/5' }}">Pengajuan</a>
                    <a href="{{ route('user.profile.edit') }}" class="rounded-full px-3.5 py-1.5 text-sm font-medium transition-all {{ request()->routeIs('user.profile.*') ? 'bg-gradient-to-r from-accent to-violet-600 text-white shadow-sm shadow-accent/25' : 'text-secondary/60 hover:bg-surface dark:text-white/50 dark:hover:bg-white/5' }}">Profile</a>
                </div>
            </div>

            <div class="flex items-center gap-1.5">
                <a href="{{ route('user.pengajuan.create') }}" class="relative flex h-9 w-9 items-center justify-center rounded-full bg-white text-secondary shadow-sm ring-1 ring-secondary/10 transition hover:-translate-y-0.5 hover:text-accent hover:shadow-md hover:ring-accent/30 dark:bg-white/10 dark:text-white dark:ring-white/10" title="Keranjang peminjaman">
                    <x-icon name="cart" class="h-4.5 w-4.5"/>
                    <span x-data x-text="$store.cart.total" x-show="$store.cart.total > 0" x-cloak
                          class="absolute -right-0.5 -top-0.5 flex h-4.5 w-4.5 items-center justify-center rounded-full bg-gradient-to-br from-accent to-violet-600 text-[10px] font-bold text-white shadow-sm"></span>
                </a>

                <x-notifikasi-dropdown/>

                <button @click="dark = !dark" type="button" class="flex h-9 w-9 items-center justify-center rounded-full bg-white text-secondary shadow-sm ring-1 ring-secondary/10 transition hover:-translate-y-0.5 hover:text-accent hover:shadow-md hover:ring-accent/30 dark:bg-white/10 dark:text-white dark:ring-white/10">
                    <x-icon name="moon" class="h-4.5 w-4.5" x-show="!dark"/>
                    <x-icon name="sun" class="h-4.5 w-4.5" x-show="dark" x-cloak/>
                </button>

                <div x-data="{ open: false }" @click.outside="open = false" class="relative ml-0.5">
                    <button @click="open = !open" type="button" class="flex items-center gap-2 rounded-full py-1 pl-1 pr-2.5 transition hover:bg-surface dark:hover:bg-white/5">
                        <img src="{{ auth()->user()->avatar_url }}" class="h-7 w-7 rounded-full object-cover ring-2 ring-accent/20" alt="{{ auth()->user()->nama }}">
                        <span class="hidden text-left text-xs leading-tight sm:block">
                            <span class="block font-semibold">{{ Str::limit(auth()->user()->nama, 14) }}</span>
                            <span class="block text-secondary/50 dark:text-white/40">Peminjam</span>
                        </span>
                        <x-icon name="chevron-down" class="hidden h-3.5 w-3.5 text-secondary/40 sm:block"/>
                    </button>
                    <div x-show="open" x-transition x-cloak class="absolute right-0 z-50 mt-2 w-48 overflow-hidden rounded-2xl border border-secondary/10 bg-white py-1 shadow-xl dark:border-white/10 dark:bg-secondary">
                        <a href="{{ route('user.kartu') }}" class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-surface dark:hover:bg-white/5"><x-icon name="id-card" class="h-4 w-4 text-accent"/> Kartu Anggota</a>
                        <a href="{{ route('user.profile.edit') }}" class="flex items-center gap-2 px-4 py-2 text-sm hover:bg-surface dark:hover:bg-white/5"><x-icon name="user" class="h-4 w-4"/> Profil Saya</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm text-danger hover:bg-danger/5"><x-icon name="logout" class="h-4 w-4"/> Keluar</button>
                        </form>
                    </div>
                </div>

                <button @click="mobileOpen = !mobileOpen" type="button" class="flex h-9 w-9 items-center justify-center rounded-full bg-surface md:hidden dark:bg-white/10">
                    <x-icon name="menu" class="h-4.5 w-4.5"/>
                </button>
            </div>
        </div>

        <div x-show="mobileOpen" x-transition x-cloak class="border-t border-secondary/5 px-4 py-3 md:hidden dark:border-white/10">
            <a href="{{ route('user.dashboard') }}" class="block rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('user.dashboard') ? 'bg-accent/10 text-accent' : '' }}">Dashboard</a>
            <a href="{{ route('user.pengajuan.index') }}" class="block rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('user.pengajuan.*') ? 'bg-accent/10 text-accent' : '' }}">Pengajuan</a>
            <a href="{{ route('user.kartu') }}" class="block rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('user.kartu') ? 'bg-accent/10 text-accent' : '' }}">Kartu Anggota</a>
            <a href="{{ route('user.profile.edit') }}" class="block rounded-xl px-3 py-2 text-sm font-medium {{ request()->routeIs('user.profile.*') ? 'bg-accent/10 text-accent' : '' }}">Profile</a>
        </div>
    </nav>

    @include('components.flash')

    <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        @yield('content')
    </main>

    @include('components.footer')

    {{-- Alpine store untuk keranjang peminjaman multi-item, persist ke localStorage
         supaya bertahan lintas halaman (checklist: "form pengajuan peminjaman
         multi-item / keranjang"). Setiap item juga menyimpan harga_sewa saat
         ditambahkan supaya total biaya bisa dihitung di halaman keranjang. --}}
    <script>
        document.addEventListener('alpine:init', () => {
            const KUNCI = 'cart_perkap';

            // Bentuk item keranjang yang pasti (semua angka benar-benar number). Dipakai saat
            // memuat dari localStorage maupun saat menambah item, sehingga data rusak/format
            // lama tidak pernah membuat perhitungan atau tampilan error.
            const bersihkan = (i) => ({
                alat_id: Number(i.alat_id),
                nama_alat: String(i.nama_alat ?? ''),
                kode_alat: String(i.kode_alat ?? ''),
                satuan: String(i.satuan || 'Pcs'),
                stok_tersedia: Math.max(0, Number(i.stok_tersedia) || 0),
                harga_sewa: Math.max(0, Number(i.harga_sewa) || 0),
                jumlah: Math.max(1, Number(i.jumlah) || 1),
            });

            // Baca keranjang dari localStorage dengan aman: JSON rusak, format lama, atau
            // localStorage yang diblokir browser tidak boleh membuat halaman error.
            const muat = () => {
                try {
                    const data = JSON.parse(localStorage.getItem(KUNCI) || '[]');
                    return Array.isArray(data)
                        ? data.filter(i => i && Number.isFinite(Number(i.alat_id))).map(bersihkan)
                        : [];
                } catch (e) {
                    return [];
                }
            };

            Alpine.store('cart', {
                items: muat(),

                init() {
                    // Sinkron antar tab: keranjang berubah di tab lain -> muat ulang di sini.
                    window.addEventListener('storage', (e) => {
                        if (e.key === KUNCI) this.items = muat();
                    });
                },

                /** Tambah 1 unit alat. Mengembalikan true bila berhasil (tombol memakainya untuk
                 *  menampilkan "Ditambahkan"), false bila data tidak valid / stok habis. */
                tambah(alat) {
                    const id = Number(alat?.alat_id);
                    const stok = Math.max(0, Number(alat?.stok_tersedia) || 0);
                    if (!Number.isFinite(id) || stok < 1) return false;

                    const ada = this.items.find(i => i.alat_id === id);
                    if (ada) {
                        ada.stok_tersedia = stok;
                        ada.jumlah = Math.min(ada.jumlah + 1, stok);
                    } else {
                        this.items.push(bersihkan({ ...alat, alat_id: id, jumlah: 1 }));
                    }
                    this.simpan();
                    return true;
                },
                ubahJumlah(alatId, jumlah) {
                    const item = this.items.find(i => i.alat_id === alatId);
                    if (item) {
                        item.jumlah = Math.max(1, Math.min(Number(jumlah) || 1, item.stok_tersedia));
                        this.simpan();
                    }
                },
                hapus(alatId) {
                    this.items = this.items.filter(i => i.alat_id !== alatId);
                    this.simpan();
                },
                kosongkan() {
                    this.items = [];
                    this.simpan();
                },
                simpan() {
                    try {
                        localStorage.setItem(KUNCI, JSON.stringify(this.items));
                    } catch (e) {
                        // Mode privat / penyimpanan penuh: keranjang tetap berfungsi selama halaman terbuka.
                    }
                },
                get total() {
                    return this.items.reduce((jumlah, i) => jumlah + i.jumlah, 0);
                },
                get totalHarga() {
                    return this.items.reduce((jumlah, i) => jumlah + (i.jumlah * (i.harga_sewa || 0)), 0);
                },
            });
        });
    </script>

    @stack('scripts')
</body>
</html>
