<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransaksiProduk extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'addons' => 'json',
    ];

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class);
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class);
    }

    public function design()
    {
        return $this->belongsTo(ProdukProses::class, 'design_id');
    }

    public function transaksiProses()
    {
        return $this->hasMany(TransaksiProses::class);
    }
}
