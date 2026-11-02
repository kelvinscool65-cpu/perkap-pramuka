@php $alat = $alat ?? null; @endphp

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    <div class="card-base lg:col-span-2 dark:border-white/10 dark:bg-white/[0.03]">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="nama_alat" class="label-base">Nama Alat</label>
                <input type="text" id="nama_alat" name="nama_alat" value="{{ old('nama_alat', $alat->nama_alat ?? '') }}" required class="input-base" placeholder="mis. Tenda Regu">
                @error('nama_alat') <p class="mt-1.5 text-xs text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="kategori" class="label-base">Kategori</label>
                <select id="kategori" name="kategori" required class="input-base">
                    <option value="">Pilih kategori</option>
                    @foreach($kategoriList as $kat)
                        <option value="{{ $kat }}" @selected(old('kategori', $alat->kategori ?? '') === $kat)>{{ $kat }}</option>
                    @endforeach
                </select>
                @error('kategori') <p class="mt-1.5 text-xs text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="satuan" class="label-base">Satuan</label>
                <input type="text" id="satuan" name="satuan" value="{{ old('satuan', $alat->satuan ?? 'Pcs') }}" required class="input-base" placeholder="Pcs / Biji / Pasang">
                @error('satuan') <p class="mt-1.5 text-xs text-danger">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="stok_total" class="label-base">Stok Total</label>
                <input type="number" min="0" id="stok_total" name="stok_total" value="{{ old('stok_total', $alat->stok_total ?? '') }}" required class="input-base">
                @error('stok_total') <p class="mt-1.5 text-xs text-danger">{{ $message }}</p> @enderror
                @if($alat)
                    <p class="mt-1.5 text-xs text-secondary/40">Stok tersedia saat ini: {{ $alat->stok_tersedia }}. Selisih akan otomatis disesuaikan.</p>
                @endif
            </div>

            <div>
                <label for="harga_sewa" class="label-base">Harga Sewa (per peminjaman)</label>
                <div class="relative">
                    <span class="pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-secondary/40">Rp</span>
                    <input type="number" min="0" step="500" id="harga_sewa" name="harga_sewa" value="{{ old('harga_sewa', $alat->harga_sewa ?? 0) }}" required class="input-base pl-10">
                </div>
                @error('harga_sewa') <p class="mt-1.5 text-xs text-danger">{{ $message }}</p> @enderror
                <p class="mt-1.5 text-xs text-secondary/40">Terlihat oleh peminjam di katalog. Isi 0 jika gratis.</p>
            </div>

            <div>
                <label for="kondisi" class="label-base">Kondisi Umum</label>
                <select id="kondisi" name="kondisi" required class="input-base">
                    <option value="good" @selected(old('kondisi', $alat->kondisi ?? 'good') === 'good')>Baik</option>
                    <option value="damaged" @selected(old('kondisi', $alat->kondisi ?? '') === 'damaged')>Rusak</option>
                    <option value="lost" @selected(old('kondisi', $alat->kondisi ?? '') === 'lost')>Hilang</option>
                </select>
            </div>

            @if($alat)
                <div>
                    <label for="status" class="label-base">Status</label>
                    <select id="status" name="status" required class="input-base">
                        <option value="tersedia" @selected(old('status', $alat->status) === 'tersedia')>Tersedia</option>
                        <option value="dipinjam" @selected(old('status', $alat->status) === 'dipinjam')>Dipinjam</option>
                        <option value="perawatan" @selected(old('status', $alat->status) === 'perawatan')>Perawatan</option>
                    </select>
                </div>
            @endif

            <div class="sm:col-span-2">
                <label for="deskripsi" class="label-base">Deskripsi Singkat</label>
                <textarea id="deskripsi" name="deskripsi" rows="4" maxlength="1000" class="input-base" placeholder="Penjelasan singkat tentang alat ini — cara pakai, catatan kondisi, dll. Akan tampil saat peminjam menekan kartu alat ini.">{{ old('deskripsi', $alat->deskripsi ?? '') }}</textarea>
                <p class="mt-1.5 text-xs text-secondary/40">Kosongkan jika belum ada catatan — peminjam akan melihat pesan default.</p>
                @error('deskripsi') <p class="mt-1.5 text-xs text-danger">{{ $message }}</p> @enderror
            </div>
        </div>
    </div>

    <div class="card-base h-fit dark:border-white/10 dark:bg-white/[0.03]">
        <label class="label-base">Foto Alat (opsional)</label>
        <div x-data="{ preview: null }" class="space-y-3">
            <div class="aspect-square w-full overflow-hidden rounded-2xl bg-surface dark:bg-white/5">
                <template x-if="preview">
                    <img :src="preview" class="h-full w-full object-cover">
                </template>
                <template x-if="!preview">
                    <div class="h-full w-full">
                        @if($alat && $alat->gambar)
                            <img src="{{ $alat->gambar_url }}" class="h-full w-full object-cover">
                        @elseif($alat)
                            <div @class([
                                'flex h-full w-full items-center justify-center',
                                'wash-info' => $alat->warna_ikon === 'info',
                                'wash-accent' => $alat->warna_ikon === 'accent',
                                'wash-success' => $alat->warna_ikon === 'success',
                                'wash-warning' => $alat->warna_ikon === 'warning',
                                'wash-rose' => $alat->warna_ikon === 'rose',
                            ])>
                                <x-alat-icon :ikon="$alat->ikon" class="h-2/3 w-2/3"/>
                            </div>
                        @else
                            <div class="flex h-full w-full items-center justify-center bg-surface dark:bg-white/5">
                                <x-alat-icon ikon="default" class="h-2/3 w-2/3 opacity-50"/>
                            </div>
                        @endif
                    </div>
                </template>
            </div>
            <input type="file" name="gambar" accept="image/*" @change="preview = URL.createObjectURL($event.target.files[0])" class="block w-full text-xs text-secondary/60 file:btn-outline file:mr-3 file:border-0">
            @error('gambar') <p class="text-xs text-danger">{{ $message }}</p> @enderror
            @if($alat && ! $alat->gambar)
                <p class="text-xs text-secondary/40">Belum ada foto asli — memakai ilustrasi otomatis. Upload foto kapan saja untuk menggantinya.</p>
            @endif
        </div>
    </div>
</div>
