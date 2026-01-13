<?php

namespace App\Filament\Admin\Resources\DeskprintResource\Pages;

use App\Filament\Admin\Resources\DeskprintResource;
use App\Models\Customer;
use App\Models\ProdukHarga;
use App\Models\ProdukProses;
use App\Models\KaryawanPekerjaan;
use App\Models\TransaksiKalkulasi;
use App\Enums\KaryawanPekerjaan\TipeEnum;
use App\Models\ProdukProsesKategori;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Auth;

class ManageDeskprints extends ManageRecords
{
    protected static string $resource = DeskprintResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->closeModalByClickingAway(false)
                ->mutateFormDataUsing(function (array $data): array {
                    // Hitung total_harga_kalkulasi sebelum save
                    if (isset($data['customer_id']) && isset($data['produks']) && is_array($data['produks'])) {
                        $data['total_harga_kalkulasi'] = $this->calculateTotalHargaKalkulasi($data['customer_id'], $data['produks']);
                    } else {
                        $data['total_harga_kalkulasi'] = 0;
                    }
                    
                    // Set created_by ke user yang sedang login
                    $data['created_by'] = Auth::id();
                    
                    return $data;
                })
                ->after(function ($record) {
                    // Update total_harga_produk untuk setiap produk setelah create
                    $this->updateTotalHargaProduk($record);
                    // Update total_harga_kalkulasi setelah semua produk dibuat
                    $this->updateTotalHargaKalkulasi($record);
                    
                    // Catat karyawan yang membuat kalkulasi
                    KaryawanPekerjaan::create([
                        'karyawan_id' => Auth::id(),
                        'tipe' => TipeEnum::NORMAL,
                        'karyawan_pekerjaan_type' => TransaksiKalkulasi::class,
                        'karyawan_pekerjaan_id' => $record->id,
                    ]);
                })
                ->modalHeading('Deskprint'),
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return 'Deskprint';
    }

    protected function updateTotalHargaProduk($record): void
    {
        if (!$record || !$record->customer_id) {
            return;
        }

        // Refresh record untuk memastikan data terbaru (termasuk addons yang baru di-save)
        $record->refresh();
        $record->load('transaksiKalkulasiProduks');

        $customer = Customer::find($record->customer_id);
        if (!$customer) {
            return;
        }

        foreach ($record->transaksiKalkulasiProduks as $produk) {
            // Parse jumlah untuk menentukan tier
            $jumlah = (int) ($produk->jumlah ?? 1);
            if ($jumlah <= 0) $jumlah = 1;
            
            // Ambil harga satuan berdasarkan tiering
            $hargaSatuan = DeskprintResource::getHargaSatuanByTiering(
                $produk->produk_id,
                $customer->customer_kategori_id,
                $jumlah
            );

            // Konversi ke float untuk perhitungan
            $jumlahFloat = (float) $jumlah;

            $panjang = $produk->panjang ? (float) $produk->panjang : 1.0;
            if ($panjang <= 0) $panjang = 1.0;

            $lebar = $produk->lebar ? (float) $produk->lebar : 1.0;
            if ($lebar <= 0) $lebar = 1.0;

            // Hitung total produk
            $totalProduk = $hargaSatuan * $jumlahFloat * $panjang * $lebar;

            // Tambah harga design (single value, bukan array)
            if ($produk->design_id) {
                $designProses = ProdukProses::where('id', $produk->design_id)
                    ->where('produk_proses_kategori_id', ProdukProsesKategori::praProduksiId()) // Design
                    ->first();
                if ($designProses) {
                    $totalProduk += (float) ($designProses->harga ?? 0);
                }
            }

            // Tambah harga addon (dikali jumlah pesanan)
            // Model sudah punya cast 'addons' => 'json', jadi Laravel auto-decode JSON ke array
            if ($produk->addons && is_array($produk->addons) && !empty($produk->addons)) {
                // Filter untuk memastikan semua ID adalah integer positif
                $addonsArray = array_filter(array_map('intval', $produk->addons), fn($id) => $id > 0);
                
                if (!empty($addonsArray)) {
                    $totalAddon = ProdukProses::whereIn('id', $addonsArray)
                        ->where('produk_proses_kategori_id', ProdukProsesKategori::finishingId()) // Finishing/Addon
                        ->whereNotNull('harga')
                        ->sum('harga');
                    $totalProduk += (float) ($totalAddon * $jumlahFloat);
                }
            }

            // Cek Harga Minimal Produk
            $produkModel = \App\Models\Produk::find($produk->produk_id);
            if ($produkModel && $produkModel->harga_minimal > 0) {
                if ($totalProduk < $produkModel->harga_minimal) {
                    $totalProduk = (float) $produkModel->harga_minimal;
                }
            }

            // Update total_harga_produk
            $produk->update([
                'total_harga_produk' => (int) round($totalProduk)
            ]);
        }
    }

    protected function calculateTotalHargaKalkulasi($customerId, array $produks): int
    {
        if (!$customerId || empty($produks)) {
            return 0;
        }

        $customer = Customer::find($customerId);
        if (!$customer) {
            return 0;
        }

        $totalKeseluruhan = 0;

        foreach ($produks as $produk) {
            if (!isset($produk['produk_id'])) continue;

            // Parse jumlah untuk menentukan tier
            $jumlahRaw = $produk['jumlah'] ?? 1;
            if (is_string($jumlahRaw)) {
                $jumlah = (int) str_replace([',', ' ', '.'], '', $jumlahRaw);
            } else {
                $jumlah = (int) $jumlahRaw;
            }
            if ($jumlah <= 0) $jumlah = 1;
            
            // Ambil harga satuan berdasarkan tiering
            $hargaSatuan = DeskprintResource::getHargaSatuanByTiering(
                $produk['produk_id'],
                $customer->customer_kategori_id,
                $jumlah
            );
            
            // Konversi ke float untuk perhitungan
            $jumlahFloat = (float) $jumlah;

            // Parse panjang
            $panjangRaw = $produk['panjang'] ?? null;
            if ($panjangRaw === null || $panjangRaw === '') {
                $panjang = 1.0;
            } else {
                if (is_string($panjangRaw)) {
                    $panjang = (float) str_replace([',', ' '], '', $panjangRaw);
                } else {
                    $panjang = (float) $panjangRaw;
                }
                if ($panjang <= 0) $panjang = 1.0;
            }

            // Parse lebar
            $lebarRaw = $produk['lebar'] ?? null;
            if ($lebarRaw === null || $lebarRaw === '') {
                $lebar = 1.0;
            } else {
                if (is_string($lebarRaw)) {
                    $lebar = (float) str_replace([',', ' '], '', $lebarRaw);
                } else {
                    $lebar = (float) $lebarRaw;
                }
                if ($lebar <= 0) $lebar = 1.0;
            }

            // Hitung total produk
            $totalProduk = $hargaSatuan * $jumlahFloat * $panjang * $lebar;

            // Tambah harga design (single value, bukan array)
            if (isset($produk['design_id']) && !empty($produk['design_id']) && $produk['design_id'] !== 'none') {
                $designProses = ProdukProses::where('id', $produk['design_id'])
                    ->where('produk_proses_kategori_id', ProdukProsesKategori::praProduksiId()) // Design
                    ->first();
                if ($designProses) {
                    $totalProduk += (float) ($designProses->harga ?? 0);
                }
            }

            // Tambah harga addon (dikali jumlah pesanan)
            // Data dari form akan selalu array atau null
            if (isset($produk['addons']) && is_array($produk['addons']) && !empty($produk['addons'])) {
                // Filter untuk memastikan semua ID adalah integer positif
                $addonsArray = array_filter(array_map('intval', $produk['addons']), fn($id) => $id > 0);
                
                if (!empty($addonsArray)) {
                    $totalAddon = ProdukProses::whereIn('id', $addonsArray)
                        ->where('produk_proses_kategori_id', ProdukProsesKategori::finishingId()) // Finishing/Addon
                        ->whereNotNull('harga')
                        ->sum('harga');
                    $totalProduk += (float) ($totalAddon * $jumlahFloat);
                }
            }

            $totalKeseluruhan += $totalProduk;
        }

        return (int) round($totalKeseluruhan);
    }

    protected function updateTotalHargaKalkulasi($record): void
    {
        if (!$record || !$record->customer_id) {
            return;
        }

        $customer = Customer::find($record->customer_id);
        if (!$customer) {
            return;
        }

        // Reload transaksiKalkulasiProduks untuk mendapatkan data terbaru
        $record->refresh();
        $record->load('transaksiKalkulasiProduks');

        $totalKeseluruhan = 0;

        foreach ($record->transaksiKalkulasiProduks as $produk) {
            // Gunakan total_harga_produk yang sudah dihitung
            $totalKeseluruhan += $produk->total_harga_produk ?? 0;
        }

        // Update total_harga_kalkulasi
        $record->update([
            'total_harga_kalkulasi' => (int) round($totalKeseluruhan)
        ]);
    }
}
