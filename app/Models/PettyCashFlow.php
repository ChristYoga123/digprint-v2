<?php

namespace App\Models;

use App\Enums\PettyCashFlow\TipeEnum;
use Illuminate\Database\Eloquent\Model;
use App\Enums\PettyCashFlow\StatusApprovalEnum;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class PettyCashFlow extends Model implements HasMedia
{
    use InteractsWithMedia;
    protected $guarded = ['id'];

    protected $casts = [
        'tipe' => TipeEnum::class,
        'status_approval' => StatusApprovalEnum::class,
    ];

    public function pettyCash()
    {
        return $this->belongsTo(PettyCash::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
