<?php

namespace App\Http\Requests;

use App\Models\Alat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAlatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'nama_alat' => ['required', 'string', 'max:150'],
            'kategori' => ['required', Rule::in(Alat::KATEGORI)],
            'deskripsi' => ['nullable', 'string', 'max:1000'],
            'satuan' => ['required', 'string', 'max:20'],
            'stok_total' => ['required', 'integer', 'min:0'],
            'harga_sewa' => ['required', 'integer', 'min:0'],
            'kondisi' => ['required', Rule::in(['good', 'damaged', 'lost'])],
            'status' => ['required', Rule::in(['tersedia', 'dipinjam', 'perawatan'])],
            'gambar' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
