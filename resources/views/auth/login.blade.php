@extends('layouts.guest', ['title' => 'Masuk'])

@section('content')
    <h1 class="text-2xl font-bold">Selamat Datang</h1>
    <p class="mt-1.5 text-sm text-secondary/60">Masuk ke akun anda untuk melanjutkan.</p>

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-4" x-data="{ loading: false }" @submit="loading = true">
        @csrf

        <div>
            <label for="login" class="label-base">Username atau Email</label>
            <input id="login" type="text" name="login" value="{{ old('login') }}" required autofocus
                   class="input-base" placeholder="mis. peminjam1">
            @error('login') <p class="mt-1.5 text-xs text-danger">{{ $message }}</p> @enderror
        </div>

        <div x-data="{ show: false }">
            <label for="password" class="label-base">Password</label>
            <div class="relative">
                <input :type="show ? 'text' : 'password'" id="password" name="password" required
                       class="input-base pr-11" placeholder="••••••••">
                <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex w-10 items-center justify-center text-secondary/40 hover:text-secondary">
                    <x-icon name="eye" class="h-4 w-4" x-show="!show"/>
                    <x-icon name="eye-off" class="h-4 w-4" x-show="show" x-cloak/>
                </button>
            </div>
        </div>

        <div class="flex items-center justify-between text-sm">
            <label class="flex items-center gap-2 text-secondary/60">
                <input type="checkbox" name="remember" class="rounded border-secondary/20 text-accent focus:ring-accent/30">
                Ingat saya
            </label>
        </div>

        <x-btn-submit loading-label="Memeriksa akun...">Masuk</x-btn-submit>
    </form>

    <p class="mt-6 text-center text-sm text-secondary/60">
        Belum punya akun?
        <a href="{{ route('register') }}" class="font-semibold text-accent hover:underline">Daftar sekarang</a>
    </p>

    <div class="mt-8 rounded-2xl bg-surface p-4 text-xs text-secondary/50">
        <p class="font-medium text-secondary/70">Akun demo</p>
        <p class="mt-1">Admin — admin@perkap.test / password</p>
        <p>Peminjam — peminjam@perkap.test / password</p>
    </div>
@endsection
