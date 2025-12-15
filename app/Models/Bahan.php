<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bahan extends Model
{
    protected $guarded = ['id'];

    public function satuanTerbesar()
    {
        return $this->belongsTo(Satuan::class, 'satuan_terbesar_id');
    }

    public function satuanTerkecil()
    {
        return $this->belongsTo(Satuan::class, 'satuan_terkecil_id');
    }

    /**
     * Get all stok batches for this bahan.
     */
    public function stokBatches()
    {
        return $this->hasMany(BahanStokBatch::class, 'bahan_id');
    }

    /**
     * Get available stok from batches (calculated from FIFO batches).
     */
    public function getAvailableStokFromBatches(): int
    {
        return $this->stokBatches()
            ->where('jumlah_tersedia', '>', 0)
            ->sum('jumlah_tersedia');
    }

    /**
     * Get stok attribute (calculated from batches for compatibility).
     * This accessor allows the stok field to be used even though it doesn't exist in the database.
     */
    public function getStokAttribute(): int
    {
        return $this->getAvailableStokFromBatches();
    }
}
