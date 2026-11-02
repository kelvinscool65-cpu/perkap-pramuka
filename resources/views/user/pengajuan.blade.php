@extends('layouts.user', ['title' => 'Riwayat Pengajuan'])

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-xl font-bold">Riwayat Pengajuan</h1>
            <p class="text-sm text-secondary/60 dark:text-white/50">Semua peminjaman yang pernah kamu ajukan.</p>
        </div>
        <a href="{{ route('user.pengajuan.create') }}" class="btn-primary whitespace-nowrap">
            <x-icon name="plus" class="h-4 w-4"/> Ajukan Baru
        </a>
    </div>

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        @php
            $kartu = [
                ['label' => 'Total', 'nilai' => $stats['total'], 'warna' => 'accent'],
                ['label' => 'Menunggu', 'nilai' => $stats['menunggu'], 'warna' => 'warning'],
                ['label' => 'Disetujui', 'nilai' => $stats['disetujui'], 'warna' => 'success'],
                ['label' => 'Selesai', 'nilai' => $stats['selesai'], 'warna' => 'info'],
            ];
        @endphp
        @foreach($kartu as $k)
            <div @class([
                'rounded-2xl border border-secondary/5 p-4 dark:border-white/10',
                'wash-accent' => $k['warna'] === 'accent',
                'wash-warning' => $k['warna'] === 'warning',
                'wash-success' => $k['warna'] === 'success',
                'wash-info' => $k['warna'] === 'info',
            ])>
                <p class="text-xs text-secondary/50 dark:text-white/40">{{ $k['label'] }}</p>
                <p @class(['mt-1 text-2xl font-bold', 'text-accent' => $k['warna'] === 'accent', 'text-warning' => $k['warna'] === 'warning', 'text-success' => $k['warna'] === 'success', 'text-info' => $k['warna'] === 'info'])><x-count-up :angka="$k['nilai']"/></p>
            </div>
        @endforeach
    </div>

    <div class="mb-4 flex flex-wrap gap-2">
        @php $tabs = ['' => 'Semua', 'pending' => 'Menunggu', 'approved' => 'Disetujui', 'returned' => 'Selesai', 'rejected' => 'Ditolak']; @endphp
        @foreach($tabs as $value => $label)
            <a href="{{ route('user.pengajuan.index', array_filter(['status' => $value])) }}"
               class="rounded-full px-3.5 py-1.5 text-sm font-medium transition-all {{ request('status', '') === $value ? 'bg-gradient-to-r from-accent to-violet-600 text-white shadow-sm shadow-accent/25' : 'bg-surface text-secondary/60 hover:bg-accent/10 hover:text-accent dark:bg-white/5 dark:text-white/50' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <div class="space-y-3">
        @forelse($pengajuan as $p)
            <div class="card-base flex flex-col gap-3 dark:border-white/10 dark:bg-white/[0.03] sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <p class="font-semibold">{{ $p->kode_pengajuan }}</p>
                        <span class="badge-{{ $p->warna_status }}">{{ $p->label_status }}</span>
                    </div>
                    <p class="mt-1 text-xs text-secondary/50 dark:text-white/40">
                        {{ $p->details->pluck('alat.nama_alat')->implode(', ') }}
                    </p>
                    <p class="mt-1 text-xs text-secondary/40 dark:text-white/30">
                        {{ $p->tanggal_pinjam->format('d M Y') }} – {{ $p->tanggal_kembali->format('d M Y') }}
                        <span class="mx-1">·</span>
                        <span class="font-semibold text-accent">{{ $p->total_biaya_format }}</span>
                    </p>
                </div>
                <div class="flex gap-2">
                    @if($p->status_pengajuan === 'pending')
                        <form method="POST" action="{{ route('user.pengajuan.batalkan', $p) }}" onsubmit="return confirm('Batalkan pengajuan ini?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-outline !text-danger text-xs">Batalkan</button>
                        </form>
                    @endif
                    <a href="{{ route('user.pengajuan.show', $p) }}" class="btn-outline text-xs">Detail</a>
                </div>
            </div>
        @empty
            <div class="card-base py-12 text-center dark:border-white/10 dark:bg-white/[0.03]">
                <p class="text-sm text-secondary/40">Belum ada pengajuan pada status ini.</p>
                <a href="{{ route('user.dashboard') }}" class="mt-3 inline-block text-sm font-medium text-accent hover:underline">Jelajahi katalog</a>
            </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $pengajuan->links() }}</div>
@endsection

@if(request('sukses_baru'))
    @push('scripts')
        <script>
            localStorage.removeItem('cart_perkap');

            // Efek konfeti kecil saat pengajuan berhasil dikirim — sentuhan unik
            // supaya momen "berhasil" terasa lebih hidup, bukan cuma teks hijau biasa.
            (function konfeti() {
                const warna = ['#8B5CF6', '#10B981', '#F59E0B', '#3B82F6', '#EC4899'];
                const jumlah = 40;
                for (let i = 0; i < jumlah; i++) {
                    const p = document.createElement('div');
                    const ukuran = 6 + Math.random() * 6;
                    p.style.cssText = `
                        position:fixed; top:-20px; left:${Math.random() * 100}vw;
                        width:${ukuran}px; height:${ukuran * 0.4}px;
                        background:${warna[i % warna.length]};
                        opacity:${0.7 + Math.random() * 0.3};
                        border-radius:2px; z-index:9999; pointer-events:none;
                        transform:rotate(${Math.random() * 360}deg);
                    `;
                    document.body.appendChild(p);
                    const jatuh = p.animate([
                        { transform: `translateY(0) rotate(0deg)`, opacity: 1 },
                        { transform: `translateY(${70 + Math.random() * 25}vh) rotate(${360 + Math.random() * 360}deg)`, opacity: 0 },
                    ], {
                        duration: 2200 + Math.random() * 1200,
                        delay: Math.random() * 300,
                        easing: 'cubic-bezier(.25,.46,.45,.94)',
                    });
                    jatuh.onfinish = () => p.remove();
                }
            })();
        </script>
    @endpush
@endif
