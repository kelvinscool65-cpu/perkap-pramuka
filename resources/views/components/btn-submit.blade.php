@props(['loadingLabel' => 'Memproses...', 'class' => 'btn-primary w-full'])
{{-- Dipakai di dalam <form x-data="{ loading:false }" @submit="loading = true">.
     Tombol otomatis nonaktif + tampil spinner saat form dikirim, mencegah
     submit ganda (mis. klik 2x "Ajukan Peminjaman"). --}}
<button type="submit" :disabled="loading" {{ $attributes->merge(['class' => $class.' disabled:cursor-wait']) }}>
    <span x-show="!loading" class="flex items-center justify-center gap-1.5">{{ $slot }}</span>
    <span x-show="loading" x-cloak class="flex items-center justify-center gap-1.5">
        <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" opacity="0.25"/>
            <path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="4" stroke-linecap="round"/>
        </svg>
        {{ $loadingLabel }}
    </span>
</button>
