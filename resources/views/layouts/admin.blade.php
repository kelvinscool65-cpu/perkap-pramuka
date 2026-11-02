<!DOCTYPE html>
<html lang="id" x-data="{ dark: localStorage.getItem('dark') === '1' }"
      x-init="$watch('dark', v => localStorage.setItem('dark', v ? '1' : '0'))" :class="{ dark: dark }">
<head>
    @include('components.head', ['title' => $title ?? 'Dashboard Admin'])
</head>
<body class="min-h-screen bg-primary bg-gradient-to-br from-white via-white to-accent/[0.04] font-sans text-secondary antialiased dark:bg-secondary dark:from-secondary dark:via-secondary dark:to-accent/[0.06] dark:text-white" x-data="{ sidebarOpen: false }">

    {{-- Overlay mobile --}}
    <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" class="fixed inset-0 z-30 bg-black/40 lg:hidden"></div>

    {{-- Sidebar (fixed) --}}
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
           class="fixed inset-y-0 left-0 z-40 flex w-60 flex-col border-r border-secondary/5 bg-white transition-transform duration-200 dark:border-white/10 dark:bg-secondary lg:translate-x-0">
        <div class="flex items-center gap-2.5 px-5 py-4">
            <div class="flex h-9 w-14 items-center justify-center rounded-xl bg-white p-1 shadow-sm ring-1 ring-secondary/10">
                <img src="{{ asset('images/logo/gudep-gabungan.png') }}" alt="Logo Gugus Depan" class="h-full w-full object-contain">
            </div>
            <span class="text-sm font-semibold leading-tight">
                Perkap Pramuka<br><span class="text-[11px] font-normal text-secondary/50 dark:text-white/40">Panel Admin</span>
            </span>
        </div>

        <nav class="flex-1 space-y-1 px-3 py-2">
            @php
                // Lencana "Pengguna" = jumlah akun yang sudah mencapai batas penipuan dan layak ditinjau.
                $jumlahTinjau = \App\Models\User::jumlahLayakDitinjau();
                $menu = [
                    ['route' => 'admin.dashboard', 'icon' => 'home', 'label' => 'Dashboard', 'warna' => 'info'],
                    ['route' => 'admin.perlengkapan.index', 'icon' => 'box', 'label' => 'Perlengkapan', 'warna' => 'accent'],
                    ['route' => 'admin.peminjaman.index', 'icon' => 'clipboard', 'label' => 'Peminjaman', 'warna' => 'warning'],
                    ['route' => 'admin.pengguna.index', 'icon' => 'users', 'label' => 'Pengguna', 'warna' => 'rose', 'lencana' => $jumlahTinjau],
                    ['route' => 'admin.laporan.index', 'icon' => 'chart', 'label' => 'Laporan', 'warna' => 'success'],
                ];
            @endphp
            @foreach($menu as $item)
                @php $active = request()->routeIs(str_replace('.index', '', $item['route']).'*') || request()->routeIs($item['route']); @endphp
                <a href="{{ route($item['route']) }}"
                   class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium transition-all
                          {{ $active ? 'bg-gradient-to-r from-accent to-violet-600 text-white shadow-sm shadow-accent/25' : 'text-secondary/70 hover:bg-surface dark:text-white/50 dark:hover:bg-white/5' }}">
                    <span @class([
                        'flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-lg',
                        'bg-white/20' => $active,
                        'bg-info/10 text-info' => ! $active && $item['warna'] === 'info',
                        'bg-accent/10 text-accent' => ! $active && $item['warna'] === 'accent',
                        'bg-warning/10 text-warning' => ! $active && $item['warna'] === 'warning',
                        'bg-success/10 text-success' => ! $active && $item['warna'] === 'success',
                        'bg-rose/10 text-rose' => ! $active && $item['warna'] === 'rose',
                    ])>
                        <x-icon :name="$item['icon']" class="h-3.5 w-3.5"/>
                    </span>
                    {{ $item['label'] }}
                    @if(! empty($item['lencana']))
                        <span @class([
                            'ml-auto rounded-full px-1.5 py-0.5 text-[10px] font-bold leading-none',
                            'bg-white/25 text-white' => $active,
                            'bg-warning/15 text-warning' => ! $active,
                        ]) title="{{ $item['lencana'] }} akun layak ditinjau untuk diblokir">{{ $item['lencana'] }}</span>
                    @endif
                </a>
            @endforeach
        </nav>

        <div class="border-t border-secondary/5 p-3 dark:border-white/10">
            <a href="{{ route('admin.profile.edit') }}" class="flex items-center gap-2.5 rounded-xl px-2 py-2 transition hover:bg-surface dark:hover:bg-white/5">
                <img src="{{ auth()->user()->avatar_url }}" class="h-8 w-8 rounded-full object-cover ring-2 ring-accent/20" alt="{{ auth()->user()->nama }}">
                <span class="min-w-0 text-xs leading-tight">
                    <span class="block truncate font-semibold">{{ auth()->user()->nama }}</span>
                    <span class="block text-secondary/50 dark:text-white/40">Petugas Perkap</span>
                </span>
            </a>
            <form method="POST" action="{{ route('logout') }}" class="mt-1">
                @csrf
                <button type="submit" class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-sm font-medium text-danger transition hover:bg-danger/5">
                    <x-icon name="logout" class="h-4 w-4"/> Keluar
                </button>
            </form>
        </div>
    </aside>

    {{-- Konten utama --}}
    <div class="lg:pl-60">
        <header class="sticky top-0 z-20 flex items-center justify-between gap-4 border-b border-secondary/5 bg-white/90 px-4 py-2.5 backdrop-blur dark:border-white/10 dark:bg-secondary/90 sm:px-6 lg:px-8">
            <div class="flex items-center gap-3">
                <button @click="sidebarOpen = true" type="button" class="flex h-9 w-9 items-center justify-center rounded-full bg-surface dark:bg-white/10 lg:hidden">
                    <x-icon name="menu" class="h-4.5 w-4.5"/>
                </button>
                <h1 class="text-base font-bold">{{ $title ?? 'Dashboard' }}</h1>
            </div>

            <div class="flex items-center gap-1.5">
                <x-notifikasi-dropdown/>
                <button @click="dark = !dark" type="button" class="flex h-9 w-9 items-center justify-center rounded-full bg-white text-secondary shadow-sm ring-1 ring-secondary/10 transition hover:-translate-y-0.5 hover:text-accent hover:shadow-md hover:ring-accent/30 dark:bg-white/10 dark:text-white dark:ring-white/10">
                    <x-icon name="moon" class="h-4.5 w-4.5" x-show="!dark"/>
                    <x-icon name="sun" class="h-4.5 w-4.5" x-show="dark" x-cloak/>
                </button>
            </div>
        </header>

        @include('components.flash')

        <main class="px-4 py-6 sm:px-6 lg:px-8">
            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
