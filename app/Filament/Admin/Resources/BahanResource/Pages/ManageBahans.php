<?php

namespace App\Filament\Admin\Resources\BahanResource\Pages;

use App\Filament\Admin\Resources\BahanResource;
use App\Models\Bahan;
use App\Models\BahanMutasi;
use App\Models\BahanStokBatch;
use App\Models\Satuan;
use App\Enums\BahanMutasi\TipeEnum;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Collection;
use Filament\Notifications\Notification;

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
                ->processCollectionUsing(function (string $modelClass, Collection $collection) {
                    $imported = 0;
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
                            
                            // Generate kode jika kosong
                            if (empty($kode)) {
                                $kode = generateKode('BHN');
                            }
                            
                            // Cek apakah bahan sudah ada (berdasarkan nama atau kode)
                            $existingBahan = Bahan::where('nama', $nama)
                                ->orWhere('kode', $kode)
                                ->first();
                            
                            if ($existingBahan) {
                                $errors[] = "Baris {$rowNumber}: Bahan dengan nama '{$nama}' atau kode '{$kode}' sudah ada.";
                                continue;
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
                            
                        } catch (\Exception $e) {
                            $errors[] = "Baris {$rowNumber}: " . $e->getMessage();
                        }
                    }
                    
                    // Tampilkan notifikasi hasil import
                    if ($imported > 0) {
                        Notification::make()
                            ->success()
                            ->title('Import Berhasil')
                            ->body("Berhasil import {$imported} bahan.")
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
                    
                    return $collection;
                }),
            Actions\CreateAction::make(),
        ];
    }
}
