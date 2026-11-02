<?php

namespace App\Exceptions;

use LogicException;

/**
 * Dilempar bila ada kode yang mencoba mengubah ID Anggota yang sudah terisi.
 * ID Anggota adalah identitas permanen akun; satu-satunya "perubahan" yang sah hanyalah
 * pengisian pertama (saat akun dibuat / pengisian otomatis oleh migrasi).
 */
class IdentitasTidakBolehDiubah extends LogicException
{
    public function __construct(string $message = 'ID Anggota bersifat permanen dan tidak dapat diubah.')
    {
        parent::__construct($message);
    }
}
