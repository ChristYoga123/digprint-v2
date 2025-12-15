<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BahanPO extends Model
{
    protected $guarded = ['id'];

    public function po()
    {
        return $this->belongsTo(PO::class);
    }

    public function bahan()
    {
        return $this->belongsTo(Bahan::class);
    }
}
