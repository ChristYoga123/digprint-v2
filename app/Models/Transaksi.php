<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\Transaksi\JenisDiskonEnum;
use App\Enums\Transaksi\StatusTransaksiEnum;
use App\Enums\Transaksi\StatusPembayaranEnum;

class Transaksi extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'jenis_diskon' => JenisDiskonEnum::class,
        'status_transaksi' => StatusTransaksiEnum::class,
        'status_pembayaran' => StatusPembayaranEnum::class,
    ];

    public function transaksiKalkulasi()
    {
        return $this->belongsTo(TransaksiKalkulasi::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function transaksiProduks()
    {
        return $this->hasMany(TransaksiProduk::class);
    }

    public function approvedDiskonBy()
    {
        return $this->belongsTo(User::class, 'approved_diskon_by');
    }

    public function pencatatanKeuangans()
    {
        return $this->morphMany(PencatatanKeuangan::class, 'pencatatan_keuangan');
    }
}
