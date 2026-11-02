@php
    // Query langsung di komponen (bukan lewat Service Provider) supaya
    // mudah ditelusuri satu file oleh siswa saat dipelajari/dipresentasikan.
    $daftarNotifikasi = auth()->user()->notifikasi()->take(6)->get();
    // reorder(): relasi notifikasi() berurutan terbaru; ORDER BY tidak perlu (dan tidak boleh) ikut di query count.
    $jumlahBelumDibaca = auth()->user()->notifikasi()->belumDibaca()->reorder()->count();
@endphp

<div x-data="{ open: false }" @click.outside="open = false" class="relative">
    <button @click="open = !open" type="button"
            class="relative flex h-9 w-9 items-center justify-center rounded-full bg-white text-secondary shadow-sm ring-1 ring-secondary/10 transition hover:-translate-y-0.5 hover:text-accent hover:shadow-md hover:ring-accent/30 dark:bg-white/10 dark:text-white dark:ring-white/10">
        <x-icon name="bell" class="h-4.5 w-4.5"/>
        @if($jumlahBelumDibaca > 0)
            <span class="absolute -right-0.5 -top-0.5 flex h-4.5 w-4.5 items-center justify-center rounded-full bg-gradient-to-br from-danger to-red-600 text-[10px] font-bold text-white shadow-sm shadow-danger/40">
                {{ $jumlahBelumDibaca > 9 ? '9+' : $jumlahBelumDibaca }}
            </span>
        @endif
    </button>

    <div x-show="open" x-transition x-cloak
         class="absolute right-0 z-50 mt-3 w-80 overflow-hidden rounded-2xl border border-secondary/10 bg-white shadow-xl shadow-secondary/10 dark:border-white/10 dark:bg-secondary">
        <div class="flex items-center justify-between border-b border-secondary/5 px-4 py-3 dark:border-white/10">
            <p class="text-sm font-semibold">Notifikasi</p>
            @if($jumlahBelumDibaca > 0)
                <form method="POST" action="{{ route('notifikasi.baca-semua') }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="text-xs font-medium text-accent hover:underline">Tandai semua dibaca</button>
                </form>
            @endif
        </div>

        <div class="max-h-80 divide-y divide-secondary/5 overflow-y-auto dark:divide-white/10">
            @forelse($daftarNotifikasi as $notif)
                <form method="POST" action="{{ route('notifikasi.baca', $notif->id) }}" class="block">
                    @csrf @method('PATCH')
                    <button type="submit" class="flex w-full gap-3 px-4 py-3 text-left transition hover:bg-surface dark:hover:bg-white/5 {{ $notif->is_read ? 'opacity-50' : '' }}">
                        <span @class([
                            'mt-0.5 h-2 w-2 flex-shrink-0 rounded-full',
                            'bg-success' => $notif->warna === 'success',
                            'bg-warning' => $notif->warna === 'warning',
                            'bg-danger' => $notif->warna === 'danger',
                            'bg-accent' => $notif->warna === 'accent',
                        ])></span>
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-medium">{{ $notif->judul }}</span>
                            <span class="mt-0.5 block truncate text-xs text-secondary/60 dark:text-white/50">{{ $notif->pesan }}</span>
                            <span class="mt-1 block text-[11px] text-secondary/40 dark:text-white/30">{{ $notif->created_at->diffForHumans() }}</span>
                        </span>
                    </button>
                </form>
            @empty
                <p class="px-4 py-8 text-center text-sm text-secondary/50 dark:text-white/40">Belum ada notifikasi.</p>
            @endforelse
        </div>

        <a href="{{ route('notifikasi.index') }}" class="block border-t border-secondary/5 px-4 py-2.5 text-center text-xs font-medium text-accent hover:underline dark:border-white/10">
            Lihat semua notifikasi
        </a>
    </div>
</div>
