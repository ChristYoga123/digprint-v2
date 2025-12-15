<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PencatatanKeuangan extends Model
{
    protected $guarded = ['id'];

    public function pencatatanKeuanganable()
    {
        return $this->morphTo('pencatatan_keuangan');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
