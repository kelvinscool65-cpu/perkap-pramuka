@props(['teks', 'huruf' => true])
{{-- Sandi morse dari $teks: titik = bulat kecil, garis = kapsul. Warna mengikuti `currentColor`
     (atur lewat class text-*). $huruf=true menaruh huruf aslinya kecil di bawah tiap kelompok,
     sehingga siapa pun bisa mencocokkan kartu dengan ID-nya. --}}
@php
    $deret = \App\Support\SandiMorse::untuk($teks);
    $bacaan = collect($deret)->map(fn ($d) => $d['huruf'].' '.str_replace(['.', '-'], ['titik', 'garis'], $d['sandi']))->implode(', ');
@endphp
<div {{ $attributes->merge(['class' => 'flex flex-wrap items-start gap-x-3 gap-y-2']) }} role="img" aria-label="Sandi morse untuk {{ $teks }}: {{ $bacaan }}">
    @foreach($deret as $d)
        <div class="flex flex-col items-center gap-1" aria-hidden="true">
            <div class="flex h-2 items-center gap-[3px]">
                @foreach(str_split($d['sandi']) as $simbol)
                    <span @class(['h-1.5 rounded-full bg-current', 'w-1.5' => $simbol === '.', 'w-3.5' => $simbol === '-'])></span>
                @endforeach
            </div>
            @if($huruf)
                <span class="font-mono text-[10px] leading-none opacity-60">{{ $d['huruf'] }}</span>
            @endif
        </div>
    @endforeach
</div>
