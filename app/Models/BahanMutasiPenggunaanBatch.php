<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BahanMutasiPenggunaanBatch extends Model
{
    protected $guarded = ['id'];

    public function bahanMutasi()
    {
        return $this->belongsTo(BahanMutasi::class);
    }

    public function bahanStokBatch()
    {
        return $this->belongsTo(BahanStokBatch::class);
    }
}
