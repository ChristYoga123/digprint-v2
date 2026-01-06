<?php

namespace App\Models;

use App\Enums\BahanMutasi\TipeEnum;
use Illuminate\Database\Eloquent\Model;

class BahanMutasi extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'tipe' => TipeEnum::class,
        'jumlah_mutasi' => 'float',
    ];

    public function bahan()
    {
        return $this->belongsTo(Bahan::class);
    }

    public function bahanMutasiFaktur()
    {
        return $this->belongsTo(BahanMutasiFaktur::class);
    }
}
