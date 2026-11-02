@extends('layouts.admin', ['title' => 'Peminjaman'])

@section('content')
    <div class="mb-6 flex flex-wrap gap-2">
        @php $statusTabs = ['' => 'Semua', 'pending' => 'Menunggu', 'approved' => 'Disetujui', 'returned' => 'Selesai', 'rejected' => 'Ditolak']; @endphp
        @foreach($statusTabs as $value => $label)
            <a href="{{ route('admin.peminjaman.index', array_filter(['status' => $value])) }}"
               class="rounded-full px-3.5 py-1.5 text-sm font-medium transition-all {{ request('status', '') === $value ? 'bg-gradient-to-r from-accent to-violet-600 text-white shadow-sm shadow-accent/25' : 'bg-surface text-secondary/60 hover:bg-accent/10 hover:text-accent dark:bg-white/5 dark:text-white/50' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    <div class="card-base overflow-x-auto !p-0 dark:border-white/10 dark:bg-white/[0.03]">
        <table class="w-full min-w-[820px] text-left text-sm">
            <thead>
                <tr class="border-b border-secondary/5 text-xs text-secondary/50 dark:border-white/10 dark:text-white/40">
                    <th class="px-4 py-2.5 font-medium">Kode</th>
                    <th class="px-4 py-2.5 font-medium">Peminjam</th>
                    <th class="px-4 py-2.5 font-medium">Tanggal Pinjam</th>
                    <th class="px-4 py-2.5 font-medium">Tanggal Kembali</th>
                    <th class="px-4 py-2.5 font-medium">Item</th>
                    <th class="px-4 py-2.5 font-medium">Status</th>
                    <th class="px-4 py-2.5 text-right font-medium">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-secondary/5 dark:divide-white/5">
                @forelse($pengajuan as $p)
                    <tr class="hover:bg-surface/60 dark:hover:bg-white/[0.02]">
                        <td class="px-4 py-2.5 font-medium">{{ $p->kode_pengajuan }}</td>
                        <td class="px-4 py-2.5">{{ $p->user->nama }}</td>
                        <td class="px-4 py-2.5 text-secondary/70 dark:text-white/50">{{ $p->tanggal_pinjam->format('d M Y') }}</td>
                        <td class="px-4 py-2.5 text-secondary/70 dark:text-white/50">{{ $p->tanggal_kembali->format('d M Y') }}</td>
                        <td class="px-4 py-2.5 text-secondary/70 dark:text-white/50">{{ $p->details->count() }} jenis</td>
                        <td class="px-4 py-2.5"><span class="badge-{{ $p->warna_status }}">{{ $p->label_status }}</span></td>
                        <td class="px-4 py-2.5">
                            <div class="flex justify-end gap-2">
                                @if($p->status_pengajuan === 'pending')
                                    <form method="POST" action="{{ route('admin.peminjaman.approve', $p) }}" onsubmit="return confirm('Setujui pengajuan {{ $p->kode_pengajuan }}?');">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn-success !px-3 !py-1.5 text-xs">Setujui</button>
                                    </form>
                                @endif
                                <a href="{{ route('admin.peminjaman.show', $p) }}" class="btn-outline !px-3 !py-1.5 text-xs">Detail</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-12 text-center text-secondary/40">Belum ada pengajuan pada status ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $pengajuan->links() }}</div>
@endsection
