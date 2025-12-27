<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\KaryawanPekerjaan\TipeEnum;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KaryawanPekerjaan extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'jam_lembur_mulai' => 'datetime',
        'jam_lembur_selesai' => 'datetime',
        'jam_aktual_mulai' => 'datetime',
        'jam_aktual_selesai' => 'datetime',
        'approved_at' => 'datetime',
        'apakah_diapprove_lembur' => 'boolean',
        'tipe' => TipeEnum::class,
    ];

    /**
     * Get the karyawan (user) who did the work
     */
    public function karyawan(): BelongsTo
    {
        return $this->belongsTo(User::class, 'karyawan_id');
    }

    /**
     * Get the user who approved the overtime
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Polymorphic relation to the work entity
     * Can be: TransaksiProses, TransaksiProduk, Transaksi, TransaksiKalkulasi
     */
    public function pekerjaanAble(): MorphTo
    {
        return $this->morphTo('karyawan_pekerjaan');
    }

    /**
     * Scope for overtime records only
     */
    public function scopeLembur($query)
    {
        return $query->where('tipe', TipeEnum::LEMBUR);
    }

    /**
     * Scope for normal work records only
     */
    public function scopeNormal($query)
    {
        return $query->where('tipe', TipeEnum::NORMAL);
    }

    /**
     * Scope for pending approval
     */
    public function scopePendingApproval($query)
    {
        return $query->where('tipe', TipeEnum::LEMBUR)
            ->whereNull('apakah_diapprove_lembur');
    }

    /**
     * Scope for approved overtime
     */
    public function scopeApproved($query)
    {
        return $query->where('tipe', TipeEnum::LEMBUR)
            ->where('apakah_diapprove_lembur', true);
    }

    /**
     * Scope for rejected overtime
     */
    public function scopeRejected($query)
    {
        return $query->where('tipe', TipeEnum::LEMBUR)
            ->where('apakah_diapprove_lembur', false);
    }
}
