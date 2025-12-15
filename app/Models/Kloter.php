<?php

namespace App\Models;

use App\Enums\Kloter\StatusEnum;
use Illuminate\Database\Eloquent\Model;

class Kloter extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'status' => StatusEnum::class,
    ];

    public function mesin()
    {
        return $this->belongsTo(Mesin::class);
    }

    public function transaksiProses()
    {
        return $this->hasMany(TransaksiProses::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function isComplete(): bool
    {
        return $this->status === StatusEnum::SELESAI;
    }

    public function canBeCompleted(): bool
    {
        return $this->status === StatusEnum::AKTIF && $this->transaksiProses()->exists();
    }
}

