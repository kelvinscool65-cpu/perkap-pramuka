@extends('layouts.admin', ['title' => 'Detail Pengguna'])

@section('content')
@php
    use App\Models\Pelanggaran;
    use App\Support\Identitas;

    $dihapus = $pengguna->trashed();
    $diblokir = $pengguna->isDiblokir() && ! $dihapus;
    $tinjau = $pengguna->perluDitinjau();
    $penipuan = (int) $pengguna->penipuan_count;
    $semuaJenis = Pelanggaran::JENIS;
    $jenisAwal = old('jenis', in_array(request()->query('jenis'), array_keys($semuaJenis), true) ? request()->query('jenis') : 'penipuan');
    $hasilAkhir = ['good' => ['Kembali baik', 'success'], 'damaged' => ['Ada yang rusak', 'warning'], 'lost' => ['Ada yang hilang', 'danger']];
    $muatanBlokir = [
        'nama' => $pengguna->nama,
        'aksi' => route('admin.pengguna.blokir', $pengguna),
        'menunggu' => (int) $menunggu,
        'saran' => $tinjau ? "Tercatat melakukan penipuan peminjaman {$penipuan} kali." : '',
    ];
    $kontakWa = $pengguna->no_hp_normal ?: preg_replace('/^0/', '62', (string) $pengguna->no_hp);
    $seIp = $pengguna->ip_daftar
        ? \App\Models\User::withTrashed()->where('ip_daftar', $pengguna->ip_daftar)->where('id', '!=', $pengguna->id)->count()
        : 0;
@endphp

