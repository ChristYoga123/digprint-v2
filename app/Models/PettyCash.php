<?php

namespace App\Models;

use App\Enums\PettyCash\StatusEnum;
use Illuminate\Database\Eloquent\Model;

class PettyCash extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'status' => StatusEnum::class,
    ];

    public function userBuka()
    {
        return $this->belongsTo(User::class, 'user_id_buka');
    }

    public function userTutup()
    {
        return $this->belongsTo(User::class, 'user_id_tutup');
    }

    public function pettyCashFlows()
    {
        return $this->hasMany(PettyCashFlow::class);
    }

    public function approvedByBuka()
    {
        return $this->belongsTo(User::class, 'approved_by_buka');
    }

    public function approvedByTutup()
    {
        return $this->belongsTo(User::class, 'approved_by_tutup');
    }
}
