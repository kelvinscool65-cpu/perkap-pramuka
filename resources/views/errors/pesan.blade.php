@extends('errors.layout')
{{-- Kerangka halaman galat umum (403/404/419/429/500/503). Variabel: $kode, $judul, $uraian, $ikon (isi <svg>),
     opsional $tombol & $tautan. Berdiri sendiri: tanpa Vite, sesi, maupun database. --}}

@section('judul', $judul)

@section('ikon')
    <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $ikon !!}</svg>
@endsection

@section('catatan', 'Kode galat '.$kode)

@section('isi')
    <h1>{{ $judul }}</h1>
    <p>{{ $uraian }}</p>
    <a href="{{ $tautan ?? url('/') }}" class="tombol">{{ $tombol ?? 'Kembali ke beranda' }}</a>
@endsection
