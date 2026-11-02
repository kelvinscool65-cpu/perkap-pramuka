<!DOCTYPE html>
<html lang="id" x-data="{ dark: false }" :class="{ dark: dark }">
<head>
    @include('components.head', ['title' => $title ?? 'Masuk'])
</head>
<body class="min-h-screen bg-primary font-sans text-secondary antialiased dark:bg-secondary dark:text-white">
    <div class="grid min-h-screen lg:grid-cols-2">
        {{-- Panel kiri: identitas Gugus Depan, disembunyikan di mobile --}}
        <div class="relative hidden flex-col justify-between overflow-hidden bg-gradient-to-br from-secondary via-secondary to-[#1a1230] p-12 text-white lg:flex">
            <div class="flex items-center gap-3">
                <div class="flex h-10 w-14 items-center justify-center rounded-2xl bg-white p-1 shadow-lg">
                    <img src="{{ asset('images/logo/gudep-gabungan.png') }}" alt="Logo Gugus Depan" class="h-full w-full object-contain">
                </div>
                <div>
                    <p class="font-semibold leading-tight">Perkap Pramuka</p>
                    <p class="text-xs text-white/60 leading-tight">SMK Negeri 5 Surakarta</p>
                </div>
            </div>

            {{-- Showcase lambang kedua Gugus Depan — elemen visual utama panel --}}
            <div class="flex flex-col items-center" x-data="{ shown: false }" x-init="setTimeout(() => shown = true, 150)">
                <div x-show="shown" x-transition:enter="transition ease-out duration-700" x-transition:enter-start="opacity-0 scale-90 -translate-y-3" x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                     class="flex items-center gap-6 rounded-3xl bg-white/95 p-5 shadow-2xl">
                    <img src="{{ asset('images/logo/gudep-putra.png') }}" alt="Gudep Wisnu Kencana Putra" class="h-28 w-auto object-contain drop-shadow-sm">
                    <div class="h-20 w-px bg-secondary/10"></div>
                    <img src="{{ asset('images/logo/gudep-putri.png') }}" alt="Gudep Dewi Satya Warma" class="h-28 w-auto object-contain drop-shadow-sm">
                </div>
                <p class="mt-4 text-center text-[11px] uppercase tracking-widest text-white/40">Wisnu Kencana Putra &amp; Dewi Satya Warma</p>
            </div>

            <div class="max-w-sm">
                <h1 class="text-3xl font-bold leading-tight">Kelola perlengkapan Pramuka lebih rapi.</h1>
                <p class="mt-4 text-sm leading-relaxed text-white/60">
                    Ajukan peminjaman, pantau stok, dan kembalikan alat — semua tercatat otomatis di satu tempat.
                </p>
            </div>

            <p class="text-xs text-white/40">© {{ date('Y') }} Perkap Pramuka SMK Negeri 5 Surakarta</p>

            <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-accent/20 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-32 -left-20 h-80 w-80 rounded-full bg-info/10 blur-3xl"></div>
        </div>

        {{-- Panel kanan: form login/register --}}
        <div class="flex items-center justify-center px-6 py-12">
            {{-- min-w-0: kolom boleh menyempit sampai lebar layar walau ada teks panjang tanpa spasi di dalamnya --}}
            <div class="w-full min-w-0 max-w-sm">
                <div class="mb-8 flex items-center gap-3 lg:hidden">
                    <div class="flex h-9 w-14 items-center justify-center rounded-2xl bg-white p-1 shadow-sm ring-1 ring-secondary/10">
                        <img src="{{ asset('images/logo/gudep-gabungan.png') }}" alt="Logo Gugus Depan" class="h-full w-full object-contain">
                    </div>
                    <p class="font-semibold">Perkap Pramuka — SMKN 5 Surakarta</p>
                </div>

                @if($errors->any())
                    <div class="mb-5 rounded-2xl bg-danger/10 px-4 py-3 text-sm text-danger">
                        <p class="font-medium">Periksa kembali isian formulir:</p>
                        <ul class="mt-1 list-inside list-disc space-y-0.5">
                            @foreach($errors->all() as $pesan)
                                <li class="[overflow-wrap:anywhere]">{{ $pesan }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @if(session('status'))
                    <div class="mb-5 rounded-2xl bg-success/10 px-4 py-3 text-sm text-success">{{ session('status') }}</div>
                @endif

                @yield('content')
            </div>
        </div>
    </div>
</body>
</html>
