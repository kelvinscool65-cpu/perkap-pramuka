<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Alat extends Model
{
    use HasFactory, SoftDeletes;

    /** Batas stok tersedia dianggap "menipis" untuk peringatan dashboard */
    const BATAS_STOK_RENDAH = 3;

    /** Daftar kategori baku dipakai di seluruh form & filter */
    const KATEGORI = ['Tenda', 'Peralatan', 'Semaphore', 'Seragam', 'Atribut'];

    /** Prefix kode_alat per kategori, dipakai AlatSeeder & AlatController */
    const PREFIX_KATEGORI = [
        'Tenda' => 'TDA',
        'Peralatan' => 'PRL',
        'Semaphore' => 'SMP',
        'Seragam' => 'SRG',
        'Atribut' => 'ATR',
    ];

    protected $fillable = [
        'kode_alat', 'nama_alat', 'kategori', 'deskripsi', 'satuan',
        'stok_total', 'stok_tersedia', 'harga_sewa', 'kondisi', 'status', 'gambar',
    ];

    protected $casts = [
        'stok_total' => 'integer',
        'stok_tersedia' => 'integer',
        'harga_sewa' => 'integer',
    ];

    public function pengajuanDetail()
    {
        return $this->hasMany(PengajuanDetail::class, 'alat_id');
    }

    // ==== Scopes untuk pencarian & filter katalog/admin ====

    public function scopeCari(Builder $query, ?string $kata): Builder
    {
        return $query->when($kata, function ($q) use ($kata) {
            $q->where(function ($sub) use ($kata) {
                $sub->where('nama_alat', 'like', "%{$kata}%")
                    ->orWhere('kode_alat', 'like', "%{$kata}%");
            });
        });
    }

    public function scopeKategori(Builder $query, ?string $kategori): Builder
    {
        return $query->when($kategori, fn ($q) => $q->where('kategori', $kategori));
    }

    public function scopeKondisiFilter(Builder $query, ?string $kondisi): Builder
    {
        return $query->when($kondisi, fn ($q) => $q->where('kondisi', $kondisi));
    }

    public function scopeStokRendah(Builder $query): Builder
    {
        return $query->where('stok_tersedia', '<=', self::BATAS_STOK_RENDAH);
    }

    // ==== Accessors ====

    /** Persentase ketersediaan (0-100) untuk progress bar hijau di kartu alat */
    public function getPersentaseTersediaAttribute(): int
    {
        if ($this->stok_total <= 0) {
            return 0;
        }

        return (int) round(($this->stok_tersedia / $this->stok_total) * 100);
    }

    public function getGambarUrlAttribute(): string
    {
        return $this->gambar ? asset('storage/'.$this->gambar) : asset('images/placeholder-alat.svg');
    }

    /**
     * Kunci ilustrasi (lihat komponen <x-alat-icon>) yang paling sesuai dengan
     * nama alat ini — dipakai sebagai gambar default selama admin belum
     * upload foto asli. Dicocokkan eksplisit per nama (bukan tebak-tebakan)
     * supaya akurat, dengan fallback berbasis kategori untuk alat baru yang
     * ditambahkan admin di kemudian hari dan belum ada padanan spesifiknya.
     */
    public function getIkonAttribute(): string
    {
        $n = $this->nama_alat;

        return match (true) {
            $n === 'SB' || str_contains($n, 'Tenda Regu') => 'tenda',
            in_array($n, ['Matras Halus', 'Matras Kasar', 'Tiker Hijau', 'Karpet Putih Kuning'], true) => 'matras',
            $n === 'Pasak Tenda Dum' => 'pasak',
            $n === 'Kompor' => 'kompor',
            $n === 'Wadah Semaphore' => 'semaphore-box',
            in_array($n, ['Semaphore Lama', 'Semaphore Baru'], true) => 'semaphore-flag',
            str_starts_with($n, 'Tongkat') => 'tongkat',
            $n === 'Kaos Tangan' => 'sarung-tangan',
            str_starts_with($n, 'Topi Boni') => 'topi-boni',
            str_starts_with($n, 'Topi Rimba') => 'topi-rimba',
            str_starts_with($n, 'Baret') => 'baret',
            str_contains($n, 'Baju') => 'baju',
            str_contains($n, 'Celana') => 'celana',
            str_starts_with($n, 'Rompi') => 'rompi',
            str_starts_with($n, 'Sampur') => 'sampur',
            $n === 'Kopel' => 'kopel',
            str_starts_with($n, 'Tali Komando') => 'tali',
            str_starts_with($n, 'Kolongan') => 'kolongan',
            $n === 'Ikat Pinggang' => 'ikat-pinggang',
            str_starts_with($n, 'Dek ') => 'dek',
            str_starts_with($n, 'Pin ') => 'pin',
            $n === 'Hasduk' => 'hasduk',
            str_starts_with($n, 'Ban Lengan') => 'ban-lengan',
            str_starts_with($n, 'Scraf') => 'scraf',
            $n === 'Wosm' => 'wosm',
            $n === 'Cikal' => 'cikal',
            str_starts_with($n, 'Bendera') => 'bendera',
            default => match ($this->kategori) {
                'Tenda' => 'tenda',
                'Peralatan' => 'kompor',
                'Semaphore' => 'semaphore-flag',
                'Seragam' => 'baju',
                'Atribut' => 'pin',
                default => 'default',
            },
        };
    }

    /** Warna latar lembut di belakang ilustrasi, mengikuti warna tag kategori */
    public function getWarnaIkonAttribute(): string
    {
        return match ($this->kategori) {
            'Tenda' => 'info',
            'Peralatan' => 'accent',
            'Semaphore' => 'success',
            'Seragam' => 'warning',
            'Atribut' => 'rose',
            default => 'surface',
        };
    }

    /** Format Rupiah utk ditampilkan ke user & admin, mis. "Rp 15.000" */
    public function getHargaSewaFormatAttribute(): string
    {
        // (int) ... ?? 0: tetap aman walau kolom harga_sewa kosong/belum ada (skema database lama).
        return 'Rp '.number_format((int) ($this->harga_sewa ?? 0), 0, ',', '.');
    }

    /** Generate kode_alat otomatis & konsisten, dipakai seeder maupun form tambah alat */
    public static function generateKodeAlat(string $kategori): string
    {
        $prefix = self::PREFIX_KATEGORI[$kategori] ?? 'ALT';

        $urutan = self::withTrashed()->where('kode_alat', 'like', "{$prefix}-%")->count() + 1;

        return $prefix.'-'.str_pad((string) $urutan, 3, '0', STR_PAD_LEFT);
    }
}
