{{-- Modal blokir akun — dipakai di daftar pengguna dan halaman detail pengguna.
     Syarat: pembungkus memiliki variabel Alpine `blokir` (null = tertutup; terbuka bila berisi
     { nama, aksi, menunggu, saran }). Di-teleport ke <body> supaya tidak terjebak
     transform/overflow wadah halaman (pelajaran dari modal Peringatan di Laporan). --}}
<template x-teleport="body">
    <div x-show="blokir" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
         x-transition.opacity
         role="dialog" aria-modal="true" aria-label="Blokir akun"
         @click.self="blokir = null"
         @keydown.escape.window="blokir = null">
        <div x-show="blokir" x-transition
             x-data="{ alasan: '' }"
             x-effect="alasan = blokir ? (alasan || blokir.saran || '') : ''"
             class="max-h-[90vh] w-full max-w-md overflow-y-auto rounded-2xl bg-white p-5 text-secondary shadow-xl dark:bg-secondary dark:text-white">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-danger to-red-600 text-white shadow-sm shadow-danger/30">
                        <x-icon name="ban" class="h-5 w-5"/>
                    </span>
                    <div>
                        <h3 class="text-sm font-bold">Blokir akun</h3>
                        <p class="mt-0.5 text-xs text-secondary/55 dark:text-white/45">
                            <span class="font-semibold text-secondary dark:text-white" x-text="blokir?.nama"></span> tidak akan bisa masuk lagi sampai blokirnya dibuka.
                        </p>
                    </div>
                </div>
                <button type="button" @click="blokir = null" aria-label="Tutup" class="text-secondary/40 hover:text-secondary dark:hover:text-white"><x-icon name="x" class="h-4 w-4"/></button>
            </div>

            <form method="POST" :action="blokir ? blokir.aksi : '#'">
                @csrf
                <label for="alasan-blokir" class="label-base">Alasan pemblokiran</label>
                <textarea id="alasan-blokir" name="alasan" x-model="alasan" rows="3" required minlength="10" maxlength="500" class="input-base"
                          placeholder="Contoh: Tercatat tiga kali tidak mengembalikan barang pinjaman."></textarea>
                <p class="mt-1 text-[11px] text-secondary/45 dark:text-white/35">Alasan ini akan ditampilkan kepada pemilik akun saat ia mencoba masuk.</p>

                <ul class="mt-3 space-y-1.5 rounded-xl bg-surface p-3 text-xs text-secondary/70 dark:bg-white/5 dark:text-white/55">
                    <li class="flex gap-2"><x-icon name="check" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0 text-danger"/> Sesi yang sedang aktif langsung diputus.</li>
                    <li class="flex gap-2"><x-icon name="check" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0 text-danger"/> <span>Pengajuan yang menunggu (<span x-text="blokir?.menunggu ?? 0"></span>) ditolak otomatis.</span></li>
                    <li class="flex gap-2"><x-icon name="check" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0 text-success"/> Pengembalian barang yang sedang dipinjam tetap bisa diproses.</li>
                    <li class="flex gap-2"><x-icon name="check" class="mt-0.5 h-3.5 w-3.5 flex-shrink-0 text-success"/> Blokir dapat dibuka lagi kapan saja.</li>
                </ul>

                <div class="mt-4 flex gap-2">
                    <button type="button" @click="blokir = null" class="btn-outline flex-1">Batal</button>
                    <button type="submit" class="btn-danger flex-1"><x-icon name="ban" class="h-3.5 w-3.5"/> Blokir akun</button>
                </div>
            </form>
        </div>
    </div>
</template>
