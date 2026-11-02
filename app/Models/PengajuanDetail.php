<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengajuanDetail extends Model
{
    use HasFactory;

    protected $table = 'pengajuan_detail';

    protected $fillable = [
        'pengajuan_id', 'alat_id', 'jumlah', 'harga_saat_pinjam', 'kondisi_saat_kembali',
    ];

    protected $casts = [
        'jumlah' => 'integer',
        'harga_saat_pinjam' => 'integer',
    ];

    public function pengajuan()
    {
        return $this->belongsTo(PengajuanPeminjaman::class, 'pengajuan_id');
    }

    public function alat()
    {
        return $this->belongsTo(Alat::class, 'alat_id')->withTrashed();
    }

    /** Subtotal biaya item ini = harga yg di-snapshot saat pinjam × jumlah */
    public function getSubtotalAttribute(): int
    {
        return $this->harga_saat_pinjam * $this->jumlah;
    }

    public function getSubtotalFormatAttribute(): string
    {
        return 'Rp '.number_format($this->subtotal, 0, ',', '.');
    }
}
