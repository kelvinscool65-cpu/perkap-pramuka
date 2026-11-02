<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Catatan pelanggaran seorang peminjam. Dicatat manual oleh petugas dari halaman detail pengguna.
 * Jumlah pelanggaran "penipuan" menjadi dasar saran pemblokiran (config perkap.batas_penipuan).
 */
class Pelanggaran extends Model
{
    use HasFactory;

    protected $table = 'pelanggaran';

    protected $fillable = [
        'user_id', 'pengajuan_id', 'jenis', 'keterangan', 'dicatat_oleh',
    ];

    /** Jenis pelanggaran => [label, warna badge, penjelasan singkat untuk petugas] */
    public const JENIS = [
        'penipuan' => ['Penipuan peminjaman', 'danger', 'Barang tidak dikembalikan, data/identitas palsu, atau meminjam atas nama orang lain.'],
        'kehilangan' => ['Barang hilang', 'rose', 'Barang hilang dan tidak diganti.'],
        'kerusakan' => ['Barang rusak', 'warning', 'Barang dikembalikan dalam kondisi rusak karena kelalaian.'],
        'terlambat' => ['Terlambat mengembalikan', 'info', 'Melewati tanggal pengembalian tanpa kabar.'],
        'lainnya' => ['Lainnya', 'surface', 'Pelanggaran tata tertib lain.'],
    ];

    public static function daftarJenis(): array
    {
        return array_keys(self::JENIS);
    }

    public function getLabelJenisAttribute(): string
    {
        return self::JENIS[$this->jenis][0] ?? ucfirst($this->jenis);
    }

    public function getWarnaJenisAttribute(): string
    {
        return self::JENIS[$this->jenis][1] ?? 'surface';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function pengajuan(): BelongsTo
    {
        return $this->belongsTo(PengajuanPeminjaman::class, 'pengajuan_id')->withTrashed();
    }

    public function pencatat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dicatat_oleh')->withTrashed();
    }
}
