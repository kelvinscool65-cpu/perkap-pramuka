@include('errors.pesan', [
    'kode' => 500,
    'judul' => 'Terjadi gangguan di server',
    'uraian' => 'Ini bukan kesalahan Anda. Coba muat ulang beberapa saat lagi. Bila terus berulang, hubungi petugas Perkap via WhatsApp '
        .\App\Support\Identitas::formatNoHp(config('perkap.kontak_wa')).'.',
    'ikon' => '<path d="M12 4 3 19h18L12 4Z"/><path d="M12 10v4M12 17h.01"/>',
])
