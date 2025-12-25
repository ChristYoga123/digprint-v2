<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransaksiKalkulasiProduk extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'addons' => 'json',
        'proses_perlu_sample_approval' => 'json',
    ];

    public function transaksiKalkulasi()
    {
        return $this->belongsTo(TransaksiKalkulasi::class);
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class);
    }
}
