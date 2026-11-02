<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PengajuanPeminjaman extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pengajuan_peminjaman';

    protected $fillable = [
        'kode_pengajuan', 'user_id', 'petugas_id', 'tanggal_pinjam', 'tanggal_kembali',
        'keperluan', 'status_pengajuan', 'status_akhir', 'catatan_admin',
        'catatan_pengembalian', 'approved_at', 'returned_at',
    ];

    protected $casts = [
        'tanggal_pinjam' => 'date',
        'tanggal_kembali' => 'date',
        'approved_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    /** Generate kode_pengajuan otomatis format PJM-YYYYMMDD-XXX saat record dibuat */
    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $pengajuan) {
            if (empty($pengajuan->kode_pengajuan)) {
                $tanggal = now()->format('Ymd');
                $urutan = self::withTrashed()->whereDate('created_at', now())->count() + 1;
                $pengajuan->kode_pengajuan = "PJM-{$tanggal}-".str_pad((string) $urutan, 3, '0', STR_PAD_LEFT);
            }
        });
    }

    // ==== Relasi ====

    /**
     * withTrashed(): akun peminjam bisa di-soft-delete oleh admin (fitur kelola pengguna),
     * tetapi riwayat pengajuannya HARUS tetap tampil lengkap dengan nama pemiliknya.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function petugas()
    {
        return $this->belongsTo(User::class, 'petugas_id')->withTrashed();
    }

    public function details()
    {
        return $this->hasMany(PengajuanDetail::class, 'pengajuan_id');
    }

    /** Relasi many-to-many ke Alat melalui pivot pengajuan_detail (sesuai brief) */
    public function alats()
    {
        return $this->belongsToMany(Alat::class, 'pengajuan_detail', 'pengajuan_id', 'alat_id')
            ->withPivot(['id', 'jumlah', 'harga_saat_pinjam', 'kondisi_saat_kembali'])
            ->withTimestamps();
    }

    // ==== Scopes ====

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return $query->when($status, fn ($q) => $q->where('status_pengajuan', $status));
    }

    public function scopeRentangTanggal(Builder $query, ?string $dari, ?string $sampai): Builder
    {
        return $query
            ->when($dari, fn ($q) => $q->whereDate('tanggal_pinjam', '>=', $dari))
            ->when($sampai, fn ($q) => $q->whereDate('tanggal_pinjam', '<=', $sampai));
    }

    // ==== Accessors untuk badge status di Blade ====

    public function getWarnaStatusAttribute(): string
    {
        return match ($this->status_pengajuan) {
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'returned' => 'accent',
            default => 'surface',
        };
    }

    public function getLabelStatusAttribute(): string
    {
        return match ($this->status_pengajuan) {
            'pending' => 'Menunggu',
            'approved' => 'Disetujui',
            'rejected' => 'Ditolak',
            'returned' => 'Selesai',
            default => $this->status_pengajuan,
        };
    }

    /** Total biaya sewa seluruh item (dari harga yg di-snapshot saat pengajuan dibuat) */
    public function getTotalBiayaAttribute(): int
    {
        return $this->details->sum('subtotal');
    }

    public function getTotalBiayaFormatAttribute(): string
    {
        return 'Rp '.number_format($this->total_biaya, 0, ',', '.');
    }
}
