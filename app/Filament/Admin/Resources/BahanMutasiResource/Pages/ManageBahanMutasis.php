<?php

namespace App\Filament\Admin\Resources\BahanMutasiResource\Pages;

use App\Models\PO;
use Filament\Actions;
use App\Models\BahanMutasi;
use App\Models\BahanStokBatch;
use App\Models\BahanMutasiFaktur;
use App\Models\PencatatanKeuangan;
use App\Enums\BahanMutasi\TipeEnum;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\BahanMutasiPenggunaanBatch;
use Filament\Resources\Pages\ManageRecords;
use App\Enums\BahanMutasiFaktur\StatusPembayaranEnum;
use App\Filament\Admin\Resources\BahanMutasiResource;

class ManageBahanMutasis extends ManageRecords
{
    protected static string $resource = BahanMutasiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->using(function (array $data) {
                    $parse = fn ($value) => (int) str_replace([',', ' '], '', (string) ($value ?? 0));

                    // Jika tipe KELUAR, buat bahan_mutasi dari repeater dengan FIFO logic
                    if ($data['tipe'] == TipeEnum::KELUAR->value) {
                        $firstRecord = null;
                        if (!empty($data['bahanMutasiDetails'])) {
                            foreach ($data['bahanMutasiDetails'] as $detail) {
                                $jumlahKeluar = $parse($detail['jumlah_mutasi'] ?? '0');
                                
                                if (empty($detail['bahan_id']) || $jumlahKeluar <= 0) {
                                    continue;
                                }

                                // Cek stok tersedia dari batches
                                $availableStok = BahanStokBatch::where('bahan_id', $detail['bahan_id'])
                                    ->where('jumlah_tersedia', '>', 0)
                                    ->sum('jumlah_tersedia');

                                if ($availableStok < $jumlahKeluar) {
                                    throw new \Exception("Stok tidak mencukupi. Stok tersedia: {$availableStok}, yang diminta: {$jumlahKeluar}");
                                }

                                $mutasi = BahanMutasi::create([
                                    'kode' => generateKode('BM'),
                                    'tipe' => TipeEnum::KELUAR->value,
                                    'bahan_id' => $detail['bahan_id'],
                                    'jumlah_mutasi' => $jumlahKeluar,
                                ]);

                                // Proses FIFO: ambil dari batch yang paling lama
                                $sisaKeluar = $jumlahKeluar;
                                $batches = BahanStokBatch::getAvailableBatches($detail['bahan_id'], $jumlahKeluar);

                                foreach ($batches as $batch) {
                                    if ($sisaKeluar <= 0) {
                                        break;
                                    }

                                    $jumlahDigunakan = min($sisaKeluar, $batch->jumlah_tersedia);
                                    
                                    // Buat record usage
                                    BahanMutasiPenggunaanBatch::create([
                                        'bahan_mutasi_id' => $mutasi->id,
                                        'bahan_stok_batch_id' => $batch->id,
                                        'jumlah_digunakan' => $jumlahDigunakan,
                                    ]);

                                    // Kurangi jumlah_tersedia dari batch
                                    $batch->reduceStock($jumlahDigunakan);

                                    $sisaKeluar -= $jumlahDigunakan;
                                }
                                
                                if (!$firstRecord) {
                                    $firstRecord = $mutasi;
                                }
                            }
                        }
                        return $firstRecord;
                    }
                    
                    // Jika tipe MASUK, buat faktur dulu kemudian buat detail mutasi
                    if ($data['tipe'] == TipeEnum::MASUK->value) {
                        $totalHargaFaktur = $parse($data['total_harga_faktur'] ?? '0');
                        $totalDiskonFaktur = $parse($data['total_diskon'] ?? '0');
                        $totalSetelahDiskon = max(0, $totalHargaFaktur - $totalDiskonFaktur);

                        $fakturData = [
                            'kode' => generateKode('BF'),
                            'supplier_id' => $data['supplier_id'],
                            'po_id' => $data['po_id'] ?? null,
                            'total_harga' => $totalHargaFaktur,
                            'total_diskon' => $totalDiskonFaktur,
                            'total_harga_setelah_diskon' => $totalSetelahDiskon,
                            'status_pembayaran' => $data['status_pembayaran'],
                            'tanggal_pembayaran' => $data['tanggal_pembayaran'] ?? null,
                            'metode_pembayaran' => $data['metode_pembayaran'] ?? null,
                            'tanggal_jatuh_tempo' => $data['tanggal_jatuh_tempo'] ?? null,
                        ];
                        
                        $faktur = BahanMutasiFaktur::create($fakturData);

                        // Simpan bukti faktur ke Spatie Media Library collection
                        if (!empty($data['bukti_faktur'])) {
                            try {
                                // FileUpload menyimpan file ke storage, path ada di $data['bukti_faktur']
                                $filePath = is_array($data['bukti_faktur']) ? $data['bukti_faktur'][0] : $data['bukti_faktur'];
                                
                                if (is_string($filePath) && !empty($filePath)) {
                                    // Simpan file ke Spatie Media Library collection 'bahan_mutasi_faktur'
                                    $faktur->addMediaFromDisk($filePath, 'public')
                                        ->toMediaCollection('bahan_mutasi_faktur');
                                }
                            } catch (\Exception $e) {
                                Log::error('Error saving bukti faktur: ' . $e->getMessage());
                            }
                        }

                        $statusPembayaran = $faktur->status_pembayaran instanceof StatusPembayaranEnum
                            ? $faktur->status_pembayaran
                            : StatusPembayaranEnum::from($faktur->status_pembayaran);

                        if (
                            $statusPembayaran === StatusPembayaranEnum::LUNAS &&
                            $totalSetelahDiskon > 0
                        ) {
                            PencatatanKeuangan::create([
                                'pencatatan_keuangan_type' => BahanMutasiFaktur::class,
                                'pencatatan_keuangan_id' => $faktur->id,
                                'user_id' => Auth::id(),
                                'jumlah_bayar' => $totalSetelahDiskon,
                                'metode_pembayaran' => $data['metode_pembayaran'] ?? null,
                                'keterangan' => 'Pembayaran faktur ' . $faktur->kode,
                                'approved_by' => null,
                                'approved_at' => null,
                            ]);
                        }
                        
                        // Buat detail mutasi dari repeater dan buat batch untuk FIFO
                        $firstRecord = null;
                        if (!empty($data['bahanMutasiDetails'])) {
                            foreach ($data['bahanMutasiDetails'] as $detail) {
                                $jumlahSatuanTerbesar = $parse($detail['jumlah_satuan_terbesar'] ?? '0');
                                $isiPerSatuanTerbesar = $parse($detail['jumlah_satuan_terkecil'] ?? '0');
                                $jumlahMasukTerkecil = $jumlahSatuanTerbesar * max($isiPerSatuanTerbesar, 1);
                                $mutasi = BahanMutasi::create([
                                    'kode' => generateKode('BM'),
                                    'tipe' => TipeEnum::MASUK->value,
                                    'bahan_mutasi_faktur_id' => $faktur->id,
                                    'bahan_id' => $detail['bahan_id'],
                                    'jumlah_satuan_terbesar' => $jumlahSatuanTerbesar,
                                    'jumlah_satuan_terkecil' => $isiPerSatuanTerbesar,
                                    'jumlah_mutasi' => $jumlahMasukTerkecil,
                                    'total_harga_mutasi' => $parse($detail['total_harga_mutasi'] ?? '0'),
                                    'harga_satuan_terbesar' => $parse($detail['harga_satuan_terbesar'] ?? '0'),
                                    'harga_satuan_terkecil' => $parse($detail['harga_satuan_terkecil'] ?? '0'),
                                ]);

                                // Buat batch untuk FIFO tracking
                                if (!empty($detail['bahan_id']) && $jumlahMasukTerkecil > 0) {
                                    BahanStokBatch::create([
                                        'bahan_id' => $detail['bahan_id'],
                                        'bahan_mutasi_id' => $mutasi->id,
                                        'jumlah_masuk' => $jumlahMasukTerkecil,
                                        'jumlah_tersedia' => $jumlahMasukTerkecil,
                                        'harga_satuan_terkecil' => $parse($detail['harga_satuan_terkecil'] ?? '0'),
                                        'harga_satuan_terbesar' => $parse($detail['harga_satuan_terbesar'] ?? '0'),
                                        'tanggal_masuk' => $mutasi->created_at,
                                    ]);
                                }
                                
                                if (!$firstRecord) {
                                    $firstRecord = $mutasi;
                                }
                            }
                        }
                        
                        return $firstRecord;
                    }
                })
        ];
    }
}
