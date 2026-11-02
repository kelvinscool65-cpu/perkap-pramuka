@include('errors.pesan', [
    'kode' => 419,
    'judul' => 'Sesi formulir berakhir',
    'uraian' => 'Halaman ini terlalu lama dibiarkan terbuka, sehingga sesi pengisian formulirnya kedaluwarsa. Buka kembali halamannya, lalu kirim ulang isiannya.',
    'ikon' => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
    'tombol' => 'Kembali ke halaman sebelumnya',
    'tautan' => url()->previous(),
])
