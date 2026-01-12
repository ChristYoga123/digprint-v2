<?php

namespace App\Filament\Admin\Resources\BahanResource\Pages;

use App\Models\Bahan;
use Filament\Actions;
use App\Models\Satuan;
use App\Models\BahanMutasi;
use App\Models\BahanStokBatch;
use Illuminate\Support\Collection;
use App\Enums\BahanMutasi\TipeEnum;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use App\Filament\Admin\Resources\BahanResource;

class ManageBahans extends ManageRecords
{
    protected static string $resource = BahanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \EightyNine\ExcelImport\ExcelImportAction::make()
                ->label('Import Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->visible(fn () => Auth::user()->can('import_bahan'))
                ->processCollectionUsing(function (string $modelClass, Collection $collection) {
                    $imported = 0;
                    $updated = 0;
                    $newBatches = 0;
                    $errors = [];
                    
                    foreach ($collection as $index => $row) {
                        $rowNumber = $index + 2; // +2 karena index mulai dari 0 dan header di baris 1
                        
                        try {
                            // Parse fields dari row
                            $kode = trim($row['kode'] ?? '');
                            $nama = trim($row['nama'] ?? '');
                            $satuanTerbesarNama = trim($row['satuan_terbesar'] ?? '');
                            $satuanTerkecilNama = trim($row['satuan_terkecil'] ?? '');
                            $stokMinimal = (int) ($row['stok_minimal'] ?? 0);
                            $jumlahMasuk = (int) ($row['jumlah_masuk'] ?? 0);
                            $keterangan = trim($row['keterangan'] ?? '');
                            $hargaSatuanTerkecil = (int) ($row['harga_satuan_terkecil'] ?? 0);
                            $hargaSatuanTerbesar = (int) ($row['harga_satuan_terbesar'] ?? 0);
                            
                            // Skip empty rows
                            if (empty($nama)) {
                                continue;
                            }
                            
                            // Validasi satuan
                            if (empty($satuanTerbesarNama) || empty($satuanTerkecilNama)) {
                                $errors[] = "Baris {$rowNumber}: Satuan terbesar dan terkecil wajib diisi.";
                                continue;
                            }
                            
                            // Cari atau buat Satuan Terbesar
                            $satuanTerbesar = Satuan::firstOrCreate(
                                ['nama' => $satuanTerbesarNama],
                                ['nama' => $satuanTerbesarNama]
                            );
                            
                            // Cari atau buat Satuan Terkecil
                            $satuanTerkecil = Satuan::firstOrCreate(
                                ['nama' => $satuanTerkecilNama],
                                ['nama' => $satuanTerkecilNama]
                            );
                            
                            // Cek apakah bahan sudah ada (berdasarkan nama)
                            $existingBahan = Bahan::where('nama', $nama)->first();
                            
                            if ($existingBahan) {
                                // Bahan sudah ada - cek apakah ada jumlah masuk
                                if ($jumlahMasuk > 0) {
                                    // Cek harga terakhir dari batch yang ada
                                    $lastBatch = BahanStokBatch::where('bahan_id', $existingBahan->id)
                                        ->orderBy('created_at', 'desc')
                                        ->first();
                                    
                                    $lastPrice = $lastBatch ? $lastBatch->harga_satuan_terkecil : 0;
                                    $priceChanged = ($lastPrice != $hargaSatuanTerkecil);
                                    
                                    // Buat mutasi masuk baru (baik harga berubah atau tidak, tetap catat batch baru untuk FIFO)
                                    $mutasi = BahanMutasi::create([
                                        'kode' => generateKode('BM'),
                                        'tipe' => TipeEnum::MASUK->value,
                                        'bahan_id' => $existingBahan->id,
                                        'jumlah_satuan_terbesar' => null,
                                        'jumlah_satuan_terkecil' => null,
                                        'jumlah_mutasi' => $jumlahMasuk,
                                        'total_harga_mutasi' => $jumlahMasuk * $hargaSatuanTerkecil,
                                        'harga_satuan_terbesar' => $hargaSatuanTerbesar,
                                        'harga_satuan_terkecil' => $hargaSatuanTerkecil,
                                        'keterangan' => $priceChanged 
                                            ? "Import Excel - Harga berubah dari " . number_format($lastPrice) . " ke " . number_format($hargaSatuanTerkecil)
                                            : "Import Excel - Penambahan stok",
                                    ]);
                                    
                                    // Buat BahanStokBatch baru untuk FIFO tracking
                                    BahanStokBatch::create([
                                        'bahan_id' => $existingBahan->id,
                                        'bahan_mutasi_id' => $mutasi->id,
                                        'jumlah_masuk' => $jumlahMasuk,
                                        'jumlah_tersedia' => $jumlahMasuk,
                                        'harga_satuan_terkecil' => $hargaSatuanTerkecil,
                                        'harga_satuan_terbesar' => $hargaSatuanTerbesar,
                                        'tanggal_masuk' => now(),
                                    ]);
                                    
                                    if ($priceChanged) {
                                        $newBatches++;
                                    } else {
                                        $updated++;
                                    }
                                } else {
                                    // Tidak ada jumlah masuk, skip bahan yang sudah ada
                                    continue;
                                }
                            } else {
                                // Bahan baru - buat bahan dan mutasi jika ada jumlah masuk
                                
                                // Generate kode jika kosong
                                if (empty($kode)) {
                                    $kode = generateKode('BHN');
                                }
                                
                                // Cek apakah kode sudah dipakai
                                $existingKode = Bahan::where('kode', $kode)->exists();
                                if ($existingKode) {
                                    $kode = generateKode('BHN'); // Generate kode baru
                                }
                                
                                // Buat Bahan baru
                                $bahan = Bahan::create([
                                    'kode' => $kode,
                                    'nama' => $nama,
                                    'satuan_terbesar_id' => $satuanTerbesar->id,
                                    'satuan_terkecil_id' => $satuanTerkecil->id,
                                    'stok_minimal' => $stokMinimal,
                                    'keterangan' => $keterangan ?: null,
                                ]);
                                
                                // Jika ada jumlah masuk, buat mutasi masuk
                                if ($jumlahMasuk > 0) {
                                    // Buat BahanMutasi tipe MASUK
                                    $mutasi = BahanMutasi::create([
                                        'kode' => generateKode('BM'),
                                        'tipe' => TipeEnum::MASUK->value,
                                        'bahan_id' => $bahan->id,
                                        'jumlah_satuan_terbesar' => null,
                                        'jumlah_satuan_terkecil' => null,
                                        'jumlah_mutasi' => $jumlahMasuk,
                                        'total_harga_mutasi' => $jumlahMasuk * $hargaSatuanTerkecil,
                                        'harga_satuan_terbesar' => $hargaSatuanTerbesar,
                                        'harga_satuan_terkecil' => $hargaSatuanTerkecil,
                                        'keterangan' => "Import Excel - Stok awal",
                                    ]);
                                    
                                    // Buat BahanStokBatch untuk FIFO tracking
                                    BahanStokBatch::create([
                                        'bahan_id' => $bahan->id,
                                        'bahan_mutasi_id' => $mutasi->id,
                                        'jumlah_masuk' => $jumlahMasuk,
                                        'jumlah_tersedia' => $jumlahMasuk,
                                        'harga_satuan_terkecil' => $hargaSatuanTerkecil,
                                        'harga_satuan_terbesar' => $hargaSatuanTerbesar,
                                        'tanggal_masuk' => now(),
                                    ]);
                                }
                                
                                $imported++;
                            }
                            
                        } catch (\Exception $e) {
                            $errors[] = "Baris {$rowNumber}: " . $e->getMessage();
                        }
                    }
                    
                    // Tampilkan notifikasi hasil import
                    $messages = [];
                    if ($imported > 0) {
                        $messages[] = "{$imported} bahan baru";
                    }
                    if ($updated > 0) {
                        $messages[] = "{$updated} penambahan stok";
                    }
                    if ($newBatches > 0) {
                        $messages[] = "{$newBatches} batch baru (harga berubah)";
                    }
                    
                    if (!empty($messages)) {
                        Notification::make()
                            ->success()
                            ->title('Import Berhasil')
                            ->body("Berhasil import: " . implode(", ", $messages))
                            ->send();
                    }
                    
                    if (!empty($errors)) {
                        Notification::make()
                            ->warning()
                            ->title('Beberapa baris gagal diimport')
                            ->body(implode("\n", array_slice($errors, 0, 5)) . (count($errors) > 5 ? "\n... dan " . (count($errors) - 5) . " error lainnya" : ''))
                            ->persistent()
                            ->send();
                    }
                    
                    if (empty($messages) && empty($errors)) {
                        Notification::make()
                            ->info()
                            ->title('Tidak ada data yang diimport')
                            ->body('File Excel tidak berisi data yang valid atau semua bahan sudah ada tanpa jumlah masuk.')
                            ->send();
                    }
                    
                    return $collection;
                }),
            Actions\Action::make('download_template')
                ->label('Download Template')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->visible(fn () => Auth::user()->can('import_bahan'))
                ->action(function () {
                    // Generate template Excel sederhana dengan header yang dibutuhkan
                    $headers = [
                        'kode',
                        'nama',
                        'satuan_terbesar',
                        'satuan_terkecil',
                        'stok_minimal',
                        'jumlah_masuk',
                        'harga_satuan_terkecil',
                        'harga_satuan_terbesar',
                        'keterangan',
                    ];
                    
                    // Contoh data untuk membantu user memahami format
                    $exampleRow = [
                        'BHN-001',          // kode (opsional, akan di-generate jika kosong)
                        'Kertas HVS A4',     // nama (wajib)
                        'Rim',               // satuan_terbesar (wajib)
                        'Lembar',            // satuan_terkecil (wajib)
                        '100',               // stok_minimal
                        '500',               // jumlah_masuk
                        '100',               // harga_satuan_terkecil (per lembar)
                        '50000',             // harga_satuan_terbesar (per rim)
                        'Contoh keterangan', // keterangan
                    ];
                    
                    // Buat konten CSV
                    $output = fopen('php://temp', 'r+');
                    fputcsv($output, $headers);
                    fputcsv($output, $exampleRow);
                    rewind($output);
                    $csv = stream_get_contents($output);
                    fclose($output);
                    
                    // Download sebagai file
                    return response()->streamDownload(function () use ($csv) {
                        echo $csv;
                    }, 'template_import_bahan.csv', [
                        'Content-Type' => 'text/csv',
                    ]);
                }),
            Actions\CreateAction::make()
                ->visible(fn() => Auth::user()->can('create_bahan')),
        ];
    }
}
