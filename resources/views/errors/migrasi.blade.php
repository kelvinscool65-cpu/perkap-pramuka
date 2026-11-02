@extends('errors.layout')

@section('judul', 'Database perlu diperbarui')

@section('catatan', 'halaman ini muncul karena database perlu diperbarui.')

@section('isi')
    <h1>Database perlu diperbarui</h1>
    <p>
        Versi aplikasi ini memakai struktur database yang lebih baru daripada database Anda
        ({{ count($tertunda) }} pembaruan belum diterapkan). Selama belum diperbarui, beberapa fitur
        dapat gagal tanpa pesan — misalnya harga tampil <code>Rp 0</code> atau tombol <em>Ajukan</em>
        tidak bereaksi. Data yang sudah ada <strong>tidak dihapus</strong>.
    </p>

    <ol>
        <li>Buka terminal / Command Prompt di folder proyek ini.</li>
        <li>Jalankan: <code>php artisan migrate</code></li>
        <li>Muat ulang halaman ini.</li>
    </ol>

    <pre>cd {{ basename(base_path()) }}
php artisan migrate</pre>

    <p style="margin-bottom:6px"><strong>Pembaruan yang belum diterapkan:</strong></p>
    <div class="daftar">
        @foreach($tertunda as $nama)
            <div>{{ $nama }}</div>
        @endforeach
    </div>

    <a href="{{ url()->current() }}" class="tombol">Muat ulang halaman</a>
@endsection
