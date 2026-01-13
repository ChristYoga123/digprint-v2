<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Bahan;
use App\Models\Mesin;
use App\Models\Proses;
use App\Models\Produk;
use App\Models\Satuan;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\ProdukHarga;
use App\Models\ProdukProses;
use Illuminate\Database\Seeder;
use App\Models\CustomerKategori;
use App\Models\ProdukProsesBahan;
use Illuminate\Support\Facades\DB;
use App\Models\ProdukProsesKategori;
use App\Models\BahanMutasi;
use App\Models\BahanMutasiFaktur;
use App\Models\BahanStokBatch;
use App\Enums\BahanMutasi\TipeEnum;
use Carbon\Carbon;

class SampleDataSeeder extends Seeder
{
    /**
     * Seed sample data untuk testing/development.
     * TIDAK WAJIB untuk production.
     * 
     * PENTING: Jalankan MasterDataSeeder terlebih dahulu!
     * php artisan db:seed --class=MasterDataSeeder
     * php artisan db:seed --class=SampleDataSeeder
     */
    public function run(): void
    {
        DB::beginTransaction();

        try {
            // Get master data
            $satuanPcs = Satuan::where('nama', 'Pcs')->first();
            $satuanRim = Satuan::where('nama', 'Rim')->first();
            $satuanLiter = Satuan::where('nama', 'Liter')->first();
            
            $kategoriRetail = CustomerKategori::where('nama', 'Retail')->first();
            $kategoriCorporate = CustomerKategori::where('nama', 'Corporate')->first();
            $kategoriReseller = CustomerKategori::where('nama', 'Reseller')->first();
            
            $kategoriDesign = ProdukProsesKategori::where('nama', ProdukProsesKategori::NAMA_PRA_PRODUKSI)->first();
            $kategoriProduksi = ProdukProsesKategori::where('nama', ProdukProsesKategori::NAMA_PRODUKSI)->first();
            $kategoriFinishing = ProdukProsesKategori::where('nama', ProdukProsesKategori::NAMA_FINISHING)->first();

            if (!$satuanPcs || !$kategoriRetail || !$kategoriDesign) {
                $this->command->error('Master data tidak ditemukan! Jalankan MasterDataSeeder terlebih dahulu.');
                return;
            }

            // 1. Sample Customers
            $customer1 = Customer::firstOrCreate(
                ['nama' => 'Budi Santoso', 'no_hp1' => '081234567890'],
                [
                    'customer_kategori_id' => $kategoriRetail->id,
                    'alamat' => 'Jl. Merdeka No. 123, Jakarta Pusat',
                    'no_hp2' => '081234567891',
                ]
            );

            $customer2 = Customer::firstOrCreate(
                ['nama' => 'PT. Maju Jaya Abadi', 'no_hp1' => '081234567892'],
                [
                    'customer_kategori_id' => $kategoriCorporate->id,
                    'alamat' => 'Jl. Sudirman No. 456, Jakarta Selatan',
                    'nama_perusahaan' => 'PT. Maju Jaya Abadi',
                    'alamat_perusahaan' => 'Jl. Sudirman No. 456, Jakarta Selatan',
                ]
            );

            // 2. Sample Suppliers
            $supplier1 = Supplier::firstOrCreate(
                ['nama_perusahaan' => 'PT. Kertas Indonesia'],
                [
                    'kode' => generateKode('SUP'),
                    'nama_sales' => 'Ahmad Fauzi',
                    'no_hp_sales' => '081234567894',
                    'alamat_perusahaan' => 'Jl. Industri No. 100, Bandung',
                    'alamat_gudang' => 'Jl. Industri No. 100, Bandung',
                    'metode_pembayaran1' => 'Bank Transfer',
                    'nomor_rekening1' => '1234567890',
                    'nama_rekening1' => 'PT. Kertas Indonesia',
                    'metode_pembayaran2' => '',
                    'nomor_rekening2' => '',
                    'nama_rekening2' => '',
                    'is_active' => true,
                    'is_pkp' => true,
                    'npwp' => '01.234.567.8-901.000',
                    'is_po' => true,
                ]
            );

            // 3. Sample Bahan
            $bahanKertasA4 = Bahan::firstOrCreate(
                ['kode' => 'BHN-KRT001'],
                [
                    'nama' => 'Kertas A4 80gsm',
                    'satuan_terbesar_id' => $satuanRim->id,
                    'satuan_terkecil_id' => $satuanPcs->id,
                    'stok_minimal' => 20,
                    'keterangan' => 'Kertas A4 standar untuk printing',
                ]
            );

            $bahanTinta = Bahan::firstOrCreate(
                ['kode' => 'BHN-TNT001'],
                [
                    'nama' => 'Tinta Printer',
                    'satuan_terbesar_id' => $satuanLiter->id,
                    'satuan_terkecil_id' => $satuanLiter->id,
                    'stok_minimal' => 10,
                    'keterangan' => 'Tinta untuk printer',
                ]
            );

            // 4. Sample Master Proses
            $prosesDesignSimple = Proses::firstOrCreate(
                ['nama' => 'Design Simple', 'produk_proses_kategori_id' => $kategoriDesign->id],
                ['harga_default' => 50000]
            );
            $prosesDesignPremium = Proses::firstOrCreate(
                ['nama' => 'Design Premium', 'produk_proses_kategori_id' => $kategoriDesign->id],
                ['harga_default' => 100000]
            );

            $prosesCetakKartuNama = Proses::firstOrCreate(
                ['nama' => 'Cetak Kartu Nama', 'produk_proses_kategori_id' => $kategoriProduksi->id],
                ['harga_default' => null]
            );
            $prosesLaminating = Proses::firstOrCreate(
                ['nama' => 'Laminating', 'produk_proses_kategori_id' => $kategoriProduksi->id],
                ['harga_default' => null]
            );

            $prosesMataAyam = Proses::firstOrCreate(
                ['nama' => 'Mata Ayam', 'produk_proses_kategori_id' => $kategoriFinishing->id],
                ['harga_default' => 5000]
            );

            // 5. Sample Mesin
            $mesinPrinter1 = Mesin::firstOrCreate(
                ['kode' => 'MES-PRT001'],
                ['nama' => 'Printer Canon IP2770']
            );

            $mesinLaminating = Mesin::firstOrCreate(
                ['kode' => 'MES-LAM001'],
                ['nama' => 'Mesin Laminating']
            );

            // 6. Sample Produk: Kartu Nama
            $produkKartuNama = Produk::firstOrCreate(
                ['kode' => 'PRD-KTN001'],
                [
                    'nama' => 'Kartu Nama',
                    'apakah_perlu_custom_dimensi' => true,
                ]
            );

            // 6.1 Produk Proses untuk Kartu Nama
            $produkKartuNamaDesign1 = ProdukProses::firstOrCreate(
                [
                    'produk_id' => $produkKartuNama->id,
                    'produk_proses_kategori_id' => $kategoriDesign->id,
                    'nama' => 'Design Simple',
                ],
                [
                    'proses_id' => $prosesDesignSimple->id,
                    'harga' => 50000,
                    'urutan' => 0,
                    'apakah_mengurangi_bahan' => false,
                ]
            );

            $produkKartuNamaProses1 = ProdukProses::firstOrCreate(
                [
                    'produk_id' => $produkKartuNama->id,
                    'produk_proses_kategori_id' => $kategoriProduksi->id,
                    'nama' => 'Cetak Kartu Nama',
                ],
                [
                    'proses_id' => $prosesCetakKartuNama->id,
                    'urutan' => 1,
                    'mesin_id' => $mesinPrinter1->id,
                    'apakah_mengurangi_bahan' => true,
                ]
            );

            // Bahan untuk proses cetak
            ProdukProsesBahan::firstOrCreate(
                [
                    'produk_proses_id' => $produkKartuNamaProses1->id,
                    'bahan_id' => $bahanKertasA4->id,
                ],
                [
                    'jumlah' => 0,
                    'apakah_dipengaruhi_oleh_dimensi' => true,
                ]
            );

            $produkKartuNamaAddon1 = ProdukProses::firstOrCreate(
                [
                    'produk_id' => $produkKartuNama->id,
                    'produk_proses_kategori_id' => $kategoriFinishing->id,
                    'nama' => 'Mata Ayam',
                ],
                [
                    'proses_id' => $prosesMataAyam->id,
                    'harga' => 5000,
                    'mesin_id' => null,
                    'apakah_mengurangi_bahan' => false,
                ]
            );

            // 6.2 Harga Kartu Nama
            ProdukHarga::firstOrCreate(
                [
                    'produk_id' => $produkKartuNama->id,
                    'customer_kategori_id' => $kategoriRetail->id,
                    'jumlah_pesanan_minimal' => 1,
                    'jumlah_pesanan_maksimal' => 1000,
                ],
                ['harga' => 50000]
            );

            ProdukHarga::firstOrCreate(
                [
                    'produk_id' => $produkKartuNama->id,
                    'customer_kategori_id' => $kategoriCorporate->id,
                    'jumlah_pesanan_minimal' => 1,
                    'jumlah_pesanan_maksimal' => 1000,
                ],
                ['harga' => 45000]
            );

            // 7. Sample Stok Bahan
            $tanggalMasuk = Carbon::now()->subDays(5);
            $faktur = BahanMutasiFaktur::create([
                'kode' => generateKode('BF'),
                'supplier_id' => $supplier1->id,
                'total_harga' => 1000000,
                'total_diskon' => 0,
                'total_harga_setelah_diskon' => 1000000,
                'status_pembayaran' => \App\Enums\BahanMutasiFaktur\StatusPembayaranEnum::LUNAS->value,
                'tanggal_jatuh_tempo' => null,
                'created_at' => $tanggalMasuk,
            ]);

            $mutasi = BahanMutasi::create([
                'kode' => generateKode('BM'),
                'tipe' => TipeEnum::MASUK->value,
                'bahan_mutasi_faktur_id' => $faktur->id,
                'bahan_id' => $bahanKertasA4->id,
                'jumlah_satuan_terbesar' => 10,
                'jumlah_satuan_terkecil' => 500,
                'jumlah_mutasi' => 5000,
                'total_harga_mutasi' => 1000000,
                'harga_satuan_terbesar' => 100000,
                'harga_satuan_terkecil' => 200,
                'created_at' => $tanggalMasuk,
            ]);

            BahanStokBatch::create([
                'bahan_id' => $bahanKertasA4->id,
                'bahan_mutasi_id' => $mutasi->id,
                'jumlah_masuk' => 5000,
                'jumlah_tersedia' => 5000,
                'harga_satuan_terkecil' => 200,
                'harga_satuan_terbesar' => 100000,
                'tanggal_masuk' => $tanggalMasuk,
            ]);

            // 8. Assign mesin ke superadmin
            $superAdmin = User::where('email', 'superadmin@gmail.com')->first();
            if ($superAdmin) {
                $allMesinIds = Mesin::pluck('id')->toArray();
                $superAdmin->mesins()->sync($allMesinIds);
            }

            DB::commit();

            $this->command->info('✓ Sample data seeded successfully!');
            $this->command->info('  - 2 Customers');
            $this->command->info('  - 1 Supplier');
            $this->command->info('  - 2 Bahan');
            $this->command->info('  - 5 Master Proses');
            $this->command->info('  - 2 Mesin');
            $this->command->info('  - 1 Produk (Kartu Nama)');
            $this->command->info('  - 1 Stok Bahan');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Error: ' . $e->getMessage());
            throw $e;
        }
    }
}

