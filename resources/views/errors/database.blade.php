@extends('errors.layout')

@section('judul', 'Database belum dapat dihubungi')

@section('catatan', 'halaman ini hanya muncul bila database belum siap.')

@section('isi')
    <h1>Database belum dapat dihubungi</h1>
    <p>Aplikasi tidak berhasil terhubung ke database. Periksa langkah berikut:</p>

    <ol>
        <li>Pastikan server MySQL sudah menyala (XAMPP / Laragon / layanan MySQL).</li>
        <li>Pastikan database <code>{{ config('database.connections.'.config('database.default').'.database') }}</code> sudah dibuat.</li>
        <li>Cocokkan <code>DB_HOST</code>, <code>DB_PORT</code>, <code>DB_DATABASE</code>, <code>DB_USERNAME</code>, <code>DB_PASSWORD</code> di file <code>.env</code>.</li>
        <li>Pertama kali memakai database baru? Jalankan <code>php artisan migrate --seed</code>.</li>
        <li>Setelah mengubah <code>.env</code>, jalankan <code>php artisan config:clear</code>.</li>
    </ol>

    @if(!empty($detail))
        <div class="detail"><strong>Detail teknis:</strong> {{ $detail }}</div>
    @endif

    <p>Ingin diagnosis otomatis? Jalankan <code>php artisan perkap:periksa</code>.</p>

    <a href="{{ url()->current() }}" class="tombol">Coba lagi</a>
@endsection
