<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'saldo' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    // Konstanta untuk kode wallet yang sudah didefinisikan
    const KODE_DP = 'WALLET-DP';
    const KODE_KAS_PEMASUKAN = 'WALLET-KAS';

    /**
     * Get wallet DP
     */
    public static function walletDP(): ?self
    {
        return self::where('kode', self::KODE_DP)->first();
    }

    /**
     * Get wallet Kas Pemasukan
     */
    public static function walletKasPemasukan(): ?self
    {
        return self::where('kode', self::KODE_KAS_PEMASUKAN)->first();
    }

    /**
     * Relasi ke mutasi wallet
     */
    public function mutasis()
    {
        return $this->hasMany(WalletMutasi::class);
    }

    /**
     * Tambah saldo (masuk)
     */
    public function tambahSaldo(float $nominal, ?string $keterangan = null, $sumber = null, ?int $transaksiId = null, ?int $createdBy = null): WalletMutasi
    {
        $saldoSebelum = $this->saldo;
        $saldoSesudah = $saldoSebelum + $nominal;

        $this->update(['saldo' => $saldoSesudah]);

        return WalletMutasi::create([
            'kode' => generateKode('WM'),
            'wallet_id' => $this->id,
            'tipe' => 'masuk',
            'nominal' => $nominal,
            'saldo_sebelum' => $saldoSebelum,
            'saldo_sesudah' => $saldoSesudah,
            'sumber_type' => $sumber ? get_class($sumber) : null,
            'sumber_id' => $sumber ? $sumber->id : null,
            'transaksi_id' => $transaksiId,
            'keterangan' => $keterangan,
            'created_by' => $createdBy,
        ]);
    }

    /**
     * Kurangi saldo (keluar)
     */
    public function kurangiSaldo(float $nominal, ?string $keterangan = null, $sumber = null, ?int $transaksiId = null, ?int $createdBy = null): WalletMutasi
    {
        $saldoSebelum = $this->saldo;
        $saldoSesudah = $saldoSebelum - $nominal;

        $this->update(['saldo' => $saldoSesudah]);

        return WalletMutasi::create([
            'kode' => generateKode('WM'),
            'wallet_id' => $this->id,
            'tipe' => 'keluar',
            'nominal' => $nominal,
            'saldo_sebelum' => $saldoSebelum,
            'saldo_sesudah' => $saldoSesudah,
            'sumber_type' => $sumber ? get_class($sumber) : null,
            'sumber_id' => $sumber ? $sumber->id : null,
            'transaksi_id' => $transaksiId,
            'keterangan' => $keterangan,
            'created_by' => $createdBy,
        ]);
    }

    /**
     * Transfer saldo ke wallet lain
     */
    public function transferKe(Wallet $walletTujuan, float $nominal, ?string $keterangan = null, ?int $transaksiId = null, ?int $createdBy = null): array
    {
        // Kurangi dari wallet asal
        $mutasiKeluar = $this->kurangiSaldo($nominal, $keterangan, null, $transaksiId, $createdBy);
        $mutasiKeluar->update([
            'tipe' => 'transfer',
            'wallet_tujuan_id' => $walletTujuan->id,
        ]);

        // Tambah ke wallet tujuan
        $mutasiMasuk = $walletTujuan->tambahSaldo($nominal, $keterangan, null, $transaksiId, $createdBy);
        $mutasiMasuk->update([
            'tipe' => 'transfer',
            'related_mutasi_id' => $mutasiKeluar->id,
        ]);

        // Link balik
        $mutasiKeluar->update(['related_mutasi_id' => $mutasiMasuk->id]);

        return [
            'keluar' => $mutasiKeluar,
            'masuk' => $mutasiMasuk,
        ];
    }
}
