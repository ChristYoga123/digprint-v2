<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransaksiKalkulasi extends Model
{
    protected $guarded = ['id'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function transaksiKalkulasiProduks()
    {
        return $this->hasMany(TransaksiKalkulasiProduk::class);
    }

    public function transaksis()
    {
        return $this->hasMany(Transaksi::class);
    }
}
