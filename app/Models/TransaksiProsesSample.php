<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\TransaksiProsesSample\StatusSampleApprovalEnum;

class TransaksiProsesSample extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'status' => StatusSampleApprovalEnum::class,
    ];

    public function transaksiProses()
    {
        return $this->belongsTo(TransaksiProses::class);
    }

    public function operator()
    {
        return $this->belongsTo(User::class);
    }

    public function respondedBy()
    {
        return $this->belongsTo(User::class, 'responded_by');
    }
}
