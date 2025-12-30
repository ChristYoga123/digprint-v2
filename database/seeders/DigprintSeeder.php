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
use App\Models\TransaksiKalkulasi;
use Illuminate\Support\Facades\DB;
use App\Models\ProdukProsesKategori;
use App\Models\TransaksiKalkulasiProduk;
use App\Models\BahanMutasi;
use App\Models\BahanMutasiFaktur;
use App\Models\BahanStokBatch;
use App\Enums\BahanMutasi\TipeEnum;
use Carbon\Carbon;

class DigprintSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::beginTransaction();

        try {
            // 1. Satuan
            $satuanPcs = Satuan::firstOrCreate(['nama' => 'Pcs']);
            $satuanRim = Satuan::firstOrCreate(['nama' => 'Rim']);
            $satuanKg = Satuan::firstOrCreate(['nama' => 'Kg']);
            $satuanLiter = Satuan::firstOrCreate(['nama' => 'Liter']);
            $satuanMeter = Satuan::firstOrCreate(['nama' => 'Meter']);

            // 2. Customer Kategori
            $kategoriRetail = CustomerKategori::firstOrCreate(
                ['kode' => generateKode('CST'), 'nama' => 'Retail'],
                ['perlu_data_perusahaan' => false]
            );
            $kategoriCorporate = CustomerKategori::firstOrCreate(
                ['kode' => generateKode('CST'), 'nama' => 'Corporate'],
                ['perlu_data_perusahaan' => true]
            );
            $kategoriReseller = CustomerKategori::firstOrCreate(
                ['kode' => generateKode('CST'), 'nama' => 'Reseller'],
                ['perlu_data_perusahaan' => false]
            );

            // 3. Customer
            $customer1 = Customer::firstOrCreate(
                [
                    'customer_kategori_id' => $kategoriRetail->id,
                    'nama' => 'Budi Santoso',
                    'alamat' => 'Jl. Merdeka No. 123, Jakarta Pusat',
                    'no_hp1' => '081234567890',
                    'no_hp2' => '081234567891',
                ]
            );

            $customer2 = Customer::firstOrCreate(
                [
                    'customer_kategori_id' => $kategoriCorporate->id,
                    'nama' => 'PT. Maju Jaya Abadi',
                    'alamat' => 'Jl. Sudirman No. 456, Jakarta Selatan',
                    'no_hp1' => '081234567892',
                    'nama_perusahaan' => 'PT. Maju Jaya Abadi',
                    'alamat_perusahaan' => 'Jl. Sudirman No. 456, Jakarta Selatan',
                ]
            );

            $customer3 = Customer::firstOrCreate(
                [
                    'customer_kategori_id' => $kategoriReseller->id,
                    'nama' => 'Siti Nurhaliza',
                    'alamat' => 'Jl. Gatot Subroto No. 789, Jakarta Selatan',
                    'no_hp1' => '081234567893',
                ]
            );

            // 4. Supplier
            $supplier1 = Supplier::firstOrCreate(
                ['kode' => generateKode('SUP')],
                [
                    'nama_perusahaan' => 'PT. Kertas Indonesia',
                    'nama_sales' => 'Ahmad Fauzi',
                    'no_hp_sales' => '081234567894',
                    'alamat_perusahaan' => 'Jl. Industri No. 100, Bandung',
                    'alamat_gudang' => 'Jl. Industri No. 100, Bandung',
                    'metode_pembayaran1' => 'Bank Transfer',
                    'nomor_rekening1' => '1234567890',
                    'nama_rekening1' => 'PT. Kertas Indonesia',
                    'metode_pembayaran2' => 'Bank Transfer',
                    'nomor_rekening2' => '0987654321',
                    'nama_rekening2' => 'PT. Kertas Indonesia',
                    'is_active' => true,
                    'is_pkp' => true,
                    'npwp' => '01.234.567.8-901.000',
                    'is_po' => true,
                ]
            );

            // 5. Bahan
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

            $bahanTintaHitam = Bahan::firstOrCreate(
                ['kode' => 'BHN-TNT001'],
                [
                    'nama' => 'Tinta Hitam',
                    'satuan_terbesar_id' => $satuanLiter->id,
                    'satuan_terkecil_id' => $satuanLiter->id,
                    'stok_minimal' => 10,
                    'keterangan' => 'Tinta hitam untuk printer',
                ]
            );

            $bahanTintaWarna = Bahan::firstOrCreate(
                ['kode' => 'BHN-TNT002'],
                [
                    'nama' => 'Tinta Warna',
                    'satuan_terbesar_id' => $satuanLiter->id,
                    'satuan_terkecil_id' => $satuanLiter->id,
                    'stok_minimal' => 5,
                    'keterangan' => 'Tinta warna CMYK untuk printer',
                ]
            );

            $bahanKertasKarton = Bahan::firstOrCreate(
                ['kode' => 'BHN-KRT002'],
                [
                    'nama' => 'Kertas Karton 250gsm',
                    'satuan_terbesar_id' => $satuanRim->id,
                    'satuan_terkecil_id' => $satuanPcs->id,
                    'stok_minimal' => 10,
                    'keterangan' => 'Kertas karton untuk kartu nama dan undangan',
                ]
            );

            // 6. Proses Kategori
            $kategoriDesign = ProdukProsesKategori::firstOrCreate(['nama' => 'Pra Produksi']);
            $kategoriProduksi = ProdukProsesKategori::firstOrCreate(['nama' => 'Produksi']);
            $kategoriFinishing = ProdukProsesKategori::firstOrCreate(['nama' => 'Finishing']);

            // 6.5 Master Proses
            // Design Proses (dengan harga default)
            $prosesDesignSimple = Proses::firstOrCreate(
                ['nama' => 'Design Simple', 'produk_proses_kategori_id' => $kategoriDesign->id],
                ['harga_default' => 50000]
            );
            $prosesDesignPremium = Proses::firstOrCreate(
                ['nama' => 'Design Premium', 'produk_proses_kategori_id' => $kategoriDesign->id],
                ['harga_default' => 100000]
            );
            $prosesDesignMinimalis = Proses::firstOrCreate(
                ['nama' => 'Design Minimalis', 'produk_proses_kategori_id' => $kategoriDesign->id],
                ['harga_default' => 75000]
            );
            $prosesDesignElegant = Proses::firstOrCreate(
                ['nama' => 'Design Elegant', 'produk_proses_kategori_id' => $kategoriDesign->id],
                ['harga_default' => 150000]
            );

            // Produksi Proses (tanpa harga default karena harga berdasarkan tiering produk)
            $prosesCetakKartuNama = Proses::firstOrCreate(
                ['nama' => 'Cetak Kartu Nama', 'produk_proses_kategori_id' => $kategoriProduksi->id],
                ['harga_default' => null]
            );
            $prosesCetakUndangan = Proses::firstOrCreate(
                ['nama' => 'Cetak Undangan', 'produk_proses_kategori_id' => $kategoriProduksi->id],
                ['harga_default' => null]
            );
            $prosesCetakBrosur = Proses::firstOrCreate(
                ['nama' => 'Cetak Brosur', 'produk_proses_kategori_id' => $kategoriProduksi->id],
                ['harga_default' => null]
            );
            $prosesCetakFlyer = Proses::firstOrCreate(
                ['nama' => 'Cetak Flyer', 'produk_proses_kategori_id' => $kategoriProduksi->id],
                ['harga_default' => null]
            );
            $prosesLaminating = Proses::firstOrCreate(
                ['nama' => 'Laminating', 'produk_proses_kategori_id' => $kategoriProduksi->id],
                ['harga_default' => null]
            );
            $prosesCutting = Proses::firstOrCreate(
                ['nama' => 'Cutting', 'produk_proses_kategori_id' => $kategoriProduksi->id],
                ['harga_default' => null]
            );

            // Finishing Proses (dengan harga default)
            $prosesMataAyam = Proses::firstOrCreate(
                ['nama' => 'Mata Ayam', 'produk_proses_kategori_id' => $kategoriFinishing->id],
                ['harga_default' => 5000]
            );
            $prosesStandBanner = Proses::firstOrCreate(
                ['nama' => 'Stand Banner', 'produk_proses_kategori_id' => $kategoriFinishing->id],
                ['harga_default' => 15000]
            );
            $prosesStaples = Proses::firstOrCreate(
                ['nama' => 'Staples', 'produk_proses_kategori_id' => $kategoriFinishing->id],
                ['harga_default' => 3000]
            );

            // 7. Mesin
            $mesinPrinter1 = Mesin::firstOrCreate(
                ['kode' => 'MES-PRT001'],
                ['nama' => 'Printer Canon IP2770']
            );

            $mesinPrinter2 = Mesin::firstOrCreate(
                ['kode' => 'MES-PRT002'],
                ['nama' => 'Printer Epson L3110']
            );

            $mesinCutting = Mesin::firstOrCreate(
                ['kode' => 'MES-CUT001'],
                ['nama' => 'Mesin Cutting Manual']
            );

            $mesinLaminating = Mesin::firstOrCreate(
                ['kode' => 'MES-LAM001'],
                ['nama' => 'Mesin Laminating']
            );

            // 9. Produk
            $produkKartuNama = Produk::firstOrCreate(
                ['kode' => 'PRD-KTN001'],
                [
                    'nama' => 'Kartu Nama',
                    'apakah_perlu_custom_dimensi' => true,
                ]
            );

            $produkUndangan = Produk::firstOrCreate(
                ['kode' => 'PRD-UND001'],
                [
                    'nama' => 'Undangan',
                    'apakah_perlu_custom_dimensi' => true,
                ]
            );

            $produkBrosur = Produk::firstOrCreate(
                ['kode' => 'PRD-BRS001'],
                [
                    'nama' => 'Brosur A4',
                    'apakah_perlu_custom_dimensi' => false,
                ]
            );

            $produkFlyer = Produk::firstOrCreate(
                ['kode' => 'PRD-FLY001'],
                [
                    'nama' => 'Flyer A5',
                    'apakah_perlu_custom_dimensi' => false,
                ]
            );

            // 10. Produk Proses untuk Kartu Nama
            // 10.1 Design Options untuk Kartu Nama (urutan 0)
            $produkKartuNamaDesign1 = ProdukProses::firstOrCreate(
                [
                    'produk_id' => $produkKartuNama->id,
                    'produk_proses_kategori_id' => $kategoriDesign->id,
                    'nama' => 'Design Simple',
                ],
                [
                    'proses_id' => $prosesDesignSimple->id,
                    'harga' => 50000,
                    'urutan' => 0, // Design selalu di awal
                    'apakah_mengurangi_bahan' => false,
                ]
            );

            $produkKartuNamaDesign2 = ProdukProses::firstOrCreate(
                [
                    'produk_id' => $produkKartuNama->id,
                    'produk_proses_kategori_id' => $kategoriDesign->id,
                    'nama' => 'Design Premium',
                ],
                [
                    'proses_id' => $prosesDesignPremium->id,
                    'harga' => 100000,
                    'urutan' => 0,
                    'apakah_mengurangi_bahan' => false,
                ]
            );

            // 10.2 Proses Produksi: Cetak (urutan 1)
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

            // 10.3 Produk Proses Bahan untuk Kartu Nama
            ProdukProsesBahan::firstOrCreate(
                [
                    'produk_proses_id' => $produkKartuNamaProses1->id,
                    'bahan_id' => $bahanKertasKarton->id,
                ],
                [
                    'jumlah' => 0, // Dipengaruhi dimensi
                    'apakah_dipengaruhi_oleh_dimensi' => true,
                ]
            );

            ProdukProsesBahan::firstOrCreate(
                [
                    'produk_proses_id' => $produkKartuNamaProses1->id,
                    'bahan_id' => $bahanTintaHitam->id,
                ],
                [
                    'jumlah' => 10, // Fixed, tidak dipengaruhi dimensi
                    'apakah_dipengaruhi_oleh_dimensi' => false,
                ]
            );

            // 10.4 Proses Produksi: Laminating untuk Kartu Nama (setelah cetak, urutan 2)
            $produkKartuNamaProses2 = ProdukProses::firstOrCreate(
                [
                    'produk_id' => $produkKartuNama->id,
                    'produk_proses_kategori_id' => $kategoriProduksi->id,
                    'nama' => 'Laminating',
                ],
                [
                    'proses_id' => $prosesLaminating->id,
                    'urutan' => 2,
                    'mesin_id' => $mesinLaminating->id,
                    'apakah_mengurangi_bahan' => false,
                ]
            );

            // 10.5 Finishing/Addon: Mata Ayam untuk Kartu Nama (tanpa mesin)
            $produkKartuNamaAddon1 = ProdukProses::firstOrCreate(
                [
                    'produk_id' => $produkKartuNama->id,
                    'produk_proses_kategori_id' => $kategoriFinishing->id,
                    'nama' => 'Mata Ayam',
                ],
                [
                    'proses_id' => $prosesMataAyam->id,
                    'harga' => 5000,
                    'mesin_id' => null, // Manual, tidak pakai mesin
                    'apakah_mengurangi_bahan' => false,
                ]
            );

            // 11. Produk Proses untuk Undangan
            // 11.1 Design Options untuk Undangan (urutan 0)
            $produkUndanganDesign1 = ProdukProses::firstOrCreate(
                [
                    'produk_id' => $produkUndangan->id,
                    'produk_proses_kategori_id' => $kategoriDesign->id,
                    'nama' => 'Design Minimalis',
                ],
                [
                    'proses_id' => $prosesDesignMinimalis->id,
                    'harga' => 75000,
                    'urutan' => 0,
                    'apakah_mengurangi_bahan' => false,
                ]
            );

            $produkUndanganDesign2 = ProdukProses::firstOrCreate(
                [
                    'produk_id' => $produkUndangan->id,
                    'produk_proses_kategori_id' => $kategoriDesign->id,
                    'nama' => 'Design Elegant',
                ],
                [
                    'proses_id' => $prosesDesignElegant->id,
                    'harga' => 150000,
                    'urutan' => 0,
                    'apakah_mengurangi_bahan' => false,
                ]
            );

            $produkUndanganDesign3 = ProdukProses::firstOrCreate(
                [
                    'produk_id' => $produkUndangan->id,
                    'produk_proses_kategori_id' => $kategoriDesign->id,
                    'nama' => 'Design Premium',
                ],
                [
                    'proses_id' => $prosesDesignPremium->id,
                    'harga' => 250000,
                    'urutan' => 0,
                    'apakah_mengurangi_bahan' => false,
                ]
            );

            // 11.2 Proses Produksi: Cetak (urutan 1)
            $produkUndanganProses1 = ProdukProses::firstOrCreate(
                [
                    'produk_id' => $produkUndangan->id,
                    'produk_proses_kategori_id' => $kategoriProduksi->id,
                    'nama' => 'Cetak Undangan',
                ],
                [
                    'proses_id' => $prosesCetakUndangan->id,
                    'urutan' => 1,
                    'mesin_id' => $mesinPrinter2->id,
                    'apakah_mengurangi_bahan' => true,
                ]
            );

            // 11.3 Produk Proses Bahan untuk Undangan
            ProdukProsesBahan::firstOrCreate(
                [
                    'produk_proses_id' => $produkUndanganProses1->id,
                    'bahan_id' => $bahanKertasKarton->id,
                ],
                [
                    'jumlah' => 0, // Dipengaruhi dimensi
                    'apakah_dipengaruhi_oleh_dimensi' => true,
                ]
            );

            ProdukProsesBahan::firstOrCreate(
                [
                    'produk_proses_id' => $produkUndanganProses1->id,
                    'bahan_id' => $bahanTintaWarna->id,
                ],
                [
                    'jumlah' => 20, // Fixed
                    'apakah_dipengaruhi_oleh_dimensi' => false,
                ]
            );

            // 11.4 Proses Produksi: Cutting untuk Undangan (setelah cetak, urutan 2)
            $produkUndanganProses2 = ProdukProses::firstOrCreate(
                [
                    'produk_id' => $produkUndangan->id,
                    'produk_proses_kategori_id' => $kategoriProduksi->id,
                    'nama' => 'Cutting',
                ],
                [
                    'proses_id' => $prosesCutting->id,
                    'urutan' => 2,
                    'mesin_id' => $mesinCutting->id,
                    'apakah_mengurangi_bahan' => false,
                ]
            );

            // 11.5 Proses Produksi: Laminating untuk Undangan (setelah cutting, urutan 3)
            $produkUndanganProses3 = ProdukProses::firstOrCreate(
                [
                    'produk_id' => $produkUndangan->id,
                    'produk_proses_kategori_id' => $kategoriProduksi->id,
                    'nama' => 'Laminating',
                ],
                [
                    'proses_id' => $prosesLaminating->id,
                    'urutan' => 3,
                    'mesin_id' => $mesinLaminating->id,
                    'apakah_mengurangi_bahan' => false,
                ]
            );

            // 11.6 Finishing/Addon: Stand Banner untuk Undangan (tanpa mesin)
            $produkUndanganAddon1 = ProdukProses::firstOrCreate(
                [
                    'produk_id' => $produkUndangan->id,
                    'produk_proses_kategori_id' => $kategoriFinishing->id,
                    'nama' => 'Stand Banner',
                ],
                [
                    'proses_id' => $prosesStandBanner->id,
                    'harga' => 15000,
                    'mesin_id' => null, // Manual, tidak pakai mesin
                    'apakah_mengurangi_bahan' => false,
                ]
            );

            // 12. Produk Proses untuk Brosur
            // (Tidak ada Design - customer biasanya sudah punya design sendiri)
            // 12.1 Proses Produksi: Cetak (urutan 1)
            $produkBrosurProses1 = ProdukProses::firstOrCreate(
                [
                    'produk_id' => $produkBrosur->id,
                    'produk_proses_kategori_id' => $kategoriProduksi->id,
                    'nama' => 'Cetak Brosur',
                ],
                [
                    'proses_id' => $prosesCetakBrosur->id,
                    'urutan' => 1,
                    'mesin_id' => $mesinPrinter1->id,
                    'apakah_mengurangi_bahan' => true,
                ]
            );

            // 12.2 Produk Proses Bahan untuk Brosur
            ProdukProsesBahan::firstOrCreate(
                [
                    'produk_proses_id' => $produkBrosurProses1->id,
                    'bahan_id' => $bahanKertasA4->id,
                ],
                [
                    'jumlah' => 1, // Fixed, tidak dipengaruhi dimensi
                    'apakah_dipengaruhi_oleh_dimensi' => false,
                ]
            );

            ProdukProsesBahan::firstOrCreate(
                [
                    'produk_proses_id' => $produkBrosurProses1->id,
                    'bahan_id' => $bahanTintaWarna->id,
                ],
                [
                    'jumlah' => 15, // Fixed
                    'apakah_dipengaruhi_oleh_dimensi' => false,
                ]
            );

            // 12.3 Finishing/Addon: Staples untuk Brosur (tanpa mesin)
            $produkBrosurAddon1 = ProdukProses::firstOrCreate(
                [
                    'produk_id' => $produkBrosur->id,
                    'produk_proses_kategori_id' => $kategoriFinishing->id,
                    'nama' => 'Staples',
                ],
                [
                    'proses_id' => $prosesStaples->id,
                    'harga' => 3000,
                    'mesin_id' => null, // Manual, tidak pakai mesin
                    'apakah_mengurangi_bahan' => false,
                ]
            );

            // 13. Produk Proses untuk Flyer
            // (Tidak ada Design - customer biasanya sudah punya design sendiri)
            // 13.1 Proses Produksi: Cetak (urutan 1)
            $produkFlyerProses1 = ProdukProses::firstOrCreate(
                [
                    'produk_id' => $produkFlyer->id,
                    'produk_proses_kategori_id' => $kategoriProduksi->id,
                    'nama' => 'Cetak Flyer',
                ],
                [
                    'proses_id' => $prosesCetakFlyer->id,
                    'urutan' => 1,
                    'mesin_id' => $mesinPrinter2->id,
                    'apakah_mengurangi_bahan' => true,
                ]
            );

            // 13.2 Produk Proses Bahan untuk Flyer
            ProdukProsesBahan::firstOrCreate(
                [
                    'produk_proses_id' => $produkFlyerProses1->id,
                    'bahan_id' => $bahanKertasA4->id,
                ],
                [
                    'jumlah' => 1, // Fixed
                    'apakah_dipengaruhi_oleh_dimensi' => false,
                ]
            );

            ProdukProsesBahan::firstOrCreate(
                [
                    'produk_proses_id' => $produkFlyerProses1->id,
                    'bahan_id' => $bahanTintaWarna->id,
                ],
                [
                    'jumlah' => 12, // Fixed
                    'apakah_dipengaruhi_oleh_dimensi' => false,
                ]
            );

            // 12. Produk Harga (harga per kategori customer)
            // Kartu Nama
            ProdukHarga::firstOrCreate(
                [
                    'produk_id' => $produkKartuNama->id,
                    'customer_kategori_id' => $kategoriRetail->id,
                ],
                ['harga' => 50000]
            );

            ProdukHarga::firstOrCreate(
                [
                    'produk_id' => $produkKartuNama->id,
                    'customer_kategori_id' => $kategoriCorporate->id,
                ],
                ['harga' => 45000]
            );

            ProdukHarga::firstOrCreate(
                [
                    'produk_id' => $produkKartuNama->id,
                    'customer_kategori_id' => $kategoriReseller->id,
                ],
                ['harga' => 40000]
            );

            // Undangan
            ProdukHarga::firstOrCreate(
                [
                    'produk_id' => $produkUndangan->id,
                    'customer_kategori_id' => $kategoriRetail->id,
                ],
                ['harga' => 3000]
            );

            ProdukHarga::firstOrCreate(
                [
                    'produk_id' => $produkUndangan->id,
                    'customer_kategori_id' => $kategoriCorporate->id,
                ],
                ['harga' => 2500]
            );

            ProdukHarga::firstOrCreate(
                [
                    'produk_id' => $produkUndangan->id,
                    'customer_kategori_id' => $kategoriReseller->id,
                ],
                ['harga' => 2000]
            );

            // Brosur
            ProdukHarga::firstOrCreate(
                [
                    'produk_id' => $produkBrosur->id,
                    'customer_kategori_id' => $kategoriRetail->id,
                ],
                ['harga' => 2000]
            );

            ProdukHarga::firstOrCreate(
                [
                    'produk_id' => $produkBrosur->id,
                    'customer_kategori_id' => $kategoriCorporate->id,
                ],
                ['harga' => 1500]
            );

            ProdukHarga::firstOrCreate(
                [
                    'produk_id' => $produkBrosur->id,
                    'customer_kategori_id' => $kategoriReseller->id,
                ],
                ['harga' => 1200]
            );

            // Flyer
            ProdukHarga::firstOrCreate(
                [
                    'produk_id' => $produkFlyer->id,
                    'customer_kategori_id' => $kategoriRetail->id,
                ],
                ['harga' => 1500]
            );

            ProdukHarga::firstOrCreate(
                [
                    'produk_id' => $produkFlyer->id,
                    'customer_kategori_id' => $kategoriCorporate->id,
                ],
                ['harga' => 1200]
            );

            ProdukHarga::firstOrCreate(
                [
                    'produk_id' => $produkFlyer->id,
                    'customer_kategori_id' => $kategoriReseller->id,
                ],
                ['harga' => 1000]
            );

            // 16. Operator Users dengan mesin assignment
            $operator1 = User::firstOrCreate(
                ['nik' => '1111111111'],
                [
                    'name' => 'Operator Printer',
                    'email' => 'operator1@gmail.com',
                    'no_hp' => '081234567891',
                    'alamat' => 'Jl. Operator 1',
                    'password' => bcrypt('password'),
                    'is_active' => true,
                ]
            );
            $operator1->assignRole('operator');
            $operator1->mesins()->sync([$mesinPrinter1->id, $mesinPrinter2->id]);

            $operator2 = User::firstOrCreate(
                ['nik' => '2222222222'],
                [
                    'name' => 'Operator Finishing',
                    'email' => 'operator2@gmail.com',
                    'no_hp' => '081234567892',
                    'alamat' => 'Jl. Operator 2',
                    'password' => bcrypt('password'),
                    'is_active' => true,
                ]
            );
            $operator2->assignRole('operator');
            $operator2->mesins()->sync([$mesinLaminating->id, $mesinCutting->id]);

            // 17. Assign semua mesin ke superadmin
            $superAdmin = User::where('email', 'superadmin@gmail.com')->first();
            if ($superAdmin) {
                $allMesinIds = Mesin::pluck('id')->toArray();
                $superAdmin->mesins()->sync($allMesinIds);
            }

            // 18. Buat batch bahan untuk testing mutasi (FIFO)
            // Buat beberapa batch per bahan dengan tanggal berbeda
            $bahanList = [
                $bahanKertasA4,
                $bahanTintaHitam,
                $bahanTintaWarna,
                $bahanKertasKarton,
            ];

            foreach ($bahanList as $bahan) {
                // Buat 3 batch dengan tanggal berbeda (untuk testing FIFO)
                for ($i = 1; $i <= 3; $i++) {
                    $tanggalMasuk = Carbon::now()->subDays(10 - ($i * 2)); // Batch 1: 8 hari lalu, Batch 2: 6 hari lalu, Batch 3: 4 hari lalu
                    
                    // Buat faktur dummy untuk mutasi masuk
                    $faktur = BahanMutasiFaktur::create([
                        'kode' => generateKode('BF'),
                        'supplier_id' => $supplier1->id,
                        'total_harga' => 1000000 * $i, // Harga berbeda per batch
                        'total_diskon' => 0,
                        'total_harga_setelah_diskon' => 1000000 * $i,
                        'status_pembayaran' => \App\Enums\BahanMutasiFaktur\StatusPembayaranEnum::LUNAS->value,
                        'tanggal_jatuh_tempo' => null, // Lunas tidak perlu jatuh tempo
                        'created_at' => $tanggalMasuk,
                        'updated_at' => $tanggalMasuk,
                    ]);

                    // Tentukan jumlah dan harga berdasarkan bahan
                    $jumlahSatuanTerbesar = 10 * $i; // Batch 1: 10, Batch 2: 20, Batch 3: 30
                    $isiPerSatuanTerbesar = 500; // Contoh: 500 pcs per rim
                    $jumlahMasukTerkecil = $jumlahSatuanTerbesar * $isiPerSatuanTerbesar;
                    $hargaSatuanTerbesar = 100000 * $i; // Harga berbeda per batch
                    $hargaSatuanTerkecil = $hargaSatuanTerbesar / $isiPerSatuanTerbesar;
                    $totalHargaMutasi = $jumlahSatuanTerbesar * $hargaSatuanTerbesar;

                    // Buat mutasi masuk
                    $mutasi = BahanMutasi::create([
                        'kode' => generateKode('BM'),
                        'tipe' => TipeEnum::MASUK->value,
                        'bahan_mutasi_faktur_id' => $faktur->id,
                        'bahan_id' => $bahan->id,
                        'jumlah_satuan_terbesar' => $jumlahSatuanTerbesar,
                        'jumlah_satuan_terkecil' => $isiPerSatuanTerbesar,
                        'jumlah_mutasi' => $jumlahMasukTerkecil,
                        'total_harga_mutasi' => $totalHargaMutasi,
                        'harga_satuan_terbesar' => $hargaSatuanTerbesar,
                        'harga_satuan_terkecil' => $hargaSatuanTerkecil,
                        'created_at' => $tanggalMasuk,
                        'updated_at' => $tanggalMasuk,
                    ]);

                    // Buat batch untuk FIFO tracking
                    BahanStokBatch::create([
                        'bahan_id' => $bahan->id,
                        'bahan_mutasi_id' => $mutasi->id,
                        'jumlah_masuk' => $jumlahMasukTerkecil,
                        'jumlah_tersedia' => $jumlahMasukTerkecil, // Masih full, belum digunakan
                        'harga_satuan_terkecil' => $hargaSatuanTerkecil,
                        'harga_satuan_terbesar' => $hargaSatuanTerbesar,
                        'tanggal_masuk' => $tanggalMasuk,
                        'created_at' => $tanggalMasuk,
                        'updated_at' => $tanggalMasuk,
                    ]);
                }
            }

            DB::commit();

            $this->command->info('Seeder berhasil dijalankan!');
            $this->command->info('Data yang dibuat:');
            $this->command->info('- 3 Customer Kategori');
            $this->command->info('- 3 Customer');
            $this->command->info('- 1 Supplier');
            $this->command->info('- 4 Bahan');
            $this->command->info('- 3 Proses Kategori');
            $this->command->info('- 13 Master Proses (Design, Produksi, Finishing)');
            $this->command->info('- 4 Mesin');
            $this->command->info('- 18+ Produk Proses (Design, Produksi dengan mesin, Finishing tanpa mesin)');
            $this->command->info('- 4 Produk');
            $this->command->info('- 2 Operator Users dengan mesin assignment');
            $this->command->info('- 12 Bahan Stok Batch (3 batch per bahan untuk testing FIFO)');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('Error: ' . $e->getMessage());
            throw $e;
        }
    }
}

