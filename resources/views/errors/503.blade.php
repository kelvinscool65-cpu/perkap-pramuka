@include('errors.pesan', [
    'kode' => 503,
    'judul' => 'Sedang dalam pemeliharaan',
    'uraian' => 'Layanan sedang diperbarui sebentar. Coba lagi dalam beberapa menit. Data peminjaman Anda aman.',
    'ikon' => '<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L4 17l3 3 5.3-5.3a4 4 0 0 0 5.4-5.4l-2.6 2.6-2.4-.6-.6-2.4 2.6-2.6Z"/>',
    'tombol' => 'Muat ulang',
    'tautan' => url()->current(),
])
