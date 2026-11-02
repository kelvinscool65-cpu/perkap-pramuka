<?php

/*
|--------------------------------------------------------------------------
| Pengaturan khusus aplikasi Perkap Pramuka
|--------------------------------------------------------------------------
| Semua nilai bisa diubah lewat file .env tanpa mengedit kode.
*/

return [

    // Kontak petugas Perkap (tampil di pesan akun diblokir, batas pendaftaran, dll.)
    'kontak_wa' => env('PERKAP_WA', '085877708909'),

    // Awalan ID Anggota (2–6 huruf), mis. PRK-26-K7M2QH-9. JANGAN diubah setelah ada pengguna
    // terdaftar: ID yang sudah terbit tetap berlaku, tetapi ID baru akan memakai awalan yang baru.
    'prefix_id_anggota' => env('PERKAP_PREFIX_ID', 'PRK'),

    // Batas pendaftaran akun dari SATU alamat IP dalam jendela waktu bergulir (anti pembuatan akun
    // massal). Bawaan: maksimal 3 akun per 24 jam dari IP yang sama.
    // CATATAN: seluruh siswa yang memakai Wi-Fi sekolah yang sama tampil sebagai SATU IP publik.
    // Untuk pendaftaran massal di sekolah, naikkan batasnya sementara (mis. 40) atau daftarkan
    // IP publik sekolah di `ip_dikecualikan`.
    'batas_daftar_per_ip' => (int) env('PERKAP_BATAS_DAFTAR_PER_IP', 3),
    'jendela_daftar_jam' => (int) env('PERKAP_JENDELA_DAFTAR_JAM', 24),

    // IP / blok CIDR yang dikecualikan dari batas pendaftaran, dipisah koma.
    // Contoh: PERKAP_IP_DIKECUALIKAN=203.0.113.10,198.51.100.0/24
    'ip_dikecualikan' => array_values(array_filter(array_map('trim', explode(',', (string) env('PERKAP_IP_DIKECUALIKAN', ''))))),

    // Batas PERCOBAAN mengirim formulir daftar (termasuk yang gagal validasi) per IP per jam.
    // Berbeda dari batas di atas: ini menahan spam/bot, bukan jumlah akun yang jadi.
    'batas_percobaan_daftar' => (int) env('PERKAP_BATAS_PERCOBAAN_DAFTAR', 20),

    // Jumlah pelanggaran jenis "penipuan" yang membuat akun ditandai LAYAK DIBLOKIR di panel admin.
    // Pemblokiran tetap keputusan petugas (tidak otomatis).
    'batas_penipuan' => (int) env('PERKAP_BATAS_PENIPUAN', 3),

    // Periksa migrasi database yang belum dijalankan pada setiap permintaan, lalu tampilkan
    // petunjuk "jalankan php artisan migrate" alih-alih membiarkan fitur gagal tanpa pesan.
    // Aman dimatikan (false) bila deploy sudah stabil.
    'periksa_migrasi' => (bool) env('PERKAP_PERIKSA_MIGRASI', true),

];
