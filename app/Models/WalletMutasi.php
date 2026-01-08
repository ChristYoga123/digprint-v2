<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletMutasi extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'nominal' => 'decimal:2',
        'saldo_sebelum' => 'decimal:2',
        'saldo_sesudah' => 'decimal:2',
    ];

    /**
     * Relasi ke wallet
     */
    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Relasi ke wallet tujuan (untuk transfer)
     */
    public function walletTujuan()
    {
        return $this->belongsTo(Wallet::class, 'wallet_tujuan_id');
    }

    /**
     * Relasi ke mutasi terkait (untuk transfer)
     */
    public function relatedMutasi()
    {
        return $this->belongsTo(WalletMutasi::class, 'related_mutasi_id');
    }

    /**
     * Polymorphic relation ke sumber (PencatatanKeuangan, dll)
     */
    public function sumber()
    {
        return $this->morphTo();
    }

    /**
     * Relasi ke transaksi
     */
    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class);
    }

    /**
     * Relasi ke user yang membuat
     */
    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Cek apakah ini mutasi masuk
     */
    public function isMasuk(): bool
    {
        return $this->tipe === 'masuk';
    }

    /**
     * Cek apakah ini mutasi keluar
     */
    public function isKeluar(): bool
    {
        return $this->tipe === 'keluar';
    }

    /**
     * Cek apakah ini mutasi transfer
     */
    public function isTransfer(): bool
    {
        return $this->tipe === 'transfer';
    }
}
