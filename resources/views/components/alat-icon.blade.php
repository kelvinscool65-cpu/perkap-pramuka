@props(['ikon', 'class' => 'h-full w-full'])
{{-- Ilustrasi vektor custom per jenis alat (bukan foto — dibuat sendiri supaya
     bebas lisensi & selalu cocok dgn nama barangnya). Dipanggil lewat
     <x-alat-icon :ikon="$alat->ikon"/>. Lihat Alat::getIkonAttribute() utk
     pemetaan 52 nama barang -> salah satu dari 27 kunci ikon di bawah ini. --}}
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" {{ $attributes->merge(['class' => $class]) }}>
    @switch($ikon)

        @case('tenda')
            <path d="M8 82 L50 18 L92 82 Z" fill="#3B82F6"/>
            <path d="M50 18 L92 82 L74 82 L50 40 Z" fill="#2563EB"/>
            <path d="M34 82 L50 48 L66 82 Z" fill="#111111" opacity="0.85"/>
            <rect x="4" y="80" width="92" height="5" rx="2.5" fill="#92400E"/>
            @break

        @case('matras')
            <rect x="10" y="32" width="80" height="36" rx="8" fill="#10B981"/>
            <rect x="10" y="32" width="80" height="36" rx="8" fill="url(#matrasShade)" opacity="0.15"/>
            <line x1="22" y1="42" x2="78" y2="42" stroke="#fff" stroke-width="2.5" opacity="0.55"/>
            <line x1="22" y1="50" x2="78" y2="50" stroke="#fff" stroke-width="2.5" opacity="0.55"/>
            <line x1="22" y1="58" x2="78" y2="58" stroke="#fff" stroke-width="2.5" opacity="0.55"/>
            @break

        @case('pasak')
            <path d="M46 10 L54 10 L59 68 L50 92 L41 68 Z" fill="#6B7280"/>
            <ellipse cx="50" cy="11" rx="9" ry="4.5" fill="#4B5563"/>
            <path d="M46 10 L50 10 L52 68 L50 92 L48 68 Z" fill="#9CA3AF"/>
            @break

        @case('kompor')
            <rect x="20" y="62" width="60" height="20" rx="4" fill="#4B5563"/>
            <rect x="26" y="68" width="12" height="8" rx="2" fill="#1F2937"/>
            <rect x="62" y="68" width="12" height="8" rx="2" fill="#1F2937"/>
            <circle cx="50" cy="46" r="20" fill="#9CA3AF"/>
            <path d="M50 30c-3 8-10 10-10 18a10 10 0 0 0 20 0c0-8-7-10-10-18Z" fill="#F59E0B"/>
            <path d="M50 36c-2 5-6 7-6 12a6 6 0 0 0 12 0c0-5-4-7-6-12Z" fill="#EF4444"/>
            @break

        @case('semaphore-box')
            <rect x="18" y="38" width="64" height="42" rx="5" fill="#92400E"/>
            <rect x="18" y="38" width="64" height="14" rx="5" fill="#B45309"/>
            <rect x="44" y="48" width="12" height="10" rx="2" fill="#F3F4F6"/>
            @break

        @case('semaphore-flag')
            <line x1="30" y1="90" x2="60" y2="14" stroke="#57534E" stroke-width="4" stroke-linecap="round"/>
            <path d="M60 14 L94 30 L60 40 Z" fill="#EF4444"/>
            <path d="M60 14 L60 40 L40 34 Z" fill="#F3F4F6" stroke="#EF4444" stroke-width="2"/>
            <line x1="70" y1="92" x2="42" y2="30" stroke="#57534E" stroke-width="4" stroke-linecap="round"/>
            <path d="M42 30 L10 42 L42 54 Z" fill="#F59E0B"/>
            <path d="M42 30 L42 54 L60 46 Z" fill="#F3F4F6" stroke="#F59E0B" stroke-width="2"/>
            @break

        @case('tongkat')
            <line x1="20" y1="88" x2="80" y2="12" stroke="#B45309" stroke-width="7" stroke-linecap="round"/>
            <line x1="20" y1="88" x2="80" y2="12" stroke="#92400E" stroke-width="2" stroke-linecap="round" opacity="0.6"/>
            @break

        @case('sarung-tangan')
            <path d="M32 90 V48 a14 14 0 0 1 28 0 V90 Z" fill="#8B5CF6"/>
            <path d="M28 60 a7 7 0 0 1 14 0 v20 h-14 Z" fill="#7C3AED"/>
            <rect x="28" y="82" width="24" height="9" rx="3" fill="#6D28D9"/>
            @break

        @case('topi-boni')
            <ellipse cx="50" cy="72" rx="42" ry="9" fill="#111111"/>
            <path d="M18 70 a32 30 0 0 1 64 0 Z" fill="#F59E0B"/>
            <circle cx="50" cy="40" r="5" fill="#111111"/>
            @break

        @case('topi-rimba')
            <ellipse cx="50" cy="60" rx="44" ry="11" fill="#78716C"/>
            <path d="M26 60 a24 26 0 0 1 48 0 Z" fill="#A8734A"/>
            <rect x="30" y="52" width="40" height="7" rx="3" fill="#57534E"/>
            @break

        @case('baret')
            <path d="M12 62 a38 26 0 0 1 76 0 c-10 8-66 8-76 0Z" fill="#1F2937"/>
            <circle cx="66" cy="30" r="5" fill="#1F2937"/>
            <path d="M64 34 q10 4 8 16" fill="none" stroke="#1F2937" stroke-width="3" stroke-linecap="round"/>
            @break

        @case('baju')
            <path d="M30 20 L42 14 L50 22 L58 14 L70 20 L82 36 L70 46 L64 40 V88 H36 V40 L30 46 L18 36 Z" fill="#3B82F6"/>
            <path d="M42 14 L50 22 L58 14" fill="none" stroke="#1E3A8A" stroke-width="2.5"/>
            @break

        @case('celana')
            <path d="M28 14 H72 L76 90 H58 L50 46 L42 90 H24 Z" fill="#374151"/>
            <line x1="50" y1="20" x2="50" y2="44" stroke="#111827" stroke-width="2" opacity="0.5"/>
            @break

        @case('rompi')
            <path d="M32 18 L50 26 L68 18 L78 34 L68 42 V88 H56 L50 60 L44 88 H32 V42 L22 34 Z" fill="#57534E"/>
            <path d="M50 26 L50 88" stroke="#F59E0B" stroke-width="2" opacity="0.4"/>
            @break

        @case('sampur')
            <path d="M22 12 L40 12 L78 84 L60 90 Z" fill="#3B82F6"/>
            <path d="M22 12 L40 12 L44 22 L26 22 Z" fill="#1D4ED8"/>
            @break

        @case('kopel')
            <rect x="10" y="42" width="80" height="16" rx="4" fill="#78716C"/>
            <rect x="36" y="34" width="28" height="32" rx="4" fill="#F59E0B" stroke="#92400E" stroke-width="2"/>
            <circle cx="50" cy="50" r="5" fill="#92400E"/>
            @break

        @case('tali')
            <path d="M50 15 a20 20 0 1 1 -0.1 0" fill="none" stroke="#92400E" stroke-width="7" stroke-linecap="round"/>
            <path d="M50 30 a13 13 0 1 1 -0.1 0" fill="none" stroke="#B45309" stroke-width="6" stroke-linecap="round"/>
            <path d="M50 44 a7 7 0 1 1 -0.1 0" fill="none" stroke="#F59E0B" stroke-width="5" stroke-linecap="round"/>
            <line x1="66" y1="60" x2="80" y2="90" stroke="#92400E" stroke-width="6" stroke-linecap="round"/>
            @break

        @case('kolongan')
            <circle cx="50" cy="50" r="28" fill="none" stroke="#F59E0B" stroke-width="9"/>
            <circle cx="50" cy="50" r="28" fill="none" stroke="#92400E" stroke-width="2" opacity="0.5"/>
            @break

        @case('ikat-pinggang')
            <rect x="6" y="44" width="88" height="14" rx="3" fill="#374151"/>
            <rect x="40" y="38" width="20" height="26" rx="3" fill="#9CA3AF" stroke="#4B5563" stroke-width="2"/>
            <circle cx="50" cy="51" r="3.5" fill="#4B5563"/>
            @break

        @case('dek')
            <path d="M20 30 L50 50 L20 70" fill="none" stroke="#F59E0B" stroke-width="9" stroke-linecap="round" stroke-linejoin="round"/>
            <path d="M50 30 L80 50 L50 70" fill="none" stroke="#3B82F6" stroke-width="9" stroke-linecap="round" stroke-linejoin="round"/>
            @break

        @case('pin')
            <circle cx="50" cy="50" r="34" fill="#EC4899"/>
            <circle cx="50" cy="50" r="34" fill="none" stroke="#111111" stroke-width="3"/>
            <path d="M50 30 L56 44 L71 44 L59 53 L64 68 L50 59 L36 68 L41 53 L29 44 L44 44 Z" fill="#FDE68A"/>
            @break

        @case('hasduk')
            <path d="M10 20 H90 L50 78 Z" fill="#EF4444"/>
            <path d="M10 20 H90 L82 32 H18 Z" fill="#B91C1C"/>
            @break

        @case('ban-lengan')
            <rect x="30" y="12" width="24" height="76" rx="12" fill="#FDE9D9"/>
            <rect x="24" y="38" width="36" height="24" rx="4" fill="#EF4444"/>
            <rect x="24" y="44" width="36" height="4" fill="#F3F4F6" opacity="0.8"/>
            <rect x="24" y="52" width="36" height="4" fill="#F3F4F6" opacity="0.8"/>
            @break

        @case('scraf')
            <rect x="14" y="30" width="72" height="40" rx="4" fill="#EF4444" transform="rotate(-4 50 50)"/>
            <rect x="14" y="30" width="72" height="8" fill="#F3F4F6" opacity="0.85" transform="rotate(-4 50 50)"/>
            <rect x="14" y="62" width="72" height="8" fill="#F3F4F6" opacity="0.85" transform="rotate(-4 50 50)"/>
            @break

        @case('wosm')
            <circle cx="50" cy="50" r="34" fill="#3B82F6"/>
            <circle cx="50" cy="50" r="34" fill="none" stroke="#111111" stroke-width="3"/>
            <path d="M50 26c9 6 9 16 0 22 9 6 9 16 0 22-9-6-9-16 0-22-9-6-9-16 0-22Z" fill="#FDE68A"/>
            <circle cx="50" cy="50" r="4" fill="#111111"/>
            @break

        @case('cikal')
            <circle cx="50" cy="50" r="34" fill="#10B981"/>
            <circle cx="50" cy="50" r="34" fill="none" stroke="#111111" stroke-width="3"/>
            <path d="M50 72 V42" stroke="#fff" stroke-width="4" stroke-linecap="round"/>
            <path d="M50 42c-14 0-18-14-18-14s14-2 18 10c4-12 18-10 18-10s-4 14-18 14Z" fill="#fff"/>
            @break

        @case('bendera')
            <line x1="26" y1="90" x2="26" y2="10" stroke="#57534E" stroke-width="4" stroke-linecap="round"/>
            <path d="M26 14 H78 L64 30 L78 46 H26 Z" fill="#F3F4F6" stroke="#EF4444" stroke-width="2"/>
            <rect x="26" y="14" width="26" height="16" fill="#EF4444"/>
            @break

        @default
            <rect x="18" y="18" width="64" height="64" rx="10" fill="#E5E7EB"/>
            <path d="M50 30 L72 42 V64 L50 76 L28 64 V42 Z" fill="none" stroke="#9CA3AF" stroke-width="3"/>
    @endswitch

    <defs>
        <linearGradient id="matrasShade" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="#000"/>
            <stop offset="1" stop-color="#000" stop-opacity="0"/>
        </linearGradient>
    </defs>
</svg>
