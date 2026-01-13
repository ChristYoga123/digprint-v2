<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    /**
     * Seed semua data master yang WAJIB ada untuk sistem berjalan.
     * Jalankan dengan: php artisan db:seed --class=MasterDataSeeder
     */
    public function run(): void
    {
        $this->command->info('');
        $this->command->info('=================================');
        $this->command->info('  SEEDING MASTER DATA');
        $this->command->info('=================================');
        $this->command->info('');

        $this->call([
            WalletSeeder::class,
            SatuanSeeder::class,
            CustomerKategoriSeeder::class,
            ProdukProsesKategoriSeeder::class,
        ]);

        $this->command->info('');
        $this->command->info('=================================');
        $this->command->info('  MASTER DATA SEEDING COMPLETE');
        $this->command->info('=================================');
        $this->command->info('');
    }
}

