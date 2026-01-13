<?php

namespace Database\Seeders;

use App\Models\CustomerKategori;
use Illuminate\Database\Seeder;

class CustomerKategoriSeeder extends Seeder
{
    /**
     * Seed kategori customer.
     */
    public function run(): void
    {
        CustomerKategori::firstOrCreate(
            ['nama' => 'Retail'],
            [
                'kode' => generateKode('CST'),
                'perlu_data_perusahaan' => false,
            ]
        );

        CustomerKategori::firstOrCreate(
            ['nama' => 'Corporate'],
            [
                'kode' => generateKode('CST'),
                'perlu_data_perusahaan' => true,
            ]
        );

        CustomerKategori::firstOrCreate(
            ['nama' => 'Reseller'],
            [
                'kode' => generateKode('CST'),
                'perlu_data_perusahaan' => false,
            ]
        );

        $this->command->info('✓ Customer Kategori seeded: Retail, Corporate, Reseller');
    }
}

