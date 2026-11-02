@props(['name', 'class' => 'w-5 h-5'])
{{-- Komponen ikon terpusat, gaya outline presisi (setara Heroicons/Lucide).
     Dipanggil: <x-icon name="search" class="w-4 h-4"/> --}}
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
     stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" {{ $attributes->merge(['class' => $class]) }}>
    @switch($name)
        @case('search')
            <circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>
            @break

        @case('bell')
            <path d="M6 9a6 6 0 1 1 12 0c0 3.2 1 5.3 1.8 6.4a1 1 0 0 1-.8 1.6H5a1 1 0 0 1-.8-1.6C5 14.3 6 12.2 6 9Z"/>
            <path d="M9.5 20a2.5 2.5 0 0 0 5 0"/>
            @break

        @case('menu')
            <path d="M4 7h16M4 12h16M4 17h11"/>
            @break

        @case('chevron-down')
            <path d="m6 9 6 6 6-6"/>
            @break

        @case('x')
            <path d="M18 6 6 18M6 6l12 12"/>
            @break

        @case('eye')
            <path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12Z"/>
            <circle cx="12" cy="12" r="3.25"/>
            <circle cx="13.1" cy="10.9" r="0.9" fill="currentColor" stroke="none"/>
            @break

        @case('eye-off')
            <path d="M3.5 3.5 20.5 20.5"/>
            <path d="M10.7 5.6A10.4 10.4 0 0 1 12 5.5c6 0 9.5 6.5 9.5 6.5a15.6 15.6 0 0 1-3.14 4.06M6.5 6.66C4 8.3 2.5 12 2.5 12s3.5 6.5 9.5 6.5a9.9 9.9 0 0 0 3.16-.52"/>
            <path d="M9.5 12a2.5 2.5 0 0 0 3.53 2.28M14.32 14.32A2.5 2.5 0 0 0 11 10.68"/>
            @break

        @case('logout')
            <path d="M9 21H6.75A2.75 2.75 0 0 1 4 18.25V5.75A2.75 2.75 0 0 1 6.75 3H9"/>
            <path d="m14.5 16 4-4-4-4"/>
            <path d="M18.25 12h-9.5"/>
            @break

        @case('home')
            <path d="M4 10.8 12 4l8 6.8"/>
            <path d="M6 9.5V19a1 1 0 0 0 1 1h3.5v-5.5a1 1 0 0 1 1-1h1a1 1 0 0 1 1 1V20H17a1 1 0 0 0 1-1V9.5"/>
            @break

        @case('box')
            <path d="M12 3.25 4.5 7.4v9.2l7.5 4.15 7.5-4.15V7.4L12 3.25Z"/>
            <path d="M4.5 7.4 12 11.5l7.5-4.1M12 11.5v9.25"/>
            @break

        @case('clipboard')
            <rect x="5.25" y="4.25" width="13.5" height="16.5" rx="2.25"/>
            <path d="M9 4.25V3.6A1.35 1.35 0 0 1 10.35 2.25h3.3A1.35 1.35 0 0 1 15 3.6v.65"/>
            <path d="M8.75 11h6.5M8.75 14.5h6.5M8.75 18h3.75"/>
            @break

        @case('chart')
            <path d="M4 20V10.5M4 20h16M4 20V4"/>
            <rect x="8" y="13" width="2.6" height="7" rx="0.6"/>
            <rect x="13" y="8.5" width="2.6" height="11.5" rx="0.6"/>
            <rect x="18" y="11" width="2.6" height="9" rx="0.6"/>
            @break

        @case('user')
            <circle cx="12" cy="8.25" r="3.5"/>
            <path d="M4.75 20a7.25 7.25 0 0 1 14.5 0"/>
            @break

        @case('moon')
            <path d="M20.5 14.2a8.7 8.7 0 1 1-10.7-10.7 7 7 0 0 0 10.7 10.7Z"/>
            @break

        @case('sun')
            <circle cx="12" cy="12" r="3.75"/>
            <path d="M12 2.75v2M12 19.25v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M2.75 12h2M19.25 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>
            @break

        @case('plus')
            <path d="M12 5v14M5 12h14"/>
            @break

        @case('trash')
            <path d="M4.5 6.5h15"/>
            <path d="M8.5 6.5V4.75a1.25 1.25 0 0 1 1.25-1.25h4.5a1.25 1.25 0 0 1 1.25 1.25V6.5"/>
            <path d="M18 6.5 17.2 19a2 2 0 0 1-2 1.85H8.8a2 2 0 0 1-2-1.85L6 6.5"/>
            <path d="M10.25 10.5v6M13.75 10.5v6"/>
            @break

        @case('pencil')
            <path d="M4.5 19.5 5 16.75 15.9 5.85a1.75 1.75 0 0 1 2.47 0l.78.78a1.75 1.75 0 0 1 0 2.47L8.25 19l-3.75.5Z"/>
            <path d="M14.25 7.5 16.5 9.75"/>
            @break

        @case('check-circle')
            <circle cx="12" cy="12" r="9"/>
            <path d="m8.25 12.5 2.4 2.4 5.1-5.4"/>
            @break

        @case('alert')
            <path d="M10.6 4.1 2.9 17.5a1.6 1.6 0 0 0 1.38 2.4h15.44a1.6 1.6 0 0 0 1.39-2.4L13.4 4.1a1.6 1.6 0 0 0-2.8 0Z"/>
            <path d="M12 9.75v3.75"/>
            <path d="M12 16.5h.01" stroke-width="2.4"/>
            @break

        @case('cart')
            <path d="M8.25 9.5V6.75a3.75 3.75 0 1 1 7.5 0V9.5"/>
            <path d="M5.87 9.5h12.26l1.06 10.15a1.5 1.5 0 0 1-1.49 1.6H6.3a1.5 1.5 0 0 1-1.49-1.6L5.87 9.5Z"/>
            @break

        @case('arrow-left')
            <path d="M19 12H5M11 18l-6-6 6-6"/>
            @break

        @case('calendar')
            <rect x="3.5" y="5" width="17" height="15.5" rx="2.25"/>
            <path d="M3.5 9.75h17M8 3v3.5M16 3v3.5"/>
            @break

        @case('download')
            <path d="M12 3.5v11.25M8 11.25l4 3.75 4-3.75"/>
            <path d="M4.5 16.5v2.25A2.25 2.25 0 0 0 6.75 21h10.5a2.25 2.25 0 0 0 2.25-2.25V16.5"/>
            @break

        @case('whatsapp')
            <path d="M6.6 17.4 4.5 19.5l.62-3.2A7.75 7.75 0 1 1 8.5 19l-1.9-1.6Z"/>
            <path d="M8.9 8.6c.15-.4.3-.4.5-.4h.4c.15 0 .3 0 .45.35.15.35.55 1.3.6 1.4.05.1.08.2.02.35-.06.15-.1.25-.2.38-.1.13-.2.28-.3.38-.1.1-.2.2-.08.42.12.2.55.95 1.2 1.55.85.78 1.5 1 1.72 1.1.22.1.35.1.48-.05.13-.15.55-.6.7-.8.15-.2.3-.17.5-.1.2.07 1.3.6 1.5.7.2.1.35.15.4.25.05.1.05.55-.13 1.05-.18.5-1.05 1-1.5 1.05-.4.05-.85.08-3.1-.95-2.6-1.2-4.15-3.75-4.28-3.95-.13-.2-1.05-1.4-1.05-2.7 0-1.3.68-1.9.9-2.15Z" fill="currentColor" stroke="none"/>
            @break

        @case('instagram')
            <rect x="3.25" y="3.25" width="17.5" height="17.5" rx="5"/>
            <circle cx="12" cy="12" r="4.25"/>
            <circle cx="17" cy="7" r="1.1" fill="currentColor" stroke="none"/>
            @break

        @case('tiktok')
            <path d="M14 3.5c.4 2.1 1.7 3.5 4 3.7v2.7c-1.4.05-2.7-.4-4-1.25v6.15a5.15 5.15 0 1 1-4.6-5.12v2.8a2.4 2.4 0 1 0 1.9 2.35V3.5H14Z"/>
            @break

        {{-- ===== Ikon fitur identitas & kelola pengguna ===== --}}
        @case('users')
            <circle cx="9" cy="8.25" r="3.25"/>
            <path d="M2.75 19.5a6.25 6.25 0 0 1 12.5 0"/>
            <path d="M15.4 5.2a3.25 3.25 0 0 1 0 6.1"/>
            <path d="M17.6 14.1a6.25 6.25 0 0 1 3.65 5.4"/>
            @break

        @case('id-card')
            <rect x="2.75" y="5" width="18.5" height="14" rx="2.5"/>
            <circle cx="8.5" cy="10.5" r="2"/>
            <path d="M5.5 16.25c.45-1.5 1.65-2.25 3-2.25s2.55.75 3 2.25"/>
            <path d="M14.5 9.75h4M14.5 13.25h3"/>
            @break

        @case('shield-check')
            <path d="M12 3 4.75 5.75v5.5c0 4.4 3 8 7.25 9.75 4.25-1.75 7.25-5.35 7.25-9.75v-5.5L12 3Z"/>
            <path d="m8.9 12.2 2.2 2.2 4-4.3"/>
            @break

        @case('ban')
            <circle cx="12" cy="12" r="9"/>
            <path d="m5.65 5.65 12.7 12.7"/>
            @break

        @case('lock')
            <rect x="5" y="10.5" width="14" height="10" rx="2.25"/>
            <path d="M8 10.5V8a4 4 0 0 1 8 0v2.5"/>
            <path d="M12 14.5v2"/>
            @break

        @case('unlock')
            <rect x="5" y="10.5" width="14" height="10" rx="2.25"/>
            <path d="M8 10.5V8a4 4 0 0 1 7.6-1.75"/>
            <path d="M12 14.5v2"/>
            @break

        @case('copy')
            <rect x="8.5" y="8.5" width="11.75" height="11.75" rx="2.25"/>
            <path d="M15.5 8.5V6.75a2.5 2.5 0 0 0-2.5-2.5H6.75a2.5 2.5 0 0 0-2.5 2.5V13a2.5 2.5 0 0 0 2.5 2.5H8.5"/>
            @break

        @case('flag')
            <path d="M5.5 21V4.25"/>
            <path d="M5.5 5c2.5-1.5 4.5-1.5 7 0s4.5 1.5 7 0v9c-2.5 1.5-4.5 1.5-7 0s-4.5-1.5-7 0"/>
            @break

        @case('restore')
            <path d="M4 12a8 8 0 1 1 2.4 5.7"/>
            <path d="M4 6v5.5h5.5"/>
            @break

        @case('link')
            <path d="M10 14a4.25 4.25 0 0 0 6 0l3-3a4.25 4.25 0 0 0-6-6l-1 1"/>
            <path d="M14 10a4.25 4.25 0 0 0-6 0l-3 3a4.25 4.25 0 0 0 6 6l1-1"/>
            @break

        @case('info')
            <circle cx="12" cy="12" r="9"/>
            <path d="M12 11v5.25"/>
            <path d="M12 7.9h.01" stroke-width="2.4"/>
            @break

        @case('clock')
            <circle cx="12" cy="12" r="9"/>
            <path d="M12 7v5l3.25 2"/>
            @break

        @case('arrow-right')
            <path d="M5 12h14M13 6l6 6-6 6"/>
            @break

        @case('check')
            <path d="m5 12.5 4.5 4.5L19 7.5"/>
            @break

        @case('help')
            <circle cx="12" cy="12" r="9"/>
            <path d="M9.4 9.4a2.75 2.75 0 1 1 3.9 2.5c-.85.4-1.3 1-1.3 1.85"/>
            <path d="M12 16.9h.01" stroke-width="2.4"/>
            @break
    @endswitch
</svg>
