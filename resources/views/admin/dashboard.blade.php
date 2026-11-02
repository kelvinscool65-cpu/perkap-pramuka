@extends('layouts.admin', ['title' => 'Dashboard'])

@section('content')
    <div class="animate-in relative mb-6 overflow-hidden rounded-3xl bg-gradient-to-br from-secondary via-[#1a1230] to-accent p-6 text-white shadow-lg shadow-accent/20 sm:p-7">
        <div class="pointer-events-none absolute -right-10 -top-16 h-52 w-52 rounded-full bg-accent/20 blur-2xl"></div>
        <div class="pointer-events-none absolute -bottom-16 left-1/3 h-40 w-40 rounded-full bg-info/20 blur-2xl"></div>
        <div class="relative">
            <h1 class="text-2xl font-bold">Halo, {{ explode(' ', auth()->user()->nama)[0] }} 👋</h1>
            <p class="mt-1 text-sm text-white/70">Berikut ringkasan aktivitas Perkap Pramuka hari ini.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @php
            $kartu = [
                ['label' => 'Total Jenis Perlengkapan', 'nilai' => $totalJenisPerlengkapan, 'icon' => 'box', 'warna' => 'accent'],
                ['label' => 'Sedang Dipinjam', 'nilai' => $sedangDipinjam, 'icon' => 'clipboard', 'warna' => 'warning'],
                ['label' => 'Pengajuan Pending', 'nilai' => $pengajuanPending, 'icon' => 'bell', 'warna' => 'danger'],
                ['label' => 'Total Dikembalikan', 'nilai' => $totalDikembalikan, 'icon' => 'check-circle', 'warna' => 'success'],
            ];
        @endphp
        @foreach($kartu as $k)
            <div class="animate-in" style="--delay: {{ $loop->index * 80 }}ms">
            <div @class([
                'relative overflow-hidden rounded-2xl border-t-4 border-secondary/5 p-4 shadow-sm dark:border-white/10',
                'wash-accent border-t-accent' => $k['warna'] === 'accent',
                'wash-warning border-t-warning' => $k['warna'] === 'warning',
                'wash-danger border-t-danger' => $k['warna'] === 'danger',
                'wash-success border-t-success' => $k['warna'] === 'success',
            ])>
                <div class="flex items-center justify-between">
                    <p class="text-sm text-secondary/60 dark:text-white/50">{{ $k['label'] }}</p>
                    <div @class([
                        'flex h-9 w-9 items-center justify-center rounded-xl text-white shadow-sm',
                        'bg-gradient-to-br from-accent to-violet-600 shadow-accent/30' => $k['warna'] === 'accent',
                        'bg-gradient-to-br from-warning to-orange-500 shadow-warning/30' => $k['warna'] === 'warning',
                        'bg-gradient-to-br from-danger to-red-600 shadow-danger/30' => $k['warna'] === 'danger',
                        'bg-gradient-to-br from-success to-emerald-600 shadow-success/30' => $k['warna'] === 'success',
                    ])>
                        <x-icon :name="$k['icon']" class="h-4.5 w-4.5"/>
                    </div>
                </div>
                <p class="mt-3 text-2xl font-bold"><x-count-up :angka="$k['nilai']"/></p>
            </div>
            </div>
        @endforeach
    </div>

    {{-- Anggota: ringkasan akun peminjam. Bila ada akun yang sudah mencapai batas penipuan, kartu ini jadi peringatan. --}}
    <div class="animate-in mt-6 rounded-2xl border bg-white p-4 shadow-sm dark:bg-white/[0.03] {{ $anggota['tinjau'] > 0 ? 'border-warning/40' : 'border-secondary/5 dark:border-white/10' }}" style="--delay: 320ms">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-rose to-pink-600 text-white shadow-sm shadow-rose/30">
                    <x-icon name="users" class="h-4.5 w-4.5"/>
                </span>
                <div>
                    <h2 class="text-sm font-semibold">Anggota</h2>
                    @if($anggota['tinjau'] > 0)
                        <p class="text-xs text-warning"><span class="font-semibold">{{ $anggota['tinjau'] }} akun</span> tercatat menipu {{ $anggota['batas'] }} kali atau lebih. Tinjau untuk diblokir.</p>
                    @else
                        <p class="text-xs text-secondary/50 dark:text-white/40">Tidak ada akun yang perlu ditinjau.</p>
                    @endif
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-x-6 gap-y-3">
                <a href="{{ route('admin.pengguna.index', ['status' => 'aktif']) }}" class="text-center transition hover:opacity-80">
                    <p class="text-lg font-bold text-success"><x-count-up :angka="$anggota['aktif']"/></p>
                    <p class="text-[11px] text-secondary/50 dark:text-white/40">Aktif</p>
                </a>
                <a href="{{ route('admin.pengguna.index', ['status' => 'diblokir']) }}" class="text-center transition hover:opacity-80">
                    <p class="text-lg font-bold text-danger"><x-count-up :angka="$anggota['diblokir']"/></p>
                    <p class="text-[11px] text-secondary/50 dark:text-white/40">Diblokir</p>
                </a>
                <a href="{{ route('admin.pengguna.index', ['status' => 'tinjau']) }}" class="text-center transition hover:opacity-80">
                    <p @class(['text-lg font-bold', 'text-warning' => $anggota['tinjau'] > 0, 'text-secondary/40 dark:text-white/30' => $anggota['tinjau'] === 0])><x-count-up :angka="$anggota['tinjau']"/></p>
                    <p class="text-[11px] text-secondary/50 dark:text-white/40">Perlu ditinjau</p>
                </a>
                <a href="{{ route('admin.pengguna.index', $anggota['tinjau'] > 0 ? ['status' => 'tinjau'] : []) }}" class="{{ $anggota['tinjau'] > 0 ? 'btn-primary' : 'btn-outline' }} text-xs">
                    {{ $anggota['tinjau'] > 0 ? 'Tinjau sekarang' : 'Kelola pengguna' }}
                </a>
            </div>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Stok per kategori --}}
        <div class="card-base lg:col-span-2 dark:border-white/10 dark:bg-white/[0.03]">
            <h2 class="text-sm font-semibold">Stok per Kategori</h2>
            <p class="text-xs text-secondary/50 dark:text-white/40">Jumlah total unit alat berdasarkan kategori</p>
            <div class="mt-4 h-72">
                <canvas id="chartKategori"></canvas>
            </div>
        </div>

        {{-- Status pengajuan — doughnut chart berwarna --}}
        <div class="card-base dark:border-white/10 dark:bg-white/[0.03]">
            <h2 class="text-sm font-semibold">Status Pengajuan</h2>
            <p class="text-xs text-secondary/50 dark:text-white/40">Ringkasan seluruh waktu</p>
            <div class="relative mx-auto mt-4 h-44 w-44">
                <canvas id="chartStatus"></canvas>
                <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                    <span class="text-2xl font-bold">{{ array_sum($statusPengajuan) }}</span>
                    <span class="text-[11px] text-secondary/50 dark:text-white/40">Total</span>
                </div>
            </div>
            <div class="mt-4 space-y-2">
                @php
                    $statusMap = [
                        'pending' => ['label' => 'Menunggu', 'warna' => 'warning'],
                        'approved' => ['label' => 'Disetujui', 'warna' => 'success'],
                        'returned' => ['label' => 'Selesai', 'warna' => 'accent'],
                        'rejected' => ['label' => 'Ditolak', 'warna' => 'danger'],
                    ];
                @endphp
                @foreach($statusMap as $key => $s)
                    <div class="flex items-center justify-between text-sm">
                        <span class="flex items-center gap-2">
                            <span @class([
                                'h-2 w-2 rounded-full',
                                'bg-warning' => $s['warna'] === 'warning',
                                'bg-success' => $s['warna'] === 'success',
                                'bg-accent' => $s['warna'] === 'accent',
                                'bg-danger' => $s['warna'] === 'danger',
                            ])></span>
                            <span class="text-secondary/70 dark:text-white/50">{{ $s['label'] }}</span>
                        </span>
                        <span class="font-semibold"><x-count-up :angka="$statusPengajuan[$key]"/></span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Pengajuan terbaru --}}
        <div class="card-base dark:border-white/10 dark:bg-white/[0.03]">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold">Pengajuan Terbaru</h2>
                <a href="{{ route('admin.peminjaman.index') }}" class="text-xs font-medium text-accent hover:underline">Lihat semua</a>
            </div>
            <div class="mt-4 space-y-1">
                @forelse($pengajuanTerbaru as $p)
                    <a href="{{ route('admin.peminjaman.show', $p) }}" class="flex items-center justify-between rounded-xl px-3 py-2.5 transition hover:bg-surface dark:hover:bg-white/5">
                        <span>
                            <span class="block text-sm font-medium">{{ $p->kode_pengajuan }}</span>
                            <span class="block text-xs text-secondary/50 dark:text-white/40">{{ $p->user->nama }}</span>
                        </span>
                        <span class="badge-{{ $p->warna_status }}">{{ $p->label_status }}</span>
                    </a>
                @empty
                    <p class="py-6 text-center text-sm text-secondary/40">Belum ada pengajuan.</p>
                @endforelse
            </div>
        </div>

        {{-- Stok rendah --}}
        <div class="card-base dark:border-white/10 dark:bg-white/[0.03]">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-semibold">Stok Menipis</h2>
                <a href="{{ route('admin.perlengkapan.index') }}" class="text-xs font-medium text-accent hover:underline">Kelola alat</a>
            </div>
            <div class="mt-4 space-y-1">
                @forelse($stokRendah as $alat)
                    <div class="flex items-center justify-between rounded-xl px-3 py-2.5">
                        <span>
                            <span class="block text-sm font-medium">{{ $alat->nama_alat }}</span>
                            <span class="block text-xs text-secondary/50 dark:text-white/40">{{ $alat->kategori }}</span>
                        </span>
                        <span class="badge-danger">{{ $alat->stok_tersedia }} {{ $alat->satuan }}</span>
                    </div>
                @empty
                    <p class="py-6 text-center text-sm text-secondary/40">Semua stok dalam kondisi aman.</p>
                @endforelse
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js"></script>
    <script>
        // Chart.js dimuat dari CDN. Bila tidak terjangkau (offline / diblokir), jangan biarkan
        // skrip error: tampilkan keterangan, angka-angka di kartu lain tetap berfungsi.
        if (typeof Chart === 'undefined') {
            const catatan = document.createElement('p');
            catatan.className = 'flex h-full items-center justify-center text-center text-xs text-secondary/50 dark:text-white/40';
            catatan.textContent = 'Grafik belum bisa dimuat. Periksa koneksi internet, lalu muat ulang halaman.';
            document.getElementById('chartKategori')?.replaceWith(catatan);
            document.getElementById('chartStatus')?.classList.add('hidden');
        } else {
        const ctx = document.getElementById('chartKategori');
        const gradient = ctx.getContext('2d').createLinearGradient(0, 0, 0, 280);
        gradient.addColorStop(0, '#8B5CF6');
        gradient.addColorStop(1, '#3B82F6');

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json($stokPerKategori->pluck('kategori')),
                datasets: [{
                    label: 'Total Stok',
                    data: @json($stokPerKategori->pluck('total')),
                    backgroundColor: gradient,
                    borderRadius: 10,
                    maxBarThickness: 52,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, grid: { color: 'rgba(17,17,17,0.06)' } }, x: { grid: { display: false } } },
            },
        });

        new Chart(document.getElementById('chartStatus'), {
            type: 'doughnut',
            data: {
                labels: ['Menunggu', 'Disetujui', 'Selesai', 'Ditolak'],
                datasets: [{
                    data: [
                        {{ $statusPengajuan['pending'] }},
                        {{ $statusPengajuan['approved'] }},
                        {{ $statusPengajuan['returned'] }},
                        {{ $statusPengajuan['rejected'] }},
                    ],
                    backgroundColor: ['#F59E0B', '#10B981', '#8B5CF6', '#EF4444'],
                    borderWidth: 3,
                    borderColor: '#fff',
                    hoverOffset: 6,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '72%',
                plugins: { legend: { display: false } },
            },
        });
        }
    </script>
@endpush
