<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proses extends Model
{
    protected $table = 'proses';
    
    protected $guarded = ['id'];

    public function produkProsesKategori()
    {
        return $this->belongsTo(ProdukProsesKategori::class);
    }

    public function produkProses()
    {
        return $this->hasMany(ProdukProses::class);
    }
}
