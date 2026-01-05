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

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Update status transaksi berdasarkan status produk-produk di dalamnya
     */
    public function updateStatusFromProduks(): void
    {
        $statuses = $this->transaksiProduks()->get()->map(function ($produk) {
            return $produk->status instanceof \UnitEnum ? $produk->status->value : $produk->status;
        });
        
        if ($statuses->isEmpty()) {
            return;
        }

        // Cek semua status
        // Jika semua produk sudah STATUS SIAP DIAMBIL atau SELESAI -> Transaksi Selesai
        $allFinished = $statuses->every(fn($s) => in_array($s, [
            \App\Enums\TransaksiProduk\StatusTransaksiProdukEnum::SIAP_DIAMBIL->value,
            \App\Enums\TransaksiProduk\StatusTransaksiProdukEnum::SELESAI->value
        ]));

        // Jika ada yang sedang dalam proses, atau sudah selesai sebagian -> Dalam Proses
        $anyProses = $statuses->contains(fn($s) => in_array($s, [
            \App\Enums\TransaksiProduk\StatusTransaksiProdukEnum::DALAM_PROSES->value,
            \App\Enums\TransaksiProduk\StatusTransaksiProdukEnum::SIAP_DIAMBIL->value,
            \App\Enums\TransaksiProduk\StatusTransaksiProdukEnum::SELESAI->value
        ]));

        if ($allFinished) {
            $this->update(['status_transaksi' => StatusTransaksiEnum::SELESAI]);
        } elseif ($anyProses) {
            $this->update(['status_transaksi' => StatusTransaksiEnum::DALAM_PROSES]);
        } else {
            $this->update(['status_transaksi' => StatusTransaksiEnum::BELUM]);
        }
    }

    /**
     * Set status transaksi menjadi SELESAI (sudah diambil customer)
     */
    public function setSelesai(): void
    {
        $this->update(['status_transaksi' => StatusTransaksiEnum::SELESAI->value]);
    }
}
