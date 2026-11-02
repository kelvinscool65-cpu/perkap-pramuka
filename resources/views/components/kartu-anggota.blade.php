@props(['user', 'salin' => true, 'baru' => false, 'compact' => false])
{{-- Kartu Anggota.
     Gagasan visual: lencana Pramuka. Tepi bergaris putus-putus meniru jahitan badge, dan bagian acak
     ID diubah menjadi SANDI MORSE yang benar-benar menyandikan karakter ID tersebut — jadi tiap kartu
     tampak berbeda karena isinya memang berbeda. ID dipecah per bagian dengan keterangan supaya
     jelas apa arti tiap bagian (awalan, angkatan, nomor unik, karakter pemeriksa). --}}
@php
    $bagian = \App\Support\KodeAnggota::urai($user->kode_anggota);
    $nonaktif = $user->isDiblokir() || $user->trashed();
    $sejak = $user->created_at?->copy()->locale('id')->isoFormat('MMMM YYYY');
    // Huruf ID mengecil di layar sempit; `compact` memaksanya kecil di kolom sempit (mis. halaman detail admin).
    $hurufId = $compact ? 'text-xl' : 'text-xl sm:text-[28px]';
    $lingkarCek = $compact ? 'h-6 w-6 text-sm' : 'h-6 w-6 text-sm sm:h-8 sm:w-8 sm:text-lg';
@endphp

<div {{ $attributes->class([
        'relative isolate overflow-hidden rounded-[28px] bg-gradient-to-br from-secondary via-[#1d1340] to-[#3b1d8f] text-white shadow-xl shadow-accent/25',
        'p-6' => ! $compact,
        'p-5' => $compact,
        'grayscale' => $nonaktif,
        'kilau' => $baru,
     ]) }}>
    <div class="pointer-events-none absolute -right-16 -top-20 -z-10 h-56 w-56 rounded-full bg-accent/35 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-24 -left-12 -z-10 h-56 w-56 rounded-full bg-info/25 blur-3xl"></div>
    {{-- jahitan tepi badge --}}
    <div class="pointer-events-none absolute inset-2 rounded-[22px] border border-dashed border-white/25"></div>

    <div class="relative flex flex-col gap-6">
        <div class="flex items-start justify-between gap-3">
            <div class="flex min-w-0 items-center gap-2.5">
                <div class="flex h-9 w-14 flex-shrink-0 items-center justify-center rounded-xl bg-white p-1 shadow-sm">
                    <img src="{{ asset('images/logo/gudep-gabungan.png') }}" alt="" class="h-full w-full object-contain">
                </div>
                <div class="min-w-0 leading-tight">
                    <p class="truncate text-sm font-bold">Kartu Anggota</p>
                    <p class="truncate text-[11px] text-white/55">Perkap Pramuka · SMKN 5 Surakarta</p>
                </div>
            </div>

            @if($user->trashed())
                <span class="inline-flex flex-shrink-0 items-center gap-1.5 rounded-full bg-white/15 px-2.5 py-1 text-[11px] font-semibold text-white/80">Dihapus</span>
            @elseif($user->isDiblokir())
                <span class="inline-flex flex-shrink-0 items-center gap-1.5 rounded-full bg-red-400/20 px-2.5 py-1 text-[11px] font-semibold text-red-200 ring-1 ring-red-300/25">
                    <x-icon name="ban" class="h-3 w-3"/> Diblokir
                </span>
            @else
                <span class="inline-flex flex-shrink-0 items-center gap-1.5 rounded-full bg-emerald-400/15 px-2.5 py-1 text-[11px] font-semibold text-emerald-300 ring-1 ring-emerald-300/20">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span> Aktif
                </span>
            @endif
        </div>

        <div>
            <div class="flex items-center justify-between gap-2">
                <p class="text-xs text-white/55">ID Anggota</p>
                <span class="inline-flex items-center gap-1 rounded-full bg-white/10 px-2 py-0.5 text-[10px] font-medium text-white/70">
                    <x-icon name="lock" class="h-3 w-3"/> Permanen
                </span>
            </div>

            <span class="sr-only">{{ $user->kode_anggota }}</span>
            @if($bagian)
                <div class="mt-2.5 flex flex-wrap items-start gap-x-2 gap-y-3 font-mono" aria-hidden="true">
                    @foreach([[$bagian['awalan'], 'Perkap'], [$bagian['tahun'], 'Angkatan'], [$bagian['acak'], 'Nomor unik']] as [$isi, $ket])
                        <div class="flex items-start gap-x-2">
                            <div>
                                <p class="{{ $hurufId }} font-bold leading-none tracking-[0.06em]">{{ $isi }}</p>
                                <p class="mt-1.5 font-sans text-[10px] text-white/45">{{ $ket }}</p>
                            </div>
                            <span class="{{ $hurufId }} leading-none text-white/30">-</span>
                        </div>
                    @endforeach
                    <div>
                        <span class="flex {{ $lingkarCek }} items-center justify-center rounded-full bg-accent font-bold leading-none shadow-sm shadow-accent/50">{{ $bagian['pemeriksa'] }}</span>
                        <p class="mt-1.5 text-center font-sans text-[10px] text-white/45">Cek</p>
                    </div>
                </div>

                <x-sandi-morse :teks="$bagian['acak']" class="mt-4 text-violet-300"/>
            @else
                <p class="mt-2.5 break-all font-mono text-xl font-bold">{{ $user->kode_anggota ?: '—' }}</p>
            @endif
        </div>

        <div class="flex items-end justify-between gap-3">
            <div class="min-w-0">
                <p class="truncate text-base font-semibold">{{ $user->nama }}</p>
                <p class="text-xs text-white/55">
                    {{ $user->role_label }}@if($sejak) · sejak {{ $sejak }}@endif
                </p>
            </div>

            @if($salin && $user->kode_anggota)
                <div x-data="salinTeks(@js($user->kode_anggota))" class="flex-shrink-0">
                    <button type="button" @click="salin()"
                            class="inline-flex items-center gap-1.5 rounded-full bg-white/12 px-3 py-1.5 text-xs font-semibold text-white ring-1 ring-white/15 transition hover:bg-white/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/60">
                        <x-icon name="copy" class="h-3.5 w-3.5" x-show="!tersalin"/>
                        <x-icon name="check" class="h-3.5 w-3.5 text-emerald-300" x-show="tersalin" x-cloak/>
                        <span x-text="tersalin ? 'Tersalin' : 'Salin ID'">Salin ID</span>
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>
