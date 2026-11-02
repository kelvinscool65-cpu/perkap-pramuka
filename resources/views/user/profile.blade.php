@extends(auth()->user()->isAdmin() ? 'layouts.admin' : 'layouts.user', ['title' => 'Profil Saya'])

@section('content')
<div class="mx-auto max-w-2xl space-y-6">
    <div>
        <h1 class="text-xl font-bold">Profil Saya</h1>
        <p class="text-sm text-secondary/60 dark:text-white/50">Kelola akun dan keamanan kamu.</p>
    </div>

    {{-- ID Anggota: hanya-baca. Tidak ada kolom untuk mengubahnya, dan server mengabaikannya bila dikirim. --}}
    <div class="card-base dark:border-white/10 dark:bg-white/[0.03]">
        <div class="flex flex-wrap items-center gap-3">
            <span class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-accent to-violet-600 text-white shadow-sm shadow-accent/30">
                <x-icon name="id-card" class="h-5 w-5"/>
            </span>
            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <p class="text-xs text-secondary/50 dark:text-white/40">ID Anggota</p>
                    <span class="inline-flex items-center gap-1 rounded-full bg-accent/10 px-1.5 py-0.5 text-[10px] font-semibold text-accent"><x-icon name="lock" class="h-3 w-3"/> Permanen</span>
                </div>
                <p class="truncate font-mono text-base font-bold tracking-wide">{{ $user->kode_anggota }}</p>
            </div>
            <div class="flex gap-2">
                <div x-data="salinTeks(@js((string) $user->kode_anggota))">
                    <button type="button" @click="salin()" class="btn-outline text-xs">
                        <x-icon name="copy" class="h-3.5 w-3.5" x-show="!tersalin"/>
                        <x-icon name="check" class="h-3.5 w-3.5 text-success" x-show="tersalin" x-cloak/>
                        <span x-text="tersalin ? 'Tersalin' : 'Salin'">Salin</span>
                    </button>
                </div>
                @unless(auth()->user()->isAdmin())
                    <a href="{{ route('user.kartu') }}" class="btn-outline text-xs">Lihat kartu</a>
                @endunless
            </div>
        </div>
        <p class="mt-3 text-xs text-secondary/50 dark:text-white/40">Dibuat otomatis saat akun dibuat. ID ini unik, tidak bisa diubah, dan tidak dipakai ulang, termasuk oleh petugas.</p>
    </div>

    {{-- Info akun --}}
    <form method="POST" action="{{ route(auth()->user()->isAdmin() ? 'admin.profile.update' : 'user.profile.update') }}" enctype="multipart/form-data" class="card-base space-y-4 dark:border-white/10 dark:bg-white/[0.03]">
        @csrf
        @method('PUT')

        <div class="flex items-center gap-4" x-data="{ preview: null }">
            <div class="h-16 w-16 overflow-hidden rounded-full bg-surface dark:bg-white/10">
                <template x-if="preview"><img :src="preview" class="h-full w-full object-cover"></template>
                <template x-if="!preview"><img src="{{ $user->avatar_url }}" class="h-full w-full object-cover"></template>
            </div>
            <div>
                <label class="btn-outline cursor-pointer text-xs">
                    Ganti Foto
                    <input type="file" name="avatar" accept="image/*" class="hidden" @change="preview = URL.createObjectURL($event.target.files[0])">
                </label>
                <p class="mt-1.5 text-xs text-secondary/40">JPG/PNG, maks 2MB.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="label-base">Nama Lengkap</label>
                <input type="text" name="nama" value="{{ old('nama', $user->nama) }}" required class="input-base">
                @error('nama') <p class="mt-1.5 text-xs text-danger">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label-base">Username</label>
                <input type="text" name="username" value="{{ old('username', $user->username) }}" required class="input-base">
                @error('username') <p class="mt-1.5 text-xs text-danger">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label-base">Email</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="input-base">
                @error('email') <p class="mt-1.5 text-xs text-danger">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label-base">No. WhatsApp</label>
                <input type="text" name="no_hp" value="{{ old('no_hp', $user->no_hp) }}" required class="input-base">
                @error('no_hp') <p class="mt-1.5 text-xs text-danger">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label-base">Peran</label>
                <input type="text" value="{{ $user->role_label }}" disabled class="input-base opacity-60">
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="btn-primary">Simpan Perubahan</button>
        </div>
    </form>

    {{-- Ganti password --}}
    <form method="POST" action="{{ route(auth()->user()->isAdmin() ? 'admin.profile.password' : 'user.profile.password') }}" class="card-base space-y-4 dark:border-white/10 dark:bg-white/[0.03]">
        @csrf
        @method('PUT')
        <h2 class="text-sm font-semibold">Keamanan — Ganti Password</h2>

        <div>
            <label class="label-base">Password Lama</label>
            <input type="password" name="password_lama" required class="input-base">
            @error('password_lama') <p class="mt-1.5 text-xs text-danger">{{ $message }}</p> @enderror
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="label-base">Password Baru</label>
                <input type="password" name="password" required class="input-base">
                @error('password') <p class="mt-1.5 text-xs text-danger">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label-base">Konfirmasi Password Baru</label>
                <input type="password" name="password_confirmation" required class="input-base">
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="btn-outline">Ubah Password</button>
        </div>
    </form>
</div>
@endsection
