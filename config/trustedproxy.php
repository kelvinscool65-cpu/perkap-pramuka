<?php

/*
|--------------------------------------------------------------------------
| Proxy tepercaya
|--------------------------------------------------------------------------
| Dibaca otomatis oleh middleware TrustProxies bawaan Laravel (config('trustedproxy.proxies')).
| Bila aplikasi berada di belakang proxy/CDN (Nginx, Cloudflare, load balancer), isi TRUSTED_PROXIES
| di .env dengan IP proxy dipisah koma (atau "*") supaya alamat IP pengunjung terbaca dari header
| X-Forwarded-For. Ini penting untuk batas pendaftaran per IP.
|
| Bawaan KOSONG = percaya hanya pada alamat koneksi langsung. Jangan isi "*" bila server juga bisa
| diakses langsung tanpa proxy: header palsu dapat dipakai untuk menyiasati batas per IP.
*/

$proxies = trim((string) env('TRUSTED_PROXIES', ''));

return [
    'proxies' => $proxies === ''
        ? null
        : ($proxies === '*' ? '*' : array_values(array_filter(array_map('trim', explode(',', $proxies))))),
];
