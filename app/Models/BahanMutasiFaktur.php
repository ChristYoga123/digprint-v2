<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use App\Enums\BahanMutasiFaktur\StatusPembayaranEnum;

class BahanMutasiFaktur extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $guarded = ['id'];

    protected $casts = [
        'status_pembayaran' => StatusPembayaranEnum::class,
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function po()
    {
        return $this->belongsTo(PO::class);
    }

    public function bahanMutasis()
    {
        return $this->hasMany(BahanMutasi::class);
    }

    public function pencatatanKeuangans()
    {
        return $this->morphMany(PencatatanKeuangan::class, 'pencatatan_keuangan');
    }
}
