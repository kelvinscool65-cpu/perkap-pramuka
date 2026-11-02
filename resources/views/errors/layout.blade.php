<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('judul') &mdash; Perkap Pramuka SMKN 5 Surakarta</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    {{-- Halaman ini sengaja berdiri sendiri (CSS inline, tanpa Vite/session/database) karena
         muncul justru saat aplikasi belum siap dipakai. --}}
    <style>
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px;
             font-family:"Plus Jakarta Sans",ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;
             color:#111;background:radial-gradient(1200px 500px at 15% -10%,rgba(139,92,246,.18),transparent),
             radial-gradient(900px 500px at 100% 110%,rgba(59,130,246,.14),transparent),#f6f5fb}
        .kartu{width:100%;max-width:560px;background:#fff;border-radius:24px;padding:32px;
               box-shadow:0 20px 50px -20px rgba(76,29,149,.35);border:1px solid rgba(17,17,17,.05)}
        .ikon{width:52px;height:52px;border-radius:16px;display:flex;align-items:center;justify-content:center;
              background:linear-gradient(135deg,#8B5CF6,#6D28D9);color:#fff;margin-bottom:18px}
        h1{font-size:22px;line-height:1.25;margin:0 0 8px}
        p{margin:0 0 14px;color:rgba(17,17,17,.65);font-size:14px;line-height:1.6}
        ol{margin:0 0 18px;padding-left:20px;font-size:14px;line-height:1.7}
        li{margin-bottom:6px}
        code,pre{font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace}
        code{background:#f3f0ff;color:#5b21b6;padding:2px 7px;border-radius:7px;font-size:13px;font-weight:600}
        pre{background:#111;color:#e5e7eb;border-radius:14px;padding:14px 16px;font-size:12.5px;overflow:auto;margin:0 0 18px}
        .daftar{background:#f9fafb;border-radius:14px;padding:12px 14px;margin:0 0 18px;font-size:12.5px;
                color:rgba(17,17,17,.7);max-height:150px;overflow:auto}
        .daftar div{padding:2px 0;font-family:ui-monospace,Menlo,Consolas,monospace}
        .tombol{display:inline-flex;align-items:center;gap:8px;border:0;cursor:pointer;text-decoration:none;
                background:linear-gradient(90deg,#8B5CF6,#7C3AED);color:#fff;font-weight:600;font-size:13px;
                padding:10px 20px;border-radius:999px;box-shadow:0 8px 20px -8px rgba(139,92,246,.7)}
        .catatan{margin-top:18px;font-size:12px;color:rgba(17,17,17,.45)}
        .detail{background:#fef2f2;color:#b91c1c;border-radius:12px;padding:10px 12px;font-size:12px;margin:0 0 18px;word-break:break-word}
    </style>
</head>
<body>
    <main class="kartu">
        <div class="ikon">
            {{-- Ikon bawaan = database (halaman migrasi/database). Halaman lain mengisi section "ikon". --}}
            @hasSection('ikon')
                @yield('ikon')
            @else
                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><ellipse cx="12" cy="5.5" rx="8" ry="3"/><path d="M4 5.5v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6"/><path d="M4 11.5v6c0 1.7 3.6 3 8 3s8-1.3 8-3v-6"/></svg>
            @endif
        </div>
        @yield('isi')
        @hasSection('catatan')
            <p class="catatan">Perkap Pramuka SMK Negeri 5 Surakarta &middot; @yield('catatan')</p>
        @endif
    </main>
</body>
</html>
