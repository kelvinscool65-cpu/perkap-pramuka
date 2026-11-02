<?php

namespace App\Rules;

use App\Support\Identitas;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Nomor WhatsApp harus nomor seluler Indonesia yang masuk akal (08… / +62 8…). */
class NoHpSeluler implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! Identitas::noHpValid((string) $value)) {
            $fail('Nomor WhatsApp tidak valid. Gunakan nomor seluler Indonesia, mis. 0812 3456 7890.');
        }
    }
}
