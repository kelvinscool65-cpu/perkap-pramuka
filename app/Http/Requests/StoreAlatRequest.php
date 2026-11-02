<?php

namespace App\Http\Requests;

use App\Models\Alat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAlatRequest extends FormRequest
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
            'stok_total' => ['required', 'integer', 'min:1'],
            'harga_sewa' => ['required', 'integer', 'min:0'],
            'kondisi' => ['required', Rule::in(['good', 'damaged', 'lost'])],
            'gambar' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'nama_alat.required' => 'Nama alat wajib diisi.',
            'kategori.required' => 'Kategori wajib dipilih.',
            'stok_total.min' => 'Stok total minimal 1.',
            'harga_sewa.required' => 'Harga sewa wajib diisi (boleh 0 jika gratis).',
            'gambar.image' => 'File harus berupa gambar.',
            'gambar.max' => 'Ukuran gambar maksimal 2MB.',
        ];
    }
}
