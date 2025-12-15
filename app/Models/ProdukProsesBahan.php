<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProdukProsesBahan extends Model
{
    protected $guarded = ['id'];

    public function produkProses()
    {
        return $this->belongsTo(ProdukProses::class);
    }

    public function bahan()
    {
        return $this->belongsTo(Bahan::class);
    }
}
