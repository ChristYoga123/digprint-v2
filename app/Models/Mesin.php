<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mesin extends Model
{
    protected $guarded = ['id'];

    public function karyawans()
    {
        return $this->belongsToMany(User::class, 'user_has_mesins', 'mesin_id', 'karyawan_id');
    }
}
