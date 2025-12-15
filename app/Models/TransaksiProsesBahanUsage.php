<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransaksiProsesBahanUsage extends Model
{
    protected $guarded = ['id'];

    public function transaksiProses()
    {
        return $this->belongsTo(TransaksiProses::class);
    }

    public function bahan()
    {
        return $this->belongsTo(Bahan::class);
    }
}