<div x-data="{ blokir: null, hapus: false }">

    {{-- Kepala: identitas singkat + tindakan akun --}}
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center">
        <div class="flex min-w-0 flex-1 items-center gap-3">
            <a href="{{ route('admin.pengguna.index') }}" class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-surface dark:bg-white/10" aria-label="Kembali ke daftar pengguna">
                <x-icon name="arrow-left" class="h-4 w-4"/>
            </a>
            <img src="{{ $pengguna->avatar_url }}" alt="" class="h-12 w-12 flex-shrink-0 rounded-full object-cover ring-2 ring-accent/20">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="truncate text-base font-semibold">{{ $pengguna->nama }}</h2>
                    @if($dihapus)
                        <span class="badge-surface">Dihapus</span>
                    @elseif($diblokir)
                        <span class="badge-danger"><x-icon name="ban" class="h-3 w-3"/> Diblokir</span>
                    @else
                        <span class="badge-success">Aktif</span>
                    @endif
                    @if($tinjau)
                        <span class="badge-warning"><x-icon name="flag" class="h-3 w-3"/> Perlu ditinjau</span>
                    @endif
                </div>
                <p class="truncate text-xs text-secondary/50 dark:text-white/40">{{ '@'.$pengguna->username }} &middot; <span class="font-mono">{{ $pengguna->kode_anggota }}</span></p>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            @if($dihapus)
                <form method="POST" action="{{ route('admin.pengguna.pulihkan', $pengguna) }}">
                    @csrf
                    <button type="submit" class="btn-success !px-3.5 !py-1.5 text-xs"><x-icon name="restore" class="h-3.5 w-3.5"/> Pulihkan akun</button>
                </form>
            @else
                @if($diblokir)
                    <form method="POST" action="{{ route('admin.pengguna.buka-blokir', $pengguna) }}" onsubmit="return confirm('Buka blokir akun {{ addslashes($pengguna->nama) }}? Pemilik akun bisa masuk kembali.');">
                        @csrf
                        <button type="submit" class="btn-success !px-3.5 !py-1.5 text-xs"><x-icon name="unlock" class="h-3.5 w-3.5"/> Buka blokir</button>
                    </form>
                @else
                    <button type="button" @click="blokir = @js($muatanBlokir)" class="btn-outline !border-danger/30 !px-3.5 !py-1.5 text-xs !text-danger hover:!bg-danger/5">
                        <x-icon name="ban" class="h-3.5 w-3.5"/> Blokir akun
                    </button>
                @endif

                @if($sedangDipinjam > 0)
                    <button type="button" disabled class="btn-outline !px-3.5 !py-1.5 text-xs" title="Masih ada {{ $sedangDipinjam }} peminjaman aktif. Proses pengembaliannya dulu.">
                        <x-icon name="trash" class="h-3.5 w-3.5"/> Hapus akun
                    </button>
                @else
                    <button type="button" @click="hapus = true" class="btn-outline !px-3.5 !py-1.5 text-xs hover:!border-danger/30 hover:!bg-danger/5 hover:!text-danger">
                        <x-icon name="trash" class="h-3.5 w-3.5"/> Hapus akun
                    </button>
                @endif
            @endif
        </div>
    </div>

    {{-- Alasan tombol "Hapus akun" nonaktif: harus terlihat (tooltip saja tidak muncul di layar sentuh) --}}
    @if(! $dihapus && $sedangDipinjam > 0)
        <div class="mb-6 flex items-start gap-3 rounded-2xl border border-info/25 bg-info/5 px-4 py-3 text-sm">
            <x-icon name="info" class="mt-0.5 h-5 w-5 flex-shrink-0 text-info"/>
            <p>Akun ini belum bisa dihapus karena <span class="font-semibold">masih ada {{ $sedangDipinjam }} peminjaman aktif</span> (barang belum dikembalikan). Proses pengembaliannya dulu di menu <a href="{{ route('admin.peminjaman.index') }}" class="font-semibold text-info hover:underline">Peminjaman</a> agar stok tidak ikut hilang. Memblokir akun tetap bisa dilakukan kapan saja.</p>
        </div>
    @endif

    {{-- Pita keadaan akun --}}
    @if($dihapus)
        <div class="mb-6 flex items-start gap-3 rounded-2xl border border-secondary/10 bg-surface px-4 py-3 text-sm dark:border-white/10 dark:bg-white/5">
            <x-icon name="info" class="mt-0.5 h-5 w-5 flex-shrink-0 text-secondary/50 dark:text-white/40"/>
            <p>Akun ini dihapus pada <span class="font-semibold">{{ $pengguna->deleted_at->locale('id')->isoFormat('D MMMM YYYY, HH:mm') }}</span>. Riwayat peminjamannya tetap tersimpan, dan ID Anggota, email, serta nomor HP-nya tetap terpesan sehingga tidak bisa dipakai mendaftar ulang. Pulihkan akun bila penghapusan ini keliru.</p>
        </div>
    @elseif($diblokir)
        <div class="mb-6 flex items-start gap-3 rounded-2xl border border-danger/25 bg-danger/5 p-4">
            <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-danger to-red-600 text-white shadow-sm shadow-danger/30"><x-icon name="ban" class="h-4 w-4"/></span>
            <div class="min-w-0 text-sm">
                <p class="font-semibold text-danger">Akun ini sedang diblokir</p>
                <p class="mt-1 break-words">{{ $pengguna->alasan_blokir }}</p>
                <p class="mt-1.5 text-xs text-secondary/55 dark:text-white/45">
                    Diblokir {{ $pengguna->diblokir_pada?->locale('id')->isoFormat('D MMMM YYYY, HH:mm') ?? '—' }}
                    oleh {{ $pengguna->diblokirOleh?->nama ?? 'petugas' }}. Pemilik akun melihat alasan ini saat mencoba masuk.
                </p>
            </div>
        </div>
    @elseif($tinjau)
        <div class="mb-6 flex flex-col gap-3 rounded-2xl border border-warning/30 bg-warning/10 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3">
                <x-icon name="flag" class="mt-0.5 h-5 w-5 flex-shrink-0 text-warning"/>
                <p class="text-sm">Tercatat melakukan penipuan peminjaman <span class="font-semibold">{{ $penipuan }} kali</span> (batas {{ $batas }}). Petugas berhak memblokir akun ini.</p>
            </div>
            <button type="button" @click="blokir = @js($muatanBlokir)" class="btn-danger whitespace-nowrap !px-3.5 !py-1.5 text-xs"><x-icon name="ban" class="h-3.5 w-3.5"/> Blokir sekarang</button>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- ===== Kolom kiri: pelanggaran + riwayat ===== --}}
        <div class="space-y-6 lg:col-span-2">

            <div class="card-base dark:border-white/10 dark:bg-white/[0.03]">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h3 class="text-sm font-semibold">Catatan pelanggaran</h3>
                    <span @class(['badge-danger' => $penipuan >= $batas, 'badge-warning' => $penipuan > 0 && $penipuan < $batas, 'badge-success' => $penipuan === 0])>
                        Penipuan {{ $penipuan }} dari {{ $batas }}
                    </span>
                </div>
                <div class="mt-3 flex gap-1.5" role="img" aria-label="Penipuan {{ $penipuan }} dari batas {{ $batas }}">
                    @for($i = 1; $i <= $batas; $i++)
                        <span @class(['h-2 flex-1 rounded-full', 'bg-gradient-to-r from-danger to-red-600' => $i <= $penipuan, 'bg-surface dark:bg-white/10' => $i > $penipuan])></span>
                    @endfor
                </div>
                <p class="mt-2 text-xs text-secondary/55 dark:text-white/45">Jenis lain (hilang, rusak, terlambat) tetap dicatat tetapi tidak dihitung ke batas penipuan.</p>

                <div class="mt-4 divide-y divide-secondary/5 dark:divide-white/5">
                    @forelse($pelanggaran as $p)
                        <div class="flex items-start justify-between gap-3 py-3 first:pt-0 last:pb-0">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="badge-{{ $p->warna_jenis }}">{{ $p->label_jenis }}</span>
                                    <span class="text-[11px] text-secondary/45 dark:text-white/35">{{ $p->created_at->locale('id')->isoFormat('D MMM YYYY, HH:mm') }}</span>
                                </div>
                                <p class="mt-1.5 break-words text-sm text-secondary/80 dark:text-white/70">{{ $p->keterangan }}</p>
                                <p class="mt-1 text-[11px] text-secondary/45 dark:text-white/35">
                                    Dicatat oleh {{ $p->pencatat?->nama ?? 'petugas' }}
                                    @if($p->pengajuan)
                                        &middot; terkait <a href="{{ route('admin.peminjaman.show', $p->pengajuan) }}" class="font-medium text-accent hover:underline">{{ $p->pengajuan->kode_pengajuan }}</a>
                                    @endif
                                </p>
                            </div>
                            <form method="POST" action="{{ route('admin.pelanggaran.destroy', $p) }}" class="flex-shrink-0"
                                  onsubmit="return confirm('Hapus catatan pelanggaran ini? Catatan yang dihapus tidak bisa dikembalikan.');">
                                @csrf @method('DELETE')
                                <button type="submit" class="flex h-7 w-7 items-center justify-center rounded-full text-secondary/35 transition hover:bg-danger/10 hover:text-danger" title="Hapus catatan" aria-label="Hapus catatan pelanggaran">
                                    <x-icon name="trash" class="h-3.5 w-3.5"/>
                                </button>
                            </form>
                        </div>
                    @empty
                        <p class="py-1 text-sm text-secondary/55 dark:text-white/45">Belum ada catatan pelanggaran untuk anggota ini.</p>
                    @endforelse
                </div>

                {{-- Form catat pelanggaran --}}
                <form id="catat" method="POST" action="{{ route('admin.pengguna.pelanggaran.store', $pengguna) }}"
                      x-data="{ jenis: @js($jenisAwal), bantuan: @js(collect($semuaJenis)->map(fn ($j) => $j[2])->all()) }"
                      @if($errors->hasAny(['jenis', 'keterangan', 'pengajuan_id'])) x-init="$nextTick(() => $el.scrollIntoView({ block: 'center' }))" @endif
                      class="mt-5 space-y-3 rounded-xl bg-surface p-4 dark:bg-white/5">
                    @csrf
                    <h4 class="text-xs font-semibold">Catat pelanggaran baru</h4>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label for="jenis" class="label-base">Jenis pelanggaran</label>
                            <select id="jenis" name="jenis" x-model="jenis" required class="input-base !bg-white dark:!bg-secondary">
                                @foreach($semuaJenis as $kode => $info)
                                    <option value="{{ $kode }}" @selected($kode === $jenisAwal)>{{ $info[0] }}</option>
                                @endforeach
                            </select>
                            @error('jenis') <p class="mt-1.5 text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="pengajuan_id" class="label-base">Terkait pengajuan (opsional)</label>
                            <select id="pengajuan_id" name="pengajuan_id" class="input-base !bg-white dark:!bg-secondary">
                                <option value="">Tidak terkait pengajuan tertentu</option>
                                @foreach($opsiPengajuan as $op)
                                    <option value="{{ $op->id }}" @selected((int) old('pengajuan_id', $pengajuanTerpilih) === $op->id)>
                                        {{ $op->kode_pengajuan }} &middot; {{ $op->tanggal_pinjam->translatedFormat('d M Y') }}
                                    </option>
                                @endforeach
                            </select>
                            @error('pengajuan_id') <p class="mt-1.5 text-xs text-danger">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <p class="text-[11px] text-secondary/55 dark:text-white/45" x-text="bantuan[jenis]"></p>

                    <div>
                        <label for="keterangan" class="label-base">Keterangan</label>
                        <textarea id="keterangan" name="keterangan" rows="2" required minlength="5" maxlength="500" class="input-base"
                                  placeholder="Apa yang terjadi? Tulis singkat dan faktual — pemilik akun akan menerimanya lewat notifikasi.">{{ old('keterangan') }}</textarea>
                        @error('keterangan') <p class="mt-1.5 text-xs text-danger">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex items-center justify-between gap-3">
                        <p class="text-[11px] text-secondary/45 dark:text-white/35">Pemilik akun diberi tahu otomatis.</p>
                        <button type="submit" class="btn-primary !px-4 !py-1.5 text-xs"><x-icon name="flag" class="h-3.5 w-3.5"/> Catat pelanggaran</button>
                    </div>
                </form>
            </div>

            {{-- Riwayat peminjaman --}}
            <div class="card-base dark:border-white/10 dark:bg-white/[0.03]">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold">Riwayat peminjaman</h3>
                    <span class="text-xs text-secondary/50 dark:text-white/40">{{ $pengguna->pengajuan_count }} pengajuan</span>
                </div>
                <div class="mt-3 divide-y divide-secondary/5 dark:divide-white/5">
                    @forelse($riwayat as $r)
                        @php
                            $barang = $r->details->map(fn ($d) => ($d->alat?->nama_alat ?? 'Barang dihapus').' ×'.$d->jumlah);
                            $ringkas = $barang->take(3)->implode(', ').($barang->count() > 3 ? ' +'.($barang->count() - 3).' lainnya' : '');
                            $akhir = $hasilAkhir[$r->status_akhir] ?? null;
                        @endphp
                        <div class="flex items-start justify-between gap-3 py-3 first:pt-0 last:pb-0">
                            <div class="min-w-0">
                                <a href="{{ route('admin.peminjaman.show', $r) }}" class="text-sm font-medium hover:text-accent">{{ $r->kode_pengajuan }}</a>
                                <p class="truncate text-xs text-secondary/60 dark:text-white/45">{{ $ringkas }}</p>
                                <p class="text-[11px] text-secondary/40 dark:text-white/30">{{ $r->tanggal_pinjam->translatedFormat('d M Y') }} – {{ $r->tanggal_kembali->translatedFormat('d M Y') }}</p>
                            </div>
                            <div class="flex flex-shrink-0 flex-col items-end gap-1">
                                <span class="badge-{{ $r->warna_status }}">{{ $r->label_status }}</span>
                                @if($akhir && $r->status_akhir !== 'good')
                                    <span class="badge-{{ $akhir[1] }}">{{ $akhir[0] }}</span>
                                    @unless($dihapus)
                                        <a href="{{ route('admin.pengguna.show', ['pengguna' => $pengguna, 'pengajuan' => $r->id, 'jenis' => $r->status_akhir === 'lost' ? 'kehilangan' : 'kerusakan']) }}#catat"
                                           class="text-[11px] font-medium text-accent hover:underline">Catat pelanggaran</a>
                                    @endunless
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="py-1 text-sm text-secondary/55 dark:text-white/45">Anggota ini belum pernah mengajukan peminjaman.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- ===== Kolom kanan: kartu + kontak + pendaftaran ===== --}}
        <div class="space-y-6">
            <x-kartu-anggota :user="$pengguna" compact/>

            <div class="card-base dark:border-white/10 dark:bg-white/[0.03]">
                <h3 class="text-sm font-semibold">Kontak</h3>
                <dl class="mt-3 space-y-2.5 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-secondary/50 dark:text-white/40">WhatsApp</dt><dd class="font-medium">{{ Identitas::formatNoHp($pengguna->no_hp) }}</dd></div>
                    <div class="flex justify-between gap-3"><dt class="flex-shrink-0 text-secondary/50 dark:text-white/40">Email</dt><dd class="min-w-0 truncate font-medium" title="{{ $pengguna->email }}">{{ $pengguna->email }}</dd></div>
                </dl>
                <div class="mt-4 flex flex-col gap-2">
                    <a href="https://wa.me/{{ $kontakWa }}" target="_blank" rel="noopener" class="btn-outline text-xs !border-[#25D366]/30 !text-[#25D366] hover:!bg-[#25D366]/10">
                        <x-icon name="whatsapp" class="h-3.5 w-3.5"/> Hubungi via WhatsApp
                    </a>
                    <a href="{{ route('verifikasi.cek', $pengguna->kode_anggota) }}" target="_blank" rel="noopener" class="btn-outline text-xs">
                        <x-icon name="shield-check" class="h-3.5 w-3.5"/> Lihat halaman verifikasi
                    </a>
                </div>
            </div>

            <div class="card-base dark:border-white/10 dark:bg-white/[0.03]">
                <h3 class="text-sm font-semibold">Pendaftaran & aktivitas</h3>
                <dl class="mt-3 space-y-2.5 text-sm">
                    <div class="flex justify-between gap-3"><dt class="text-secondary/50 dark:text-white/40">Terdaftar</dt><dd class="text-right font-medium">{{ $pengguna->created_at->locale('id')->isoFormat('D MMM YYYY, HH:mm') }}</dd></div>
                    <div class="flex justify-between gap-3">
                        <dt class="flex-shrink-0 text-secondary/50 dark:text-white/40">IP saat daftar</dt>
                        <dd class="min-w-0 truncate text-right font-mono text-xs font-medium" title="{{ $pengguna->ip_daftar }}">{{ $pengguna->ip_daftar ?: 'tidak tercatat' }}</dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-secondary/50 dark:text-white/40">Tata tertib</dt>
                        <dd class="text-right font-medium">
                            @if($pengguna->syarat_disetujui_pada)
                                <span class="text-success">Disetujui</span> {{ $pengguna->syarat_disetujui_pada->locale('id')->isoFormat('D MMM YYYY') }}
                            @else
                                <span class="text-secondary/50 dark:text-white/40">Akun sebelum fitur ini</span>
                            @endif
                        </dd>
                    </div>
                    @if($pengguna->diblokir_pada && ! $diblokir)
                        <div class="flex justify-between gap-3"><dt class="text-secondary/50 dark:text-white/40">Blokir terakhir</dt><dd class="text-right font-medium">{{ $pengguna->diblokir_pada->locale('id')->isoFormat('D MMM YYYY') }}</dd></div>
                    @endif
                    <div class="flex justify-between gap-3 border-t border-secondary/5 pt-2.5 dark:border-white/10"><dt class="text-secondary/50 dark:text-white/40">Sedang dipinjam</dt><dd class="font-medium">{{ $sedangDipinjam }} pengajuan</dd></div>
                    <div class="flex justify-between gap-3"><dt class="text-secondary/50 dark:text-white/40">Menunggu persetujuan</dt><dd class="font-medium">{{ $menunggu }} pengajuan</dd></div>
                </dl>

                @if($seIp > 0)
                    <a href="{{ route('admin.pengguna.index', ['q' => $pengguna->ip_daftar]) }}" class="mt-3 flex items-start gap-2 rounded-xl bg-info/10 px-3 py-2 text-xs text-info transition hover:bg-info/15">
                        <x-icon name="info" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0"/>
                        <span>{{ $seIp }} akun lain mendaftar dari IP yang sama. Wi-Fi sekolah memakai satu IP bersama, jadi ini petunjuk, bukan bukti.</span>
                    </a>
                @endif
            </div>
        </div>
    </div>

    <x-modal-blokir/>

    {{-- Konfirmasi hapus akun (di-teleport ke <body>, sama seperti modal blokir) --}}
    @unless($dihapus)
        <template x-teleport="body">
            <div x-show="hapus" x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
                 x-transition.opacity
                 role="dialog" aria-modal="true" aria-label="Hapus akun"
                 @click.self="hapus = false"
                 @keydown.escape.window="hapus = false">
                <div x-show="hapus" x-transition class="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-2xl bg-white p-5 text-secondary shadow-xl dark:bg-secondary dark:text-white">
                    <div class="mb-4 flex items-start justify-between gap-3">
                        <div class="flex items-start gap-3">
                            <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-danger to-red-600 text-white shadow-sm shadow-danger/30"><x-icon name="trash" class="h-5 w-5"/></span>
                            <div>
                                <h3 class="text-sm font-bold">Hapus akun {{ $pengguna->nama }}?</h3>
                                <p class="mt-0.5 text-xs text-secondary/55 dark:text-white/45">Pemilik akun tidak akan bisa masuk lagi.</p>
                            </div>
                        </div>
                        <button type="button" @click="hapus = false" aria-label="Tutup" class="text-secondary/40 hover:text-secondary dark:hover:text-white"><x-icon name="x" class="h-4 w-4"/></button>
                    </div>

                    <ul class="space-y-1.5 rounded-xl bg-surface p-3 text-xs text-secondary/70 dark:bg-white/5 dark:text-white/55">
                        <li class="flex gap-2"><x-icon name="check" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0 text-danger"/> Pengajuan yang menunggu ({{ $menunggu }}) ditolak otomatis.</li>
                        <li class="flex gap-2"><x-icon name="check" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0 text-success"/> Riwayat peminjaman dan laporan tetap utuh.</li>
                        <li class="flex gap-2"><x-icon name="check" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0 text-success"/> ID Anggota, email, dan nomor HP tetap terpesan: tidak bisa dipakai mendaftar ulang.</li>
                        <li class="flex gap-2"><x-icon name="check" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0 text-success"/> Akun bisa dipulihkan kapan saja dari tab Dihapus.</li>
                    </ul>

                    <form method="POST" action="{{ route('admin.pengguna.destroy', $pengguna) }}" class="mt-4 flex gap-2">
                        @csrf @method('DELETE')
                        <button type="button" @click="hapus = false" class="btn-outline flex-1">Batal</button>
                        <button type="submit" class="btn-danger flex-1"><x-icon name="trash" class="h-3.5 w-3.5"/> Hapus akun</button>
                    </form>
                </div>
            </div>
        </template>
    @endunless
</div>
@endsection
