<?php

namespace App\Models;

use App\Enums\PO\StatusKirimEnum;
use Illuminate\Database\Eloquent\Model;

class PO extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'status_kirim' => StatusKirimEnum::class,
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function bahanPO()
    {
        return $this->hasMany(BahanPO::class, 'po_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function bahanMutasiFaktur()
    {
        return $this->hasOne(BahanMutasiFaktur::class, 'po_id');
    }
}
