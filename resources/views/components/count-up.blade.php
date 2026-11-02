@props(['angka', 'class' => ''])
{{-- Animasi angka menghitung naik dari 0 saat kartu pertama terlihat di layar.
     Dipakai di semua kartu statistik supaya dashboard terasa lebih hidup. --}}
<span
    {{ $attributes->merge(['class' => $class]) }}
    x-data="{ tampil: 0 }"
    x-init="
        const target = {{ (int) $angka }};
        const mulai = performance.now();
        const durasi = 700 + Math.min(target * 15, 500);
        const animasikan = (now) => {
            const progres = Math.min((now - mulai) / durasi, 1);
            tampil = Math.floor((1 - Math.pow(1 - progres, 3)) * target);
            if (progres < 1) requestAnimationFrame(animasikan);
        };
        requestAnimationFrame(animasikan);
    "
    x-text="tampil.toLocaleString('id-ID')"
>{{ $angka }}</span>
