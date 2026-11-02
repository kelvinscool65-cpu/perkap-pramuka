<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notifikasi extends Model
{
    use HasFactory;

    protected $table = 'notifikasis';

    protected $fillable = [
        'user_id', 'judul', 'pesan', 'tipe', 'is_read',
    ];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeBelumDibaca(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    /** Kelas warna ikon notifikasi berdasarkan tipe, dipakai di dropdown lonceng */
    public function getWarnaAttribute(): string
    {
        return match ($this->tipe) {
            'success' => 'success',
            'warning' => 'warning',
            'danger' => 'danger',
            default => 'accent',
        };
    }
}
