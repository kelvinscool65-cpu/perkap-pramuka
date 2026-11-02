@extends('layouts.admin', ['title' => 'Tambah Alat'])

@section('content')
    <div class="mb-6 flex items-center gap-3">
        <a href="{{ route('admin.perlengkapan.index') }}" class="flex h-9 w-9 items-center justify-center rounded-full bg-surface dark:bg-white/10">
            <x-icon name="arrow-left" class="h-4 w-4"/>
        </a>
        <div>
            <h2 class="text-base font-semibold">Tambah Alat Baru</h2>
            <p class="text-xs text-secondary/50 dark:text-white/40">Kode alat akan digenerate otomatis sesuai kategori.</p>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.perlengkapan.store') }}" enctype="multipart/form-data" x-data="{ loading: false }" @submit="loading = true">
        @csrf
        @include('admin.perlengkapan._form', ['alat' => null])

        <div class="mt-6 flex justify-end gap-3">
            <a href="{{ route('admin.perlengkapan.index') }}" class="btn-outline">Batal</a>
            <x-btn-submit loading-label="Menyimpan...">Simpan Alat</x-btn-submit>
        </div>
    </form>
@endsection
