import './bootstrap';

import Alpine from 'alpinejs';

// Catatan: Alpine.store('cart', ...) untuk keranjang peminjaman didaftarkan
// langsung di resources/views/layouts/user.blade.php lewat event 'alpine:init'
// (dijalankan otomatis sebelum Alpine.start() di bawah ini).

/*
 * Komponen "Salin ke papan klip" — dipakai di Kartu Anggota, Profil, dan panel admin:
 *   <div x-data="salinTeks('PRK-26-K7M2QH-9')"> <button @click="salin()">…</button> <span x-show="tersalin">Tersalin</span> </div>
 * Clipboard API hanya tersedia di HTTPS/localhost; di http biasa (mis. diakses lewat IP jaringan
 * lokal) dipakai cara cadangan lewat <textarea> sementara supaya tombol tetap berfungsi.
 */
Alpine.data('salinTeks', (teks) => ({
    tersalin: false,

    salin() {
        const selesai = () => {
            this.tersalin = true;
            setTimeout(() => (this.tersalin = false), 1800);
        };

        const cadangan = () => {
            const kotak = document.createElement('textarea');
            kotak.value = teks;
            kotak.setAttribute('readonly', '');
            kotak.style.cssText = 'position:fixed;top:0;left:0;opacity:0';
            document.body.appendChild(kotak);
            kotak.select();
            try {
                if (document.execCommand('copy')) selesai();
            } catch (e) {
                // browser memblokir penyalinan: tidak ada yang bisa dilakukan selain diam
            }
            document.body.removeChild(kotak);
        };

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(teks).then(selesai).catch(cadangan);
        } else {
            cadangan();
        }
    },
}));

window.Alpine = Alpine;

Alpine.start();
