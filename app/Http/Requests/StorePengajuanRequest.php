<?php

namespace App\Http\Requests;

use App\Models\Alat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePengajuanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isPeminjam() ?? false;
    }

    public function rules(): array
    {
        return [
            'tanggal_pinjam' => ['required', 'date', 'after_or_equal:today'],
            'tanggal_kembali' => ['required', 'date', 'after_or_equal:tanggal_pinjam'],
            'keperluan' => ['required', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.alat_id' => ['required', 'integer', 'exists:alats,id'],
            'items.*.jumlah' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Keranjang peminjaman masih kosong, silakan pilih alat dari katalog dulu.',
            'tanggal_kembali.after_or_equal' => 'Tanggal kembali tidak boleh sebelum tanggal pinjam.',
        ];
    }

    /**
     * Validasi stok otomatis: memastikan jumlah yang diajukan tidak melebihi
     * stok_tersedia saat ini (checklist brief: "Validasi stok otomatis").
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            foreach ($this->input('items', []) as $index => $item) {
                $alat = Alat::find($item['alat_id'] ?? null);
                if ($alat && (int) ($item['jumlah'] ?? 0) > $alat->stok_tersedia) {
                    $validator->errors()->add(
                        "items.{$index}.jumlah",
                        "Stok {$alat->nama_alat} tersisa {$alat->stok_tersedia}, tidak mencukupi."
                    );
                }
            }
        });
    }
}
