<?php

namespace Database\Seeders;

use App\Models\ProdukProsesKategori;
use Illuminate\Database\Seeder;

class ProdukProsesKategoriSeeder extends Seeder
{
    /**
     * Seed kategori proses produk.
     * WAJIB dijalankan agar sistem produksi bisa berjalan.
     */
    public function run(): void
    {
        ProdukProsesKategori::firstOrCreate(['nama' => ProdukProsesKategori::NAMA_PRA_PRODUKSI]);
        ProdukProsesKategori::firstOrCreate(['nama' => ProdukProsesKategori::NAMA_PRODUKSI]);
        ProdukProsesKategori::firstOrCreate(['nama' => ProdukProsesKategori::NAMA_FINISHING]);

        // Clear cache setelah seeding
        ProdukProsesKategori::clearIdCache();

        $this->command->info('✓ Produk Proses Kategori seeded: Pra Produksi, Produksi, Finishing');
    }
}

