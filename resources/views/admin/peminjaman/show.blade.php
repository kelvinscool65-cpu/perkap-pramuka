@extends('layouts.admin', ['title' => 'Detail Peminjaman'])

@section('content')
    @php
        // Akun peminjam bisa saja sudah diblokir/dihapus (relasi user memakai withTrashed agar riwayat tetap utuh).
        $peminjam = $pengajuan->user;
        $peminjamTidakAktif = $peminjam->isDiblokir() || $peminjam->trashed();
        $penipuanPeminjam = $peminjam->isPeminjam() ? $peminjam->jumlahPelanggaran('penipuan') : 0;
        $batasPenipuan = (int) config('perkap.batas_penipuan', 3);
    @endphp

    <div class="mb-6 flex items-center gap-3">
        <a href="{{ route('admin.peminjaman.index') }}" class="flex h-9 w-9 items-center justify-center rounded-full bg-surface dark:bg-white/10">
            <x-icon name="arrow-left" class="h-4 w-4"/>
        </a>
        <div class="flex-1">
            <div class="flex items-center gap-2">
                <h2 class="text-base font-semibold">{{ $pengajuan->kode_pengajuan }}</h2>
                <span class="badge-{{ $pengajuan->warna_status }}">{{ $pengajuan->label_status }}</span>
            </div>
            <p class="text-xs text-secondary/50 dark:text-white/40">Diajukan {{ $pengajuan->created_at->translatedFormat('d M Y, H:i') }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            {{-- Daftar alat --}}
            <div class="card-base dark:border-white/10 dark:bg-white/[0.03]">
                <h3 class="text-sm font-semibold">Alat yang Diajukan</h3>
                <div class="mt-3 divide-y divide-secondary/5 dark:divide-white/5">
                    @foreach($pengajuan->details as $detail)
                        <div class="flex items-center justify-between py-3">
                            <div>
                                <p class="text-sm font-medium">{{ $detail->alat->nama_alat }}</p>
                                <p class="text-xs text-secondary/40 dark:text-white/30">{{ $detail->alat->kode_alat }} · {{ $detail->alat->harga_sewa_format }} × {{ $detail->jumlah }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-semibold">{{ $detail->subtotal_format }}</p>
                                @if($detail->kondisi_saat_kembali)
                                    <span class="badge-{{ $detail->kondisi_saat_kembali === 'good' ? 'success' : 'danger' }}">
                                        {{ ['good' => 'Kembali Baik', 'damaged' => 'Rusak', 'lost' => 'Hilang'][$detail->kondisi_saat_kembali] }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-2 flex items-center justify-between rounded-xl bg-gradient-to-r from-accent/10 to-violet-500/10 px-3.5 py-2.5">
                    <span class="text-sm font-semibold">Total Biaya Sewa</span>
                    <span class="text-base font-bold text-accent">{{ $pengajuan->total_biaya_format }}</span>
                </div>
            </div>

            {{-- Aksi: Approve / Reject (khusus pending) --}}
            @if($pengajuan->status_pengajuan === 'pending')
                <div class="card-base dark:border-white/10 dark:bg-white/[0.03]" x-data="{ mode: null }">
                    <h3 class="text-sm font-semibold">Proses Pengajuan</h3>

                    @if($peminjamTidakAktif)
                        <div class="mt-3 flex items-start gap-2 rounded-xl bg-danger/10 px-3 py-2 text-xs text-danger">
                            <x-icon name="ban" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0"/>
                            <p>Akun peminjam {{ $peminjam->trashed() ? 'sudah dihapus' : 'sedang diblokir' }}, jadi pengajuan ini tidak bisa disetujui. Anda tetap bisa menolaknya.</p>
                        </div>
                    @endif

                    <div x-show="mode !== 'tolak'" class="mt-4 flex gap-3">
                        <form method="POST" action="{{ route('admin.peminjaman.approve', $pengajuan) }}" class="flex-1">
                            @csrf @method('PATCH')
                            <input type="hidden" name="catatan_admin" value="Disetujui.">
                            <button type="submit" class="btn-success w-full" @disabled($peminjamTidakAktif) onclick="return confirm('Setujui pengajuan ini? Stok akan otomatis dikurangi.');">
                                <x-icon name="check-circle" class="h-4 w-4"/> Setujui Pengajuan
                            </button>
                        </form>
                        <button type="button" @click="mode = 'tolak'" class="btn-danger flex-1">
                            <x-icon name="x" class="h-4 w-4"/> Tolak Pengajuan
                        </button>
                    </div>

                    <form x-show="mode === 'tolak'" x-cloak method="POST" action="{{ route('admin.peminjaman.reject', $pengajuan) }}" class="mt-4 space-y-3">
                        @csrf @method('PATCH')
                        <div>
                            <label class="label-base">Alasan penolakan</label>
                            <textarea name="catatan_admin" rows="3" required class="input-base" placeholder="Jelaskan alasan penolakan untuk peminjam..."></textarea>
                        </div>
                        <div class="flex gap-3">
                            <button type="button" @click="mode = null" class="btn-outline flex-1">Batal</button>
                            <button type="submit" class="btn-danger flex-1">Kirim Penolakan</button>
                        </div>
                    </form>
                </div>
            @endif

            {{-- Form Pengembalian (khusus approved) --}}
            @if($pengajuan->status_pengajuan === 'approved')
                <form method="POST" action="{{ route('admin.peminjaman.kembalikan', $pengajuan) }}" class="card-base space-y-4 dark:border-white/10 dark:bg-white/[0.03]">
                    @csrf @method('PATCH')
                    <div>
                        <h3 class="text-sm font-semibold">Proses Pengembalian</h3>
                        <p class="text-xs text-secondary/50 dark:text-white/40">Periksa kondisi setiap item saat dikembalikan.</p>
                    </div>

                    <div class="space-y-3">
                        @foreach($pengajuan->details as $detail)
                            <div class="flex flex-col gap-2 rounded-xl bg-surface p-3 dark:bg-white/5 sm:flex-row sm:items-center sm:justify-between">
                                <input type="hidden" name="items[{{ $loop->index }}][detail_id]" value="{{ $detail->id }}">
                                <p class="text-sm font-medium">{{ $detail->alat->nama_alat }} <span class="font-normal text-secondary/50">× {{ $detail->jumlah }}</span></p>
                                <select name="items[{{ $loop->index }}][kondisi]" required class="input-base !bg-white !py-1.5 text-xs sm:w-40 dark:!bg-secondary">
                                    <option value="good">Kembali Baik</option>
                                    <option value="damaged">Rusak</option>
                                    <option value="lost">Hilang</option>
                                </select>
                            </div>
                        @endforeach
                    </div>

                    <div>
                        <label class="label-base">Catatan Pengembalian (opsional)</label>
                        <textarea name="catatan_pengembalian" rows="2" class="input-base" placeholder="Catatan tambahan..."></textarea>
                    </div>

                    <button type="submit" class="btn-primary w-full" onclick="return confirm('Konfirmasi pengembalian ini?');">Konfirmasi Pengembalian</button>
                </form>
            @endif

            @if($pengajuan->catatan_admin)
                <div class="card-base dark:border-white/10 dark:bg-white/[0.03]">
                    <h3 class="text-sm font-semibold">Catatan Admin</h3>
                    <p class="mt-2 text-sm text-secondary/70 dark:text-white/50">{{ $pengajuan->catatan_admin }}</p>
                </div>
            @endif

            @if($pengajuan->catatan_pengembalian)
                <div class="card-base dark:border-white/10 dark:bg-white/[0.03]">
                    <h3 class="text-sm font-semibold">Catatan Pengembalian</h3>
                    <p class="mt-2 text-sm text-secondary/70 dark:text-white/50">{{ $pengajuan->catatan_pengembalian }}</p>
                </div>
            @endif

            {{-- Pengembalian bermasalah → ajak petugas mencatatnya sebagai pelanggaran (dasar pemblokiran) --}}
            @if(in_array($pengajuan->status_akhir, ['damaged', 'lost'], true) && $peminjam->isPeminjam() && ! $peminjam->trashed())
                <div class="flex flex-col gap-3 rounded-2xl border border-warning/30 bg-warning/10 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-start gap-3">
                        <x-icon name="flag" class="mt-0.5 h-5 w-5 flex-shrink-0 text-warning"/>
                        <p class="text-sm">Pengembalian ini ada barang yang {{ $pengajuan->status_akhir === 'lost' ? 'hilang' : 'rusak' }}. Catat sebagai pelanggaran agar tersimpan di riwayat anggota.</p>
                    </div>
                    <a href="{{ route('admin.pengguna.show', ['pengguna' => $peminjam, 'pengajuan' => $pengajuan->id, 'jenis' => $pengajuan->status_akhir === 'lost' ? 'kehilangan' : 'kerusakan']) }}#catat"
                       class="btn-outline whitespace-nowrap text-xs !border-warning/40 !text-warning hover:!bg-warning/10">Catat pelanggaran</a>
                </div>
            @endif
        </div>

        {{-- Sidebar info peminjam & jadwal --}}
        <div class="space-y-6">
            <div class="card-base dark:border-white/10 dark:bg-white/[0.03]">
                <div class="flex items-center justify-between gap-2">
                    <h3 class="text-sm font-semibold">Peminjam</h3>
                    @if($peminjam->trashed())
                        <span class="badge-surface">Dihapus</span>
                    @elseif($peminjam->isDiblokir())
                        <span class="badge-danger"><x-icon name="ban" class="h-3 w-3"/> Diblokir</span>
                    @else
                        <span class="badge-success">Aktif</span>
                    @endif
                </div>
                <div class="mt-3 flex items-center gap-3">
                    <img src="{{ $peminjam->avatar_url }}" class="h-11 w-11 flex-shrink-0 rounded-full object-cover" alt="">
                    <div class="min-w-0">
                        @if($peminjam->isPeminjam())
                            <a href="{{ route('admin.pengguna.show', $peminjam) }}" class="block truncate text-sm font-medium hover:text-accent" title="Lihat profil &amp; catatan pelanggaran">{{ $peminjam->nama }}</a>
                        @else
                            <p class="truncate text-sm font-medium">{{ $peminjam->nama }}</p>
                        @endif
                        <p class="text-xs text-secondary/50 dark:text-white/40">{{ \App\Support\Identitas::formatNoHp($peminjam->no_hp) }}</p>
                    </div>
                </div>

                <div x-data="salinTeks(@js((string) $peminjam->kode_anggota))" class="mt-3 flex items-center justify-between gap-2 rounded-xl bg-surface px-3 py-2 dark:bg-white/5">
                    <div class="min-w-0">
                        <p class="text-[10px] text-secondary/45 dark:text-white/35">ID Anggota</p>
                        <p class="truncate font-mono text-xs font-semibold">{{ $peminjam->kode_anggota }}</p>
                    </div>
                    <button type="button" @click="salin()" class="flex-shrink-0 text-secondary/40 transition hover:text-accent" title="Salin ID" aria-label="Salin ID Anggota">
                        <x-icon name="copy" class="h-4 w-4" x-show="!tersalin"/>
                        <x-icon name="check" class="h-4 w-4 text-success" x-show="tersalin" x-cloak/>
                    </button>
                </div>

                @if($penipuanPeminjam > 0)
                    <p @class(['mt-2.5 flex items-center gap-1.5 text-xs font-medium', 'text-danger' => $penipuanPeminjam >= $batasPenipuan, 'text-warning' => $penipuanPeminjam < $batasPenipuan])>
                        <x-icon name="flag" class="h-3.5 w-3.5"/> Penipuan tercatat {{ $penipuanPeminjam }} dari {{ $batasPenipuan }}
                    </p>
                @endif

                <div class="mt-4 space-y-2">
                    <a href="https://wa.me/{{ $peminjam->no_hp_normal ?: preg_replace('/^0/', '62', (string) $peminjam->no_hp) }}" target="_blank" rel="noopener" class="btn-outline w-full text-xs !border-[#25D366]/30 !text-[#25D366] hover:!bg-[#25D366]/10">
                        <x-icon name="whatsapp" class="h-3.5 w-3.5"/> Hubungi via WhatsApp
                    </a>
                    @if($peminjam->isPeminjam())
                        <a href="{{ route('admin.pengguna.show', $peminjam) }}" class="btn-outline w-full text-xs">
                            <x-icon name="shield-check" class="h-3.5 w-3.5"/> Profil &amp; catatan pelanggaran
                        </a>
                    @endif
                </div>
            </div>

            <div class="card-base dark:border-white/10 dark:bg-white/[0.03]">
                <h3 class="text-sm font-semibold">Jadwal & Keperluan</h3>
                <dl class="mt-3 space-y-2.5 text-sm">
                    <div class="flex justify-between"><dt class="text-secondary/50 dark:text-white/40">Tanggal Pinjam</dt><dd class="font-medium">{{ $pengajuan->tanggal_pinjam->format('d M Y') }}</dd></div>
                    <div class="flex justify-between"><dt class="text-secondary/50 dark:text-white/40">Tanggal Kembali</dt><dd class="font-medium">{{ $pengajuan->tanggal_kembali->format('d M Y') }}</dd></div>
                    @if($pengajuan->petugas)
                        <div class="flex justify-between"><dt class="text-secondary/50 dark:text-white/40">Diproses oleh</dt><dd class="font-medium">{{ $pengajuan->petugas->nama }}</dd></div>
                    @endif
                </dl>
                <div class="mt-3 border-t border-secondary/5 pt-3 dark:border-white/10">
                    <dt class="text-xs text-secondary/50 dark:text-white/40">Keperluan</dt>
                    <dd class="mt-1 text-sm">{{ $pengajuan->keperluan }}</dd>
                </div>
            </div>
        </div>
    </div>
@endsection
