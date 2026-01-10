<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Enums\Transaksi\JenisDiskonEnum;
use App\Enums\Transaksi\StatusTransaksiEnum;
use App\Enums\Transaksi\StatusPembayaranEnum;
use App\Jobs\SendSiapDiambilWhatsappJob;

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
     * Relasi ke wallet mutasi
     */
    public function walletMutasis()
    {
        return $this->hasMany(WalletMutasi::class);
    }

    /**
     * Update status transaksi berdasarkan status produk-produk di dalamnya
     */
    public function updateStatusFromProduks(): void
    {
        $oldStatus = $this->status_transaksi;
        
        $statuses = $this->transaksiProduks()->get()->map(function ($produk) {
            return $produk->status instanceof \UnitEnum ? $produk->status->value : $produk->status;
        });
        
        if ($statuses->isEmpty()) {
            return;
        }

        // Cek apakah semua produk sudah SELESAI (pelanggan sudah mengambil)
        $allSelesai = $statuses->every(fn($s) => $s === \App\Enums\TransaksiProduk\StatusTransaksiProdukEnum::SELESAI->value);
        
        // Cek apakah semua produk sudah SIAP_DIAMBIL atau SELESAI
        $allSiapDiambilOrSelesai = $statuses->every(fn($s) => in_array($s, [
            \App\Enums\TransaksiProduk\StatusTransaksiProdukEnum::SIAP_DIAMBIL->value,
            \App\Enums\TransaksiProduk\StatusTransaksiProdukEnum::SELESAI->value
        ]));

        // Jika ada yang sedang dalam proses, atau sudah selesai sebagian -> Dalam Proses
        $anyProses = $statuses->contains(fn($s) => in_array($s, [
            \App\Enums\TransaksiProduk\StatusTransaksiProdukEnum::DALAM_PROSES->value,
            \App\Enums\TransaksiProduk\StatusTransaksiProdukEnum::SIAP_DIAMBIL->value,
            \App\Enums\TransaksiProduk\StatusTransaksiProdukEnum::SELESAI->value
        ]));

        $newStatus = null;
        if ($allSelesai) {
            // Semua produk sudah diambil pelanggan
            $newStatus = StatusTransaksiEnum::SELESAI;
        } elseif ($allSiapDiambilOrSelesai) {
            // Semua produk siap diambil (belum tentu sudah diambil semua)
            $newStatus = StatusTransaksiEnum::SIAP_DIAMBIL;
        } elseif ($anyProses) {
            $newStatus = StatusTransaksiEnum::DALAM_PROSES;
        } else {
            $newStatus = StatusTransaksiEnum::BELUM;
        }
        
        $this->update(['status_transaksi' => $newStatus]);
        
        // Jika status berubah menjadi SIAP_DIAMBIL
        if ($newStatus === StatusTransaksiEnum::SIAP_DIAMBIL && 
            $oldStatus !== StatusTransaksiEnum::SIAP_DIAMBIL) {
            
            // Transfer DP ke Kas jika pembayaran sudah LUNAS
            if ($this->status_pembayaran === StatusPembayaranEnum::LUNAS) {
                $this->transferDPKeKasPemasukan();
            }
            
            // Kirim notifikasi WhatsApp bahwa pesanan siap diambil
            SendSiapDiambilWhatsappJob::dispatch($this->id);
        }
    }

    /**
     * Set status transaksi menjadi SELESAI (sudah diambil customer)
     */
    public function setSelesai(): void
    {
        $this->update(['status_transaksi' => StatusTransaksiEnum::SELESAI->value]);
    }
    
    /**
     * Cek apakah pembayaran sudah lunas
     */
    public function isLunas(): bool
    {
        return $this->status_pembayaran === StatusPembayaranEnum::LUNAS || 
               $this->status_pembayaran === StatusPembayaranEnum::LUNAS->value;
    }
    
    /**
     * Cek apakah transaksi siap diambil
     */
    public function isSiapDiambil(): bool
    {
        return $this->status_transaksi === StatusTransaksiEnum::SIAP_DIAMBIL || 
               $this->status_transaksi === StatusTransaksiEnum::SIAP_DIAMBIL->value;
    }
    
    /**
     * Transfer saldo DP ke Kas Pemasukan untuk transaksi ini
     */
    public function transferDPKeKasPemasukan(): void
    {
        $walletDP = Wallet::walletDP();
        $walletKas = Wallet::walletKasPemasukan();
        
        if (!$walletDP || !$walletKas) {
            return;
        }
        
        // Hitung total DP yang sudah masuk untuk transaksi ini
        $totalDPMasuk = WalletMutasi::where('wallet_id', $walletDP->id)
            ->where('transaksi_id', $this->id)
            ->where('tipe', 'masuk')
            ->sum('nominal');
        
        // Hitung total yang sudah ditransfer ke Kas Pemasukan
        $totalSudahTransfer = WalletMutasi::where('wallet_id', $walletDP->id)
            ->where('transaksi_id', $this->id)
            ->where('tipe', 'transfer')
            ->sum('nominal');
        
        $sisaDPUntukTransfer = $totalDPMasuk - $totalSudahTransfer;
        
        if ($sisaDPUntukTransfer > 0) {
            $walletDP->transferKe(
                $walletKas,
                $sisaDPUntukTransfer,
                'Transfer DP ke Kas Pemasukan - Transaksi ' . $this->kode . ' (Lunas & Siap Diambil)',
                $this->id,
                \Illuminate\Support\Facades\Auth::id()
            );
        }
    }
}

