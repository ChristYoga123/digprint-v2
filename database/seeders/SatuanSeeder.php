<?php

namespace Database\Seeders;

use App\Models\Satuan;
use Illuminate\Database\Seeder;

class SatuanSeeder extends Seeder
{
    /**
     * Seed satuan/unit dasar.
     */
    public function run(): void
    {
        $satuans = [
            'Pcs',
            'Rim',
            'Kg',
            'Liter',
            'Meter',
            'Lembar',
            'Box',
            'Roll',
            'Set',
        ];

        foreach ($satuans as $nama) {
            Satuan::firstOrCreate(['nama' => $nama]);
        }

        $this->command->info('✓ Satuan seeded: ' . implode(', ', $satuans));
    }
}

