@extends('layouts.admin', ['title' => 'Laporan'])

@section('content')
<div x-data="{ modalPeringatan: null }">

    <form method="GET" class="card-base mb-6 flex flex-wrap items-end gap-3 dark:border-white/10 dark:bg-white/[0.03]">
        <div>
            <label class="label-base">Dari Tanggal</label>
            <input type="date" name="dari" value="{{ $dari }}" class="input-base">
        </div>
        <div>
            <label class="label-base">Sampai Tanggal</label>
            <input type="date" name="sampai" value="{{ $sampai }}" class="input-base">
        </div>
        <div>
            <label class="label-base">Status</label>
            <select name="status" class="input-base">
                <option value="">Semua</option>
                <option value="pending" @selected(request('status') === 'pending')>Menunggu</option>
                <option value="approved" @selected(request('status') === 'approved')>Disetujui</option>
                <option value="returned" @selected(request('status') === 'returned')>Selesai</option>
                <option value="rejected" @selected(request('status') === 'rejected')>Ditolak</option>
            </select>
        </div>
        <button type="submit" class="btn-primary">Terapkan Filter</button>

        <div class="ml-auto flex gap-2">
            <a href="{{ route('admin.laporan.pdf', request()->query()) }}" class="btn-outline">
                <x-icon name="download" class="h-3.5 w-3.5"/> PDF
            </a>
            <a href="{{ route('admin.laporan.excel', request()->query()) }}" class="btn-outline">
                <x-icon name="download" class="h-3.5 w-3.5"/> Excel
            </a>
        </div>
    </form>

    <div class="mb-6 grid grid-cols-2 gap-4 lg:grid-cols-4">
        @php
            $ring = [
                ['label' => 'Total Pengajuan', 'nilai' => $ringkasan['total'], 'warna' => 'accent'],
                ['label' => 'Disetujui', 'nilai' => $ringkasan['approved'], 'warna' => 'success'],
                ['label' => 'Selesai', 'nilai' => $ringkasan['returned'], 'warna' => 'info'],
                ['label' => 'Ditolak', 'nilai' => $ringkasan['rejected'], 'warna' => 'danger'],
            ];
        @endphp
        @foreach($ring as $r)
            <div @class([
                'rounded-2xl border border-secondary/5 p-4 dark:border-white/10',
                'wash-accent' => $r['warna'] === 'accent',
                'wash-success' => $r['warna'] === 'success',
                'wash-info' => $r['warna'] === 'info',
                'wash-danger' => $r['warna'] === 'danger',
            ])>
                <p class="text-xs text-secondary/50 dark:text-white/40">{{ $r['label'] }}</p>
                <p @class([
                    'mt-1 text-2xl font-bold',
                    'text-accent' => $r['warna'] === 'accent',
                    'text-success' => $r['warna'] === 'success',
                    'text-info' => $r['warna'] === 'info',
                    'text-danger' => $r['warna'] === 'danger',
                ])><x-count-up :angka="$r['nilai']"/></p>
            </div>
        @endforeach
    </div>

    <div class="card-base overflow-x-auto !p-0 dark:border-white/10 dark:bg-white/[0.03]">
        <table class="w-full min-w-[860px] text-left text-sm">
            <thead>
                <tr class="border-b border-secondary/5 text-xs text-secondary/50 dark:border-white/10 dark:text-white/40">
                    <th class="px-4 py-3 font-medium">Kode</th>
                    <th class="px-4 py-3 font-medium">Peminjam</th>
                    <th class="px-4 py-3 font-medium">Periode</th>
                    <th class="px-4 py-3 font-medium">Alat</th>
                    <th class="px-4 py-3 font-medium">Biaya</th>
                    <th class="px-4 py-3 font-medium">Status</th>
                    <th class="px-4 py-3 text-right font-medium">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-secondary/5 dark:divide-white/5">
                @forelse($pengajuan as $p)
                    <tr>
                        <td class="px-4 py-3 font-medium">{{ $p->kode_pengajuan }}</td>
                        <td class="px-4 py-3">
                            {{ $p->user?->nama ?? 'Akun dihapus' }}
                            @if($p->user?->trashed())
                                <span class="badge-danger ml-1">Dihapus</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-secondary/70 dark:text-white/50">{{ $p->tanggal_pinjam->format('d/m/y') }} – {{ $p->tanggal_kembali->format('d/m/y') }}</td>
                        <td class="px-4 py-3 text-secondary/70 dark:text-white/50">{{ $p->details->pluck('alat.nama_alat')->implode(', ') }}</td>
                        <td class="px-4 py-3 font-medium text-accent">{{ $p->total_biaya_format }}</td>
                        <td class="px-4 py-3"><span class="badge-{{ $p->warna_status }}">{{ $p->label_status }}</span></td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end">
                                {{-- @js() menghasilkan literal berkutip tunggal -> wajib di atribut berkutip GANDA --}}
                                <button type="button"
                                        @click="modalPeringatan = @js(['id' => $p->id, 'kode' => $p->kode_pengajuan, 'peminjam' => $p->user?->nama ?? 'Akun dihapus'])"
                                        class="btn-outline !px-2.5 !py-1 text-xs !text-danger" title="Kirim notifikasi peringatan ke peminjam">
                                    <x-icon name="alert" class="h-3.5 w-3.5"/> Peringatan
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-12 text-center text-secondary/40">Tidak ada data pada rentang tanggal ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $pengajuan->links() }}</div>

    {{-- FITUR BARU: modal kirim notifikasi peringatan (mis. barang rusak/hilang,
         terlambat, dll) langsung dari halaman Laporan ke peminjam terkait. --}}
    {{-- Di-teleport ke <body> agar tidak terjebak transform/overflow wadah halaman.
         Tutup dengan klik latar (@click.self), tombol Batal/X, atau tombol Esc. --}}
    <template x-teleport="body">
        <div x-show="modalPeringatan" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
             x-transition.opacity
             role="dialog" aria-modal="true" aria-label="Kirim peringatan ke peminjam"
             @click.self="modalPeringatan = null"
             @keydown.escape.window="modalPeringatan = null">
            <div x-show="modalPeringatan" x-transition
                 class="w-full max-w-md rounded-2xl bg-white p-5 text-secondary shadow-xl dark:bg-secondary dark:text-white">
                <div class="mb-4 flex items-start justify-between">
                    <div>
                        <h3 class="flex items-center gap-2 text-sm font-bold text-danger">
                            <x-icon name="alert" class="h-[18px] w-[18px]"/> Kirim Peringatan
                        </h3>
                        <p class="mt-1 text-xs text-secondary/50 dark:text-white/40">
                            Ke <span x-text="modalPeringatan?.peminjam"></span> — <span x-text="modalPeringatan?.kode"></span>
                        </p>
                    </div>
                    <button @click="modalPeringatan = null" type="button" aria-label="Tutup" class="text-secondary/40 hover:text-secondary"><x-icon name="x" class="h-4 w-4"/></button>
                </div>

                <form method="POST" :action="modalPeringatan ? '/admin/laporan/' + modalPeringatan.id + '/peringatan' : '#'">
                    @csrf
                    <label class="label-base">Pesan Peringatan</label>
                    <textarea name="pesan" rows="4" required maxlength="500" class="input-base"
                              placeholder="Contoh: Barang yang Anda pinjam (Tenda Regu) dilaporkan rusak. Mohon segera hubungi petugas Perkap untuk klarifikasi."></textarea>
                    <p class="mt-1 text-[11px] text-secondary/40">Notifikasi ini akan langsung muncul di akun peminjam.</p>

                    <div class="mt-4 flex gap-2">
                        <button type="button" @click="modalPeringatan = null" class="btn-outline flex-1">Batal</button>
                        <button type="submit" class="btn-danger flex-1">Kirim Peringatan</button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>
@endsection
