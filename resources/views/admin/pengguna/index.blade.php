@extends('layouts.admin', ['title' => 'Pengguna'])

@section('content')
@php
    // Dipakai bersama oleh tabel (desktop) dan kartu (HP/tablet) supaya isinya selalu sama.
    $muatanBlokir = fn ($u) => [
        'nama' => $u->nama,
        'aksi' => route('admin.pengguna.blokir', $u),
        'menunggu' => (int) $u->menunggu_count,
        'saran' => $u->perluDitinjau() ? "Tercatat melakukan penipuan peminjaman {$u->penipuan_count} kali." : '',
    ];
    $pesanKosong = $cari !== '' ? "Tidak ada pengguna yang cocok dengan \"{$cari}\"." : 'Belum ada pengguna pada kelompok ini.';
@endphp
<div x-data="{ blokir: null }">

    @if($statistik['tinjau'] > 0 && $status !== 'tinjau')
        <div class="mb-5 flex flex-col gap-3 rounded-2xl border border-warning/30 bg-warning/10 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3">
                <x-icon name="flag" class="mt-0.5 h-5 w-5 flex-shrink-0 text-warning"/>
                <p class="text-sm"><span class="font-semibold">{{ $statistik['tinjau'] }} akun</span> sudah tercatat menipu {{ $batas }} kali atau lebih dan layak ditinjau untuk diblokir.</p>
            </div>
            <a href="{{ route('admin.pengguna.index', ['status' => 'tinjau']) }}" class="btn-outline whitespace-nowrap !border-warning/40 !text-warning hover:!bg-warning/10">Tinjau sekarang</a>
        </div>
    @endif

    {{-- Tab status + jumlah --}}
    <div class="mb-4 flex flex-wrap gap-2">
        @php
            $tab = [
                'semua' => ['Semua', $statistik['total'] + $statistik['dihapus']],
                'aktif' => ['Aktif', $statistik['aktif']],
                'tinjau' => ['Perlu ditinjau', $statistik['tinjau']],
                'diblokir' => ['Diblokir', $statistik['diblokir']],
                'dihapus' => ['Dihapus', $statistik['dihapus']],
            ];
        @endphp
        @foreach($tab as $kunci => [$label, $jumlah])
            <a href="{{ route('admin.pengguna.index', array_filter(['status' => $kunci === 'semua' ? null : $kunci, 'q' => $cari])) }}"
               class="inline-flex items-center gap-2 rounded-full px-3.5 py-1.5 text-sm font-medium transition-all {{ $status === $kunci ? 'bg-gradient-to-r from-accent to-violet-600 text-white shadow-sm shadow-accent/25' : 'bg-surface text-secondary/60 hover:bg-accent/10 hover:text-accent dark:bg-white/5 dark:text-white/50' }}">
                {{ $label }}
                <span @class([
                    'rounded-full px-1.5 py-0.5 text-[11px] font-semibold leading-none',
                    'bg-white/25 text-white' => $status === $kunci,
                    'bg-white text-secondary/60 dark:bg-white/10 dark:text-white/60' => $status !== $kunci,
                    '!bg-warning/20 !text-warning' => $kunci === 'tinjau' && $jumlah > 0 && $status !== $kunci,
                ])>{{ $jumlah }}</span>
            </a>
        @endforeach
    </div>

    {{-- Pencarian: nama, username, email, nomor HP, atau ID Anggota --}}
    <form method="GET" action="{{ route('admin.pengguna.index') }}" class="mb-4 flex flex-col gap-2 sm:flex-row">
        @if($status !== 'semua') <input type="hidden" name="status" value="{{ $status }}"> @endif
        <div class="relative flex-1">
            <x-icon name="search" class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-secondary/40"/>
            <input type="search" name="q" value="{{ $cari }}" placeholder="Cari nama, username, email, nomor HP, atau ID Anggota (mis. PRK-26-K7M2QH-9)" class="input-base pl-10">
        </div>
        <button type="submit" class="btn-primary sm:w-32">Cari</button>
        @if($cari !== '')
            <a href="{{ route('admin.pengguna.index', array_filter(['status' => $status === 'semua' ? null : $status])) }}" class="btn-outline sm:w-28">Reset</a>
        @endif
    </form>

    {{-- HP & tablet: satu kartu per anggota (tabel selebar ±1000px tidak muat di layar sempit) --}}
    <div class="space-y-3 lg:hidden">
        @forelse($pengguna as $u)
            @php $tinjau = $u->perluDitinjau(); @endphp
            <div @class([
                'card-base !p-4 dark:border-white/10 dark:bg-white/[0.03]',
                'opacity-70' => $u->trashed(),
                '!border-warning/40' => $tinjau && ! $u->trashed() && ! $u->isDiblokir(),
            ])>
                <div class="flex items-start gap-3">
                    <a href="{{ route('admin.pengguna.show', $u) }}" class="flex-shrink-0" tabindex="-1" aria-hidden="true">
                        <img src="{{ $u->avatar_url }}" alt="" class="h-11 w-11 rounded-full object-cover ring-2 ring-accent/10">
                    </a>
                    <div class="min-w-0 flex-1">
                        <a href="{{ route('admin.pengguna.show', $u) }}" class="block truncate font-medium hover:text-accent">{{ $u->nama }}</a>
                        <p class="truncate text-xs text-secondary/45 dark:text-white/35">{{ '@'.$u->username }} &middot; {{ \App\Support\Identitas::formatNoHp($u->no_hp) }}</p>
                        <div x-data="salinTeks(@js((string) $u->kode_anggota))" class="mt-1 flex items-center gap-1.5">
                            <span class="whitespace-nowrap font-mono text-xs font-semibold">{{ $u->kode_anggota }}</span>
                            <button type="button" @click="salin()" class="text-secondary/35 transition hover:text-accent" title="Salin ID" aria-label="Salin ID {{ $u->kode_anggota }}">
                                <x-icon name="copy" class="h-3.5 w-3.5" x-show="!tersalin"/>
                                <x-icon name="check" class="h-3.5 w-3.5 text-success" x-show="tersalin" x-cloak/>
                            </button>
                        </div>
                    </div>
                    <div class="flex flex-shrink-0 flex-col items-end gap-1">
                        @if($u->trashed())
                            <span class="badge-surface">Dihapus</span>
                        @elseif($u->isDiblokir())
                            <span class="badge-danger"><x-icon name="ban" class="h-3 w-3"/> Diblokir</span>
                        @else
                            <span class="badge-success">Aktif</span>
                        @endif
                        @if($tinjau)
                            <span class="badge-warning"><x-icon name="flag" class="h-3 w-3"/> Ditinjau</span>
                        @endif
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap items-center gap-1.5 text-xs">
                    @if($u->pelanggaran_count > 0)
                        <span class="badge-surface">{{ $u->pelanggaran_count }} catatan</span>
                        @if($u->penipuan_count > 0)
                            <span @class(['badge-danger', 'ring-1 ring-danger/40' => $tinjau])>Penipuan {{ $u->penipuan_count }}/{{ $batas }}</span>
                        @endif
                    @else
                        <span class="text-secondary/40 dark:text-white/30">Tanpa catatan pelanggaran</span>
                    @endif
                    <span class="ml-auto whitespace-nowrap text-secondary/45 dark:text-white/35">Daftar {{ $u->created_at->locale('id')->isoFormat('D MMM YYYY') }}</span>
                </div>

                <div class="mt-3 flex gap-2">
                    @if(! $u->trashed() && ! $u->isDiblokir())
                        <button type="button" @click="blokir = @js($muatanBlokir($u))" class="btn-outline flex-1 !px-2.5 !py-1.5 text-xs !text-danger">
                            <x-icon name="ban" class="h-3.5 w-3.5"/> Blokir
                        </button>
                    @elseif($u->isDiblokir() && ! $u->trashed())
                        <form method="POST" action="{{ route('admin.pengguna.buka-blokir', $u) }}" class="flex-1" onsubmit="return confirm('Buka blokir akun {{ addslashes($u->nama) }}?');">
                            @csrf
                            <button type="submit" class="btn-outline w-full !px-2.5 !py-1.5 text-xs !text-success"><x-icon name="unlock" class="h-3.5 w-3.5"/> Buka blokir</button>
                        </form>
                    @endif
                    <a href="{{ route('admin.pengguna.show', $u) }}" class="btn-outline flex-1 !px-2.5 !py-1.5 text-xs">Detail</a>
                </div>
            </div>
        @empty
            <div class="card-base py-10 text-center text-sm text-secondary/45 dark:border-white/10 dark:bg-white/[0.03] dark:text-white/35">{{ $pesanKosong }}</div>
        @endforelse
    </div>

    {{-- Desktop: tabel --}}
    <div class="card-base hidden overflow-x-auto !p-0 lg:block dark:border-white/10 dark:bg-white/[0.03]">
        <table class="w-full min-w-[1040px] text-left text-sm">
            <thead>
                <tr class="border-b border-secondary/5 text-xs text-secondary/50 dark:border-white/10 dark:text-white/40">
                    <th class="px-4 py-2.5 font-medium">Anggota</th>
                    <th class="px-4 py-2.5 font-medium">ID Anggota</th>
                    <th class="px-4 py-2.5 font-medium">Kontak</th>
                    <th class="px-4 py-2.5 font-medium">Pelanggaran</th>
                    <th class="px-4 py-2.5 font-medium">Status</th>
                    <th class="px-4 py-2.5 font-medium">Terdaftar</th>
                    <th class="px-4 py-2.5 text-right font-medium">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-secondary/5 dark:divide-white/5">
                @forelse($pengguna as $u)
                    @php $tinjau = $u->perluDitinjau(); @endphp
                    <tr @class(['hover:bg-surface/60 dark:hover:bg-white/[0.02]', 'opacity-60' => $u->trashed()])>
                        <td class="px-4 py-2.5">
                            <a href="{{ route('admin.pengguna.show', $u) }}" class="flex items-center gap-3">
                                <img src="{{ $u->avatar_url }}" alt="" class="h-9 w-9 flex-shrink-0 rounded-full object-cover ring-2 ring-accent/10">
                                <span class="min-w-0">
                                    <span class="block truncate font-medium hover:text-accent">{{ $u->nama }}</span>
                                    <span class="block truncate text-xs text-secondary/45 dark:text-white/35">{{ '@'.$u->username }}</span>
                                </span>
                            </a>
                        </td>
                        <td class="px-4 py-2.5">
                            <div x-data="salinTeks(@js((string) $u->kode_anggota))" class="flex items-center gap-1.5">
                                <span class="whitespace-nowrap font-mono text-xs font-semibold">{{ $u->kode_anggota }}</span>
                                <button type="button" @click="salin()" class="text-secondary/35 transition hover:text-accent" title="Salin ID" aria-label="Salin ID {{ $u->kode_anggota }}">
                                    <x-icon name="copy" class="h-3.5 w-3.5" x-show="!tersalin"/>
                                    <x-icon name="check" class="h-3.5 w-3.5 text-success" x-show="tersalin" x-cloak/>
                                </button>
                            </div>
                        </td>
                        <td class="px-4 py-2.5 text-secondary/70 dark:text-white/50">
                            <span class="block">{{ \App\Support\Identitas::formatNoHp($u->no_hp) }}</span>
                            <span class="block max-w-[190px] truncate text-xs text-secondary/45 dark:text-white/35">{{ $u->email }}</span>
                        </td>
                        <td class="px-4 py-2.5">
                            @if($u->pelanggaran_count > 0)
                                <div class="flex flex-wrap gap-1">
                                    <span class="badge-surface">{{ $u->pelanggaran_count }} catatan</span>
                                    @if($u->penipuan_count > 0)
                                        <span @class(['badge-danger', 'ring-1 ring-danger/40' => $tinjau])>Penipuan {{ $u->penipuan_count }}/{{ $batas }}</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-xs text-secondary/40 dark:text-white/30">Bersih</span>
                            @endif
                        </td>
                        <td class="px-4 py-2.5">
                            <div class="flex flex-wrap gap-1">
                                @if($u->trashed())
                                    <span class="badge-surface">Dihapus</span>
                                @elseif($u->isDiblokir())
                                    <span class="badge-danger"><x-icon name="ban" class="h-3 w-3"/> Diblokir</span>
                                @else
                                    <span class="badge-success">Aktif</span>
                                @endif
                                @if($tinjau)
                                    <span class="badge-warning"><x-icon name="flag" class="h-3 w-3"/> Perlu ditinjau</span>
                                @endif
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-4 py-2.5 text-secondary/70 dark:text-white/50">
                            {{ $u->created_at->locale('id')->isoFormat('D MMM YYYY') }}
                            <span class="block max-w-[150px] truncate text-[11px] text-secondary/35 dark:text-white/30" title="Kunci IP saat mendaftar">{{ $u->ip_daftar ?: 'IP tidak tercatat' }}</span>
                        </td>
                        <td class="px-4 py-2.5">
                            <div class="flex justify-end gap-2 whitespace-nowrap">
                                @if(! $u->trashed() && ! $u->isDiblokir())
                                    <button type="button"
                                            @click="blokir = @js($muatanBlokir($u))"
                                            class="btn-outline !px-2.5 !py-1 text-xs !text-danger" title="Blokir akun ini">
                                        <x-icon name="ban" class="h-3.5 w-3.5"/> Blokir
                                    </button>
                                @elseif($u->isDiblokir() && ! $u->trashed())
                                    <form method="POST" action="{{ route('admin.pengguna.buka-blokir', $u) }}" onsubmit="return confirm('Buka blokir akun {{ addslashes($u->nama) }}?');">
                                        @csrf
                                        <button type="submit" class="btn-outline !px-2.5 !py-1 text-xs !text-success"><x-icon name="unlock" class="h-3.5 w-3.5"/> Buka blokir</button>
                                    </form>
                                @endif
                                <a href="{{ route('admin.pengguna.show', $u) }}" class="btn-outline !px-3 !py-1 text-xs">Detail</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-secondary/45 dark:text-white/35">
                            {{ $pesanKosong }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $pengguna->links() }}</div>

    <x-modal-blokir/>
</div>
@endsection
