@props(['kategori'])
{{-- 5 kategori = 5 warna berbeda (bukan abu-abu polos) — dipakai di kartu alat
     & tabel admin. 'danger' (merah) sengaja tidak dipakai di sini, supaya
     tetap murni jadi sinyal "ada masalah" di badge status, tidak tertukar
     makna dengan tag kategori yang netral. --}}
<span @class([
    'badge',
    'bg-info/10 text-info' => $kategori === 'Tenda',
    'bg-accent/10 text-accent' => $kategori === 'Peralatan',
    'bg-success/10 text-success' => $kategori === 'Semaphore',
    'bg-warning/10 text-warning' => $kategori === 'Seragam',
    'bg-rose/10 text-rose' => $kategori === 'Atribut',
])>{{ $kategori }}</span>
