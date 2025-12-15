<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BahanStokBatch extends Model
{
    protected $guarded = ['id'];

    public function bahan()
    {
        return $this->belongsTo(Bahan::class);
    }

    public function bahanMutasi()
    {
        return $this->belongsTo(BahanMutasi::class);
    }

    public static function getAvailableBatches($bahanId, $jumlahDibutuhkan)
    {
        return static::where('bahan_id', $bahanId)
            ->where('jumlah_tersedia', '>', 0)
            ->orderBy('tanggal_masuk', 'asc') // FIFO: yang lama dulu
            ->get();
    }

    public function reduceStock($jumlah)
    {
        $this->decrement('jumlah_tersedia', $jumlah);
    }

    /**
     * Get HPP per satuan terkecil (alias untuk harga_satuan_terkecil)
     */
    public function getHppPerSatuanAttribute()
    {
        return $this->harga_satuan_terkecil;
    }
}
