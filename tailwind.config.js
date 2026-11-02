import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    /*
     * SAFELIST kelas komponen yang disusun DINAMIS di Blade, mis. class="badge-{{ $p->warna_status }}".
     * Tailwind v3 membuang kelas komponen (@layer components) yang tidak tertulis LITERAL di file
     * konten — tanpa daftar ini, badge status ("Menunggu", "Disetujui", dst.) tampil polos tanpa
     * warna. Ditulis eksplisit (bukan pola) supaya pasti terbaca. Jika menambah varian warna
     * baru di app.css, tambahkan juga di sini.
     */
    safelist: [
        'badge-warning', 'badge-success', 'badge-danger', 'badge-accent', 'badge-info', 'badge-rose', 'badge-surface',
        'wash-accent', 'wash-success', 'wash-warning', 'wash-danger', 'wash-info', 'wash-rose',
    ],

    theme: {
        extend: {
            // Skala spasi bawaan Tailwind v3 tidak punya 4.5; dipakai untuk ikon 18px (h-4.5 w-4.5).
            spacing: {
                4.5: '1.125rem',
            },
            // Palet warna resmi sesuai pedoman desain brief, ditambah 'info'
            // (biru) supaya ada variasi warna dekoratif (tag kategori, dll)
            // yang TIDAK bentrok dengan makna warna status (warning/success/danger).
            colors: {
                primary: '#FFFFFF',
                secondary: '#111111',
                surface: '#F3F4F6',
                success: '#10B981',
                accent: '#8B5CF6',
                warning: '#F59E0B',
                danger: '#EF4444',
                info: '#3B82F6',
                rose: '#EC4899',
            },
            fontFamily: {
                sans: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
