<?php

namespace App\Support;

/**
 * Sandi morse — salah satu sandi yang dipelajari dalam Pramuka.
 * Dipakai untuk "tanda tangan visual" pada Kartu Anggota: bagian acak ID Anggota diubah menjadi
 * titik-garis yang BENAR-BENAR dapat dibaca kembali, jadi setiap kartu tampak berbeda karena
 * isinya memang berbeda, bukan sekadar hiasan.
 */
final class SandiMorse
{
    private const TABEL = [
        'A' => '.-', 'B' => '-...', 'C' => '-.-.', 'D' => '-..', 'E' => '.', 'F' => '..-.',
        'G' => '--.', 'H' => '....', 'I' => '..', 'J' => '.---', 'K' => '-.-', 'L' => '.-..',
        'M' => '--', 'N' => '-.', 'O' => '---', 'P' => '.--.', 'Q' => '--.-', 'R' => '.-.',
        'S' => '...', 'T' => '-', 'U' => '..-', 'V' => '...-', 'W' => '.--', 'X' => '-..-',
        'Y' => '-.--', 'Z' => '--..',
        '0' => '-----', '1' => '.----', '2' => '..---', '3' => '...--', '4' => '....-',
        '5' => '.....', '6' => '-....', '7' => '--...', '8' => '---..', '9' => '----.',
    ];

    /**
     * @return list<array{huruf:string, sandi:string}>  mis. [['huruf' => 'K', 'sandi' => '-.-'], …]
     */
    public static function untuk(?string $teks): array
    {
        $hasil = [];

        foreach (str_split(strtoupper((string) $teks)) as $karakter) {
            if (isset(self::TABEL[$karakter])) {
                $hasil[] = ['huruf' => $karakter, 'sandi' => self::TABEL[$karakter]];
            }
        }

        return $hasil;
    }
}
