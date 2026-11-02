@extends('layouts.user', ['title' => 'Detail Pengajuan'])

@section('content')
<div class="mx-auto max-w-2xl">
    <div class="mb-6 flex items-center gap-3">
        <a href="{{ route('user.pengajuan.index') }}" class="flex h-9 w-9 items-center justify-center rounded-full bg-surface dark:bg-white/10">
            <x-icon name="arrow-left" class="h-4 w-4"/>
        </a>
        <div class="flex-1">
            <div class="flex items-center gap-2">
                <h1 class="text-lg font-bold">{{ $pengajuan->kode_pengajuan }}</h1>
                <span class="badge-{{ $pengajuan->warna_status }}">{{ $pengajuan->label_status }}</span>
            </div>
            <p class="text-xs text-secondary/50 dark:text-white/40">Diajukan {{ $pengajuan->created_at->translatedFormat('d M Y, H:i') }}</p>
        </div>
    </div>

    <div class="space-y-5">
        <div class="card-base dark:border-white/10 dark:bg-white/[0.03]">
            <h2 class="text-sm font-semibold">Alat yang Diajukan</h2>
            <div class="mt-3 divide-y divide-secondary/5 dark:divide-white/5">
                @foreach($pengajuan->details as $detail)
                    <div class="flex items-center justify-between py-2.5 text-sm">
                        <div>
                            <span class="block">{{ $detail->alat->nama_alat }}</span>
                            <span class="block text-xs text-secondary/40 dark:text-white/30">{{ $detail->alat->harga_sewa_format }} × {{ $detail->jumlah }}</span>
                        </div>
                        <span class="font-medium">{{ $detail->subtotal_format }}</span>
                    </div>
                @endforeach
            </div>
            <div class="mt-2 flex items-center justify-between rounded-xl bg-gradient-to-r from-accent/10 to-violet-500/10 px-3.5 py-2.5">
                <span class="text-sm font-semibold">Total Biaya Sewa</span>
                <span class="text-base font-bold text-accent">{{ $pengajuan->total_biaya_format }}</span>
            </div>
        </div>

        <div class="card-base dark:border-white/10 dark:bg-white/[0.03]">
            <h2 class="text-sm font-semibold">Jadwal & Keperluan</h2>
            <dl class="mt-3 space-y-2.5 text-sm">
                <div class="flex justify-between"><dt class="text-secondary/50 dark:text-white/40">Tanggal Pinjam</dt><dd class="font-medium">{{ $pengajuan->tanggal_pinjam->format('d M Y') }}</dd></div>
                <div class="flex justify-between"><dt class="text-secondary/50 dark:text-white/40">Tanggal Kembali</dt><dd class="font-medium">{{ $pengajuan->tanggal_kembali->format('d M Y') }}</dd></div>
            </dl>
            <div class="mt-3 border-t border-secondary/5 pt-3 dark:border-white/10">
                <dt class="text-xs text-secondary/50 dark:text-white/40">Keperluan</dt>
                <dd class="mt-1 text-sm">{{ $pengajuan->keperluan }}</dd>
            </div>
        </div>

        @if($pengajuan->status_pengajuan === 'pending')
            <form method="POST" action="{{ route('user.pengajuan.batalkan', $pengajuan) }}" onsubmit="return confirm('Batalkan pengajuan ini?');">
                @csrf @method('DELETE')
                <button type="submit" class="btn-outline w-full !text-danger">Batalkan Pengajuan</button>
            </form>
        @endif

        @if($pengajuan->catatan_admin)
            <div class="card-base dark:border-white/10 dark:bg-white/[0.03]">
                <h2 class="text-sm font-semibold">Catatan Admin</h2>
                <p class="mt-2 text-sm text-secondary/70 dark:text-white/50">{{ $pengajuan->catatan_admin }}</p>
            </div>
        @endif

        @if($pengajuan->status_pengajuan === 'returned')
            <div class="card-base dark:border-white/10 dark:bg-white/[0.03]">
                <h2 class="text-sm font-semibold">Hasil Pengembalian</h2>
                <div class="mt-3 space-y-2">
                    @foreach($pengajuan->details as $detail)
                        <div class="flex items-center justify-between text-sm">
                            <span>{{ $detail->alat->nama_alat }}</span>
                            <span class="badge-{{ $detail->kondisi_saat_kembali === 'good' ? 'success' : 'danger' }}">
                                {{ ['good' => 'Baik', 'damaged' => 'Rusak', 'lost' => 'Hilang'][$detail->kondisi_saat_kembali] ?? '-' }}
                            </span>
                        </div>
                    @endforeach
                </div>
                @if($pengajuan->catatan_pengembalian)
                    <p class="mt-3 border-t border-secondary/5 pt-3 text-sm text-secondary/70 dark:border-white/10 dark:text-white/50">{{ $pengajuan->catatan_pengembalian }}</p>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection
