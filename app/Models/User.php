<?php

namespace App\Models;

use App\Exceptions\IdentitasTidakBolehDiubah;
use App\Support\Identitas;
use App\Support\KodeAnggota;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    public const STATUS_AKTIF = 'aktif';

    public const STATUS_DIBLOKIR = 'diblokir';

    /**
     * Sengaja TIDAK memuat: kode_anggota, no_hp_normal, email_kanonik, status_akun, ip_daftar,
     * syarat_disetujui_pada, diblokir_*. Kolom-kolom itu hanya diisi oleh sistem (event model di
     * bawah / ManajemenAkun), jadi tidak mungkin diubah lewat form atau mass-assignment.
     */
    protected $fillable = [
        'nama', 'email', 'username', 'password', 'no_hp', 'role', 'avatar',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'diblokir_pada' => 'datetime',
            'syarat_disetujui_pada' => 'datetime',
        ];
    }

    // ==== Identitas unik: dijaga di SATU tempat supaya semua jalur (form, seeder, tinker) patuh ====

    protected static function booted(): void
    {
        // Akun baru selalu mendapat ID Anggota dan status "aktif".
        static::creating(function (self $user) {
            if (blank($user->kode_anggota)) {
                $user->kode_anggota = static::kodeAnggotaBaru();
            }
            if (blank($user->status_akun)) {
                $user->status_akun = self::STATUS_AKTIF;
            }
        });

        // Nilai baku (pembanding keunikan) selalu dihitung ulang dari email/no_hp yang tersimpan.
        static::saving(function (self $user) {
            if ($user->isDirty('email') || blank($user->email_kanonik)) {
                $user->email_kanonik = Identitas::kanonikEmail($user->email);
            }
            if ($user->isDirty('no_hp') || (filled($user->no_hp) && blank($user->no_hp_normal))) {
                $user->no_hp_normal = Identitas::normalisasiNoHp($user->no_hp);
            }
        });

        // ID Anggota yang sudah terisi TIDAK BOLEH diubah (lapisan aplikasi; lapisan database = trigger).
        static::updating(function (self $user) {
            if ($user->isDirty('kode_anggota') && filled($user->getOriginal('kode_anggota'))) {
                throw new IdentitasTidakBolehDiubah;
            }
        });
    }

    /**
     * ID Anggota baru yang belum dipakai siapa pun — termasuk akun yang sudah dihapus (withTrashed),
     * sebab ID milik akun terhapus tetap "terpesan". Indeks UNIQUE di database adalah jaminan akhir;
     * pengecekan ini hanya menghindari bentrok yang sangat langka. $pembuat dapat diganti untuk uji.
     */
    public static function kodeAnggotaBaru(?callable $pembuat = null): string
    {
        $pembuat ??= fn () => KodeAnggota::buat();

        for ($i = 0; $i < 25; $i++) {
            $kode = $pembuat();
            if (! static::withTrashed()->where('kode_anggota', $kode)->exists()) {
                return $kode;
            }
        }

        throw new \RuntimeException('Tidak berhasil membuat ID Anggota yang unik. Coba lagi.');
    }

    // ==== Relasi ====

    /** Semua pengajuan yang dibuat user ini (sebagai peminjam) */
    public function pengajuan(): HasMany
    {
        return $this->hasMany(PengajuanPeminjaman::class, 'user_id');
    }

    /** Semua pengajuan yang pernah diproses user ini (sebagai petugas/admin) */
    public function pengajuanDiproses(): HasMany
    {
        return $this->hasMany(PengajuanPeminjaman::class, 'petugas_id');
    }

    public function notifikasi(): HasMany
    {
        return $this->hasMany(Notifikasi::class, 'user_id')->latest();
    }

    /**
     * Catatan pelanggaran peminjam (dicatat petugas). Sengaja TANPA urutan bawaan: relasi yang
     * ikut di-count() akan membawa ORDER BY ke query agregat, yang ditolak MySQL mode ketat
     * (ONLY_FULL_GROUP_BY). Urutkan dengan ->latest() hanya saat menampilkan daftar.
     */
    public function pelanggaran(): HasMany
    {
        return $this->hasMany(Pelanggaran::class, 'user_id');
    }

    /** Petugas yang memblokir akun ini */
    public function diblokirOleh(): BelongsTo
    {
        return $this->belongsTo(self::class, 'diblokir_oleh')->withTrashed();
    }

    // ==== Scopes ====

    public function scopePeminjam(Builder $query): Builder
    {
        return $query->where('role', 'peminjam');
    }

    public function scopeAktif(Builder $query): Builder
    {
        return $query->where('status_akun', self::STATUS_AKTIF);
    }

    public function scopeDiblokir(Builder $query): Builder
    {
        return $query->where('status_akun', self::STATUS_DIBLOKIR);
    }

    /** Peminjam aktif yang catatan "penipuan"-nya sudah mencapai batas → layak ditinjau untuk diblokir. */
    public function scopeLayakDitinjau(Builder $query): Builder
    {
        return $query->peminjam()->aktif()->whereHas(
            'pelanggaran',
            fn ($pelanggaran) => $pelanggaran->where('jenis', 'penipuan'),
            '>=',
            (int) config('perkap.batas_penipuan', 3),
        );
    }

    public static function jumlahLayakDitinjau(): int
    {
        return static::query()->layakDitinjau()->count();
    }

    // ==== Helper role & status ====

    public function isAdmin(): bool
    {
        return $this->role === 'petugas_perkap';
    }

    public function isPeminjam(): bool
    {
        return $this->role === 'peminjam';
    }

    public function isDiblokir(): bool
    {
        return $this->status_akun === self::STATUS_DIBLOKIR;
    }

    public function getRoleLabelAttribute(): string
    {
        return $this->role === 'petugas_perkap' ? 'Petugas Perkap' : 'Peminjam';
    }

    /** Jumlah pelanggaran; null = semua jenis. Memakai hasil withCount bila sudah dimuat (hemat query). */
    public function jumlahPelanggaran(?string $jenis = null): int
    {
        if ($jenis === 'penipuan' && isset($this->attributes['penipuan_count'])) {
            return (int) $this->attributes['penipuan_count'];
        }
        if ($jenis === null && isset($this->attributes['pelanggaran_count'])) {
            return (int) $this->attributes['pelanggaran_count'];
        }

        return $this->pelanggaran()->when($jenis, fn ($q) => $q->where('jenis', $jenis))->count();
    }

    /** Akun peminjam aktif yang sudah mencapai batas penipuan → layak ditinjau/diblokir petugas. */
    public function perluDitinjau(): bool
    {
        return $this->isPeminjam()
            && ! $this->isDiblokir()
            && $this->jumlahPelanggaran('penipuan') >= (int) config('perkap.batas_penipuan', 3);
    }

    /** Pesan yang ditampilkan kepada pemilik akun yang diblokir (saat login / saat sesinya diputus). */
    public function pesanBlokir(): string
    {
        $alasan = trim((string) $this->alasan_blokir);

        return 'Akun Anda sedang diblokir oleh petugas Perkap.'
            .($alasan !== '' ? " Alasan: {$alasan}." : '')
            .' Bila Anda merasa ini keliru, hubungi petugas via WhatsApp '
            .Identitas::formatNoHp(config('perkap.kontak_wa')).'.';
    }

    /**
     * Foto profil. Bila belum ada, dibuat avatar berinisial sebagai gambar SVG inline (data URI):
     * tidak butuh internet, tidak membocorkan nama anggota ke layanan pihak ketiga, dan warnanya
     * tetap (diturunkan dari nama) sehingga setiap anggota mudah dikenali.
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/'.$this->avatar);
        }

        $kata = preg_split('/\s+/u', trim((string) $this->nama), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $inisial = mb_strtoupper(mb_substr($kata[0] ?? '?', 0, 1).(isset($kata[1]) ? mb_substr($kata[1], 0, 1) : ''));

        // [awal, akhir] gradien — dari palet aplikasi
        $palet = [['#8B5CF6', '#6D28D9'], ['#3B82F6', '#1D4ED8'], ['#10B981', '#047857'], ['#F59E0B', '#B45309'], ['#EC4899', '#BE185D']];
        [$a, $b] = $palet[abs(crc32((string) $this->nama)) % count($palet)];

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1">'
            ."<stop offset=\"0\" stop-color=\"{$a}\"/><stop offset=\"1\" stop-color=\"{$b}\"/></linearGradient></defs>"
            .'<rect width="64" height="64" fill="url(#g)"/>'
            .'<text x="32" y="32" dy=".35em" text-anchor="middle" font-family="Arial,Helvetica,sans-serif" font-size="26" font-weight="700" fill="#fff">'
            .htmlspecialchars($inisial, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</text></svg>';

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
