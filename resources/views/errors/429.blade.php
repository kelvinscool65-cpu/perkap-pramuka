@php
    // Header Retry-After berisi detik; dijadikan "N detik" / "N menit" agar mudah dipahami.
    $detik = (int) (isset($exception) && method_exists($exception, 'getHeaders') ? ($exception->getHeaders()['Retry-After'] ?? 0) : 0);
    $tunggu = $detik <= 0 ? 'beberapa saat' : ($detik >= 120 ? ceil($detik / 60).' menit' : $detik.' detik');
@endphp
@include('errors.pesan', [
    'kode' => 429,
    'judul' => 'Terlalu banyak permintaan',
    'uraian' => "Permintaan dari jaringan Anda terlalu sering dalam waktu singkat. Tunggu {$tunggu}, lalu coba lagi. Pembatasan ini melindungi sistem dari penyalahgunaan.",
    'ikon' => '<path d="M7 4h10M7 20h10"/><path d="M8 4v3a4 4 0 0 0 1.6 3.2L12 12l-2.4 1.8A4 4 0 0 0 8 17v3"/><path d="M16 4v3a4 4 0 0 1-1.6 3.2L12 12l2.4 1.8A4 4 0 0 1 16 17v3"/>',
    'tombol' => 'Kembali ke halaman sebelumnya',
    'tautan' => url()->previous(),
])
