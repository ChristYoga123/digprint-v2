<?php

namespace Database\Seeders;

use App\Models\Wallet;
use Illuminate\Database\Seeder;

class WalletSeeder extends Seeder
{
    /**
     * Seed wallets untuk sistem keuangan.
     * WAJIB dijalankan agar pembayaran TOP/LUNAS bisa masuk ke wallet.
     */
    public function run(): void
    {
        Wallet::firstOrCreate(
            ['kode' => Wallet::KODE_DP],
            [
                'nama' => 'DP',
                'saldo' => 0,
                'keterangan' => 'Wallet untuk menyimpan uang DP/cicilan dari customer yang belum lunas',
                'is_active' => true,
            ]
        );

        Wallet::firstOrCreate(
            ['kode' => Wallet::KODE_KAS_PEMASUKAN],
            [
                'nama' => 'Kas Pemasukan',
                'saldo' => 0,
                'keterangan' => 'Wallet untuk menyimpan pemasukan dari transaksi lunas',
                'is_active' => true,
            ]
        );

        $this->command->info('✓ Wallet seeded: DP, Kas Pemasukan');
    }
}

