<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produk extends Model
{
    protected $guarded = ['id'];

    public function produkProses()
    {
        return $this->hasMany(ProdukProses::class)->orderBy('urutan');
    }

    public function produkProsesBahan()
    {
        return $this->hasManyThrough(Bahan::class, ProdukProsesBahan::class);
    }

    public function produkHargas()
    {
        return $this->hasMany(ProdukHarga::class);
    }
}
