<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class TransaksiProdukSubjoin extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $guarded = ['id'];

    public function transaksiProduk()
    {
        return $this->belongsTo(TransaksiProduk::class);
    }

    public function produkProses()
    {
        return $this->belongsTo(ProdukProses::class);
    }
}
