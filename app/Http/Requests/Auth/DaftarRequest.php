<?php

namespace App\Http\Requests\Auth;

use App\Rules\IdentitasBelumDipakai;
use App\Rules\NoHpSeluler;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Validasi pendaftaran akun peminjam.
 *
 * Keunikan diperiksa pada BENTUK BAKU (huruf kecil, nomor HP "62…", Gmail tanpa titik/+label) dan
 * mencakup akun yang sudah dihapus admin — lihat App\Rules\IdentitasBelumDipakai.
 */
class DaftarRequest extends FormRequest
{
    /** Username yang meniru jabatan/sistem tidak boleh dipakai pendaftar. */
    private const USERNAME_DILARANG = [
        'admin', 'administrator', 'petugas', 'perkap', 'pramuka', 'root', 'system', 'sistem',
        'support', 'moderator', 'owner', 'superuser', 'staff', 'null', 'undefined',
    ];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nama' => preg_replace('/\s+/u', ' ', trim((string) $this->input('nama'))),
            'username' => strtolower(trim((string) $this->input('username'))),
            'email' => strtolower(trim((string) $this->input('email'))),
            'no_hp' => trim((string) $this->input('no_hp')),
        ]);
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'min:3', 'max:100', "regex:/^[\\pL\\pM][\\pL\\pM\\s.'\\-]*$/u"],
            'username' => [
                'required', 'string', 'min:4', 'max:30', 'alpha_dash:ascii',
                Rule::notIn(self::USERNAME_DILARANG),
                Rule::unique('users', 'username'),
            ],
            'email' => ['required', 'string', 'email:rfc', 'max:150', new IdentitasBelumDipakai('email')],
            'no_hp' => ['required', 'string', 'max:20', new NoHpSeluler, new IdentitasBelumDipakai('no_hp')],
            'password' => ['required', 'confirmed', Password::defaults()],
            'setuju' => ['accepted'],
            // Jebakan bot: kolom ini disembunyikan dari manusia; bot yang mengisi semua kolom akan ketahuan.
            'website' => ['prohibited'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nama' => 'nama lengkap',
            'no_hp' => 'nomor WhatsApp',
            'password_confirmation' => 'konfirmasi password',
        ];
    }

    public function messages(): array
    {
        return [
            'nama.regex' => 'Nama hanya boleh berisi huruf, spasi, titik, apostrof, dan tanda hubung.',
            'username.not_in' => 'Username ini tidak tersedia. Pilih username lain.',
            'username.unique' => 'Username sudah dipakai. Pilih yang lain.',
            'username.alpha_dash' => 'Username hanya boleh berisi huruf, angka, strip (-), dan garis bawah (_).',
            'password.confirmed' => 'Konfirmasi password tidak sama dengan password.',
            'setuju.accepted' => 'Anda harus menyetujui tata tertib peminjaman untuk membuat akun.',
            'website.prohibited' => 'Permintaan tidak valid.',
        ];
    }
}
