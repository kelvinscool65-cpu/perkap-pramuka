<?php

namespace App\Rules;

use App\Models\User;
use App\Support\Identitas;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Memastikan email / nomor HP belum dipakai akun lain — dibandingkan dalam BENTUK BAKU
 * (lihat App\Support\Identitas), jadi "0812…" = "+62 812…" dan "a.b@gmail.com" = "ab@gmail.com".
 *
 * Akun yang sudah dihapus admin ikut dihitung (withTrashed): identitas akun bermasalah tetap
 * "terpesan" sehingga tidak bisa dipakai mendaftar ulang.
 */
class IdentitasBelumDipakai implements ValidationRule
{
    /** @param 'email'|'no_hp' $jenis */
    public function __construct(
        private readonly string $jenis,
        private readonly ?int $abaikanId = null,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $nilai = trim((string) $value);

        $query = User::withTrashed()->when($this->abaikanId, fn ($q) => $q->where('id', '!=', $this->abaikanId));

        if ($this->jenis === 'email') {
            $kanonik = Identitas::kanonikEmail($nilai);
            $ada = $query->where(fn ($q) => $q->where('email', strtolower($nilai))->orWhere('email_kanonik', $kanonik))->exists();

            if ($ada) {
                $fail('Email ini sudah terdaftar pada akun lain. Gunakan email yang berbeda, atau masuk bila itu akun Anda.');
            }

            return;
        }

        $baku = Identitas::normalisasiNoHp($nilai);
        if ($baku !== null && $query->where('no_hp_normal', $baku)->exists()) {
            $fail('Nomor WhatsApp ini sudah terdaftar pada akun lain. Setiap akun harus memakai nomor yang berbeda.');
        }
    }
}
