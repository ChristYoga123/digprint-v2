<?php

namespace App\Filament\Admin\Resources;

use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\BahanMutasi;
use Filament\Tables\Actions;
use App\Models\BahanStokBatch;
use App\Models\TransaksiProduk;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use App\Enums\BahanMutasi\TipeEnum;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use App\Models\TransaksiProsesBahanUsage;
use App\Models\BahanMutasiPenggunaanBatch;
use Filament\Forms\Components\CheckboxList;
use App\Enums\Transaksi\StatusTransaksiEnum;
use App\Enums\TransaksiProses\StatusProsesEnum;
use App\Filament\Admin\Resources\FinishingResource\Pages;

class FinishingResource extends Resource
{
    protected static ?string $model = TransaksiProduk::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';
    
    protected static ?string $navigationLabel = 'Finishing';
    
    protected static ?string $modelLabel = 'Finishing';
    
    protected static ?int $navigationSort = 33;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // No form needed for workflow
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                TransaksiProduk::query()
                    // Has addons (kategori = 3)
                    ->whereHas('transaksiProses', function($query) {
                        $query->whereHas('produkProses', function($q) {
                            $q->where('produk_proses_kategori_id', 3); // Addon/Finishing
                        });
                    })
                    // All production processes completed
                    ->whereDoesntHave('transaksiProses', function($query) {
                        $query->whereHas('produkProses', function($q) {
                            $q->where('produk_proses_kategori_id', 2); // Produksi
                        })
                        ->where('status_proses', '!=', StatusProsesEnum::SELESAI->value);
                    })
                    // Has uncompleted addons
                    ->whereHas('transaksiProses', function($query) {
                        $query->whereHas('produkProses', function($q) {
                            $q->where('produk_proses_kategori_id', 3);
                        })
                        ->where('status_proses', StatusProsesEnum::BELUM);
                    })
                    ->with([
                        'transaksi.customer',
                        'produk',
                        'transaksiProses' => function($query) {
                            $query->whereHas('produkProses', function($q) {
                                $q->where('produk_proses_kategori_id', 3);
                            })
                            ->orderBy('urutan');
                        },
                        'transaksiProses.produkProses'
                    ])
                    ->orderBy('created_at', 'asc')
            )
            ->columns([
                TextColumn::make('transaksi.kode')
                    ->label('Kode Transaksi')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn(TransaksiProduk $record) => 
                        $record->transaksi->customer->nama ?? '-'
                    ),
                TextColumn::make('produk.nama')
                    ->label('Produk')
                    ->searchable(),
                TextColumn::make('addons')
                    ->label('Addon/Finishing')
                    ->getStateUsing(function(TransaksiProduk $record) {
                        $addons = $record->transaksiProses
                            ->filter(fn($tp) => $tp->produkProses?->produk_proses_kategori_id == 3)
                            ->map(function($tp) {
                                $status = $tp->status_proses === StatusProsesEnum::SELESAI ? '✓' : '○';
                                return $status . ' ' . $tp->produkProses->nama;
                            })
                            ->join(', ');
                        return $addons ?: '-';
                    })
                    ->wrap(),
                TextColumn::make('jumlah')
                    ->label('Jumlah')
                    ->suffix(' pcs'),
                TextColumn::make('transaksi.created_at')
                    ->label('Tanggal Order')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Actions\Action::make('selesaikan_finishing')
                    ->label('Selesaikan')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Selesaikan Finishing')
                    ->modalDescription('Pilih addon/finishing yang sudah selesai dikerjakan')
                    ->visible(function(TransaksiProduk $record) {
                        // Hidden jika semua proses addon adalah subjoin
                        $nonSubjoinAddons = $record->transaksiProses
                            ->filter(fn($tp) => 
                                $tp->produkProses?->produk_proses_kategori_id == 3 
                                && $tp->status_proses === StatusProsesEnum::BELUM
                                && !$tp->apakah_menggunakan_subjoin
                            );
                        return $nonSubjoinAddons->isNotEmpty();
                    })
                    ->form(function(TransaksiProduk $record) {
                        // Hanya tampilkan addon yang bukan subjoin
                        $addonOptions = $record->transaksiProses
                            ->filter(fn($tp) => 
                                $tp->produkProses?->produk_proses_kategori_id == 3 
                                && $tp->status_proses === StatusProsesEnum::BELUM
                                && !$tp->apakah_menggunakan_subjoin
                            )
                            ->mapWithKeys(fn($tp) => [$tp->id => $tp->produkProses->nama])
                            ->toArray();

                        return [
                            CheckboxList::make('completed_addon_ids')
                                ->label('Addon/Finishing yang Sudah Selesai')
                                ->options($addonOptions)
                                ->required()
                                ->minItems(1),
                        ];
                    })
                    ->action(function(TransaksiProduk $record, array $data) {
                        try {
                            DB::beginTransaction();

                            $completedAddonIds = $data['completed_addon_ids'] ?? [];

                            foreach ($completedAddonIds as $transaksiProsesId) {
                                $transaksiProses = $record->transaksiProses->find($transaksiProsesId);
                                
                                if (!$transaksiProses) continue;

                                // Jika addon mengurangi bahan, lakukan FIFO reduction
                                if ($transaksiProses->produkProses->apakah_mengurangi_bahan) {
                                    $materialNeeds = $transaksiProses->getMaterialNeeds();
                                    $jumlahPesanan = $record->jumlah;

                                    foreach ($materialNeeds as $need) {
                                        $bahanId = $need['bahan_id'];
                                        $jumlahPerUnit = $need['jumlah_per_unit'];
                                        $dipengaruhiDimensi = $need['apakah_dipengaruhi_oleh_dimensi'] ?? false;
                                        
                                        // Jika dipengaruhi dimensi, hitung: (panjang x lebar) x jumlah
                                        if ($dipengaruhiDimensi) {
                                            $panjang = $record->panjang ?? 0;
                                            $lebar = $record->lebar ?? 0;
                                            $luasPerUnit = $panjang * $lebar; // cm²
                                            $totalJumlahBahan = $luasPerUnit * $jumlahPesanan;
                                        } else {
                                            // Jika tidak dipengaruhi dimensi, hitung normal
                                            $totalJumlahBahan = $jumlahPesanan * $jumlahPerUnit;
                                        }

                                        // Cek stok tersedia
                                        $availableStok = BahanStokBatch::where('bahan_id', $bahanId)
                                            ->where('jumlah_tersedia', '>', 0)
                                            ->sum('jumlah_tersedia');

                                        if ($availableStok < $totalJumlahBahan) {
                                            throw new \Exception("Stok bahan untuk addon '{$transaksiProses->produkProses->nama}' tidak mencukupi. Tersedia: {$availableStok}, dibutuhkan: {$totalJumlahBahan}");
                                        }

                                        // Buat BahanMutasi KELUAR
                                        $mutasi = BahanMutasi::create([
                                            'kode' => generateKode('BM'),
                                            'tipe' => TipeEnum::KELUAR->value,
                                            'bahan_id' => $bahanId,
                                            'jumlah_mutasi' => $totalJumlahBahan,
                                        ]);

                                        // FIFO: ambil dari batch yang paling lama
                                        $sisaKeluar = $totalJumlahBahan;
                                        $batches = BahanStokBatch::getAvailableBatches($bahanId, $totalJumlahBahan);
                                        $totalHPP = 0;

                                        foreach ($batches as $batch) {
                                            if ($sisaKeluar <= 0) break;

                                            $jumlahDigunakan = min($sisaKeluar, $batch->jumlah_tersedia);
                                            
                                            // Buat record usage untuk bahan mutasi
                                            BahanMutasiPenggunaanBatch::create([
                                                'bahan_mutasi_id' => $mutasi->id,
                                                'bahan_stok_batch_id' => $batch->id,
                                                'jumlah_digunakan' => $jumlahDigunakan,
                                            ]);

                                            // Calculate HPP contribution from this batch
                                            $totalHPP += ($batch->hpp_per_satuan * $jumlahDigunakan);

                                            // Kurangi jumlah_tersedia dari batch
                                            $batch->reduceStock($jumlahDigunakan);

                                            $sisaKeluar -= $jumlahDigunakan;
                                        }

                                        // Track material usage dengan HPP
                                        TransaksiProsesBahanUsage::create([
                                            'transaksi_proses_id' => $transaksiProses->id,
                                            'bahan_id' => $bahanId,
                                            'jumlah_digunakan' => $totalJumlahBahan,
                                            'hpp' => $totalHPP,
                                        ]);
                                    }
                                }

                                // Update status proses addon
                                $transaksiProses->update([
                                    'status_proses' => StatusProsesEnum::SELESAI->value,
                                ]);
                            }

                            // Cek apakah semua proses (termasuk addon) sudah selesai
                            $allProcessesComplete = $record->transaksiProses
                                ->every(fn($tp) => $tp->status_proses === StatusProsesEnum::SELESAI);

                            if ($allProcessesComplete) {
                                // Update status transaksi menjadi SELESAI
                                $record->transaksi->update([
                                    'status_transaksi' => StatusTransaksiEnum::SELESAI->value,
                                ]);
                            }

                            DB::commit();

                            Notification::make()
                                ->title('Berhasil')
                                ->body('Finishing/addon berhasil diselesaikan' . ($allProcessesComplete ? '. Transaksi sudah selesai!' : ''))
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            DB::rollBack();
                            
                            Notification::make()
                                ->title('Gagal menyelesaikan finishing')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Actions\Action::make('selesaikan_subjoin')
                    ->label('Selesaikan Subjoin')
                    ->icon('heroicon-o-check')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Selesaikan Subjoin Finishing')
                    ->modalDescription('Pilih subjoin finishing yang sudah selesai')
                    ->visible(function(TransaksiProduk $record) {
                        // Hanya tampil jika ada proses subjoin yang belum selesai
                        $subjoinAddons = $record->transaksiProses
                            ->filter(fn($tp) => 
                                $tp->produkProses?->produk_proses_kategori_id == 3 
                                && $tp->status_proses === StatusProsesEnum::BELUM
                                && $tp->apakah_menggunakan_subjoin
                            );
                        return $subjoinAddons->isNotEmpty();
                    })
                    ->form(function(TransaksiProduk $record) {
                        $subjoinOptions = $record->transaksiProses
                            ->filter(fn($tp) => 
                                $tp->produkProses?->produk_proses_kategori_id == 3 
                                && $tp->status_proses === StatusProsesEnum::BELUM
                                && $tp->apakah_menggunakan_subjoin
                            )
                            ->mapWithKeys(fn($tp) => [$tp->id => $tp->produkProses->nama])
                            ->toArray();

                        return [
                            CheckboxList::make('completed_subjoin_ids')
                                ->label('Subjoin Finishing yang Sudah Selesai')
                                ->options($subjoinOptions)
                                ->required()
                                ->minItems(1),
                        ];
                    })
                    ->action(function(TransaksiProduk $record, array $data) {
                        try {
                            DB::beginTransaction();

                            $completedSubjoinIds = $data['completed_subjoin_ids'] ?? [];

                            foreach ($completedSubjoinIds as $transaksiProsesId) {
                                $transaksiProses = $record->transaksiProses->find($transaksiProsesId);
                                
                                if (!$transaksiProses) continue;

                                // Update status proses subjoin (tanpa pengurangan bahan)
                                $transaksiProses->update([
                                    'status_proses' => StatusProsesEnum::SELESAI->value,
                                ]);
                            }

                            // Refresh data
                            $record->load('transaksiProses');

                            // Cek apakah semua proses (termasuk addon) sudah selesai
                            $allProcessesComplete = $record->transaksiProses
                                ->every(fn($tp) => $tp->status_proses === StatusProsesEnum::SELESAI);

                            if ($allProcessesComplete) {
                                // Update status transaksi menjadi SELESAI
                                $record->transaksi->update([
                                    'status_transaksi' => StatusTransaksiEnum::SELESAI->value,
                                ]);
                            }

                            DB::commit();

                            Notification::make()
                                ->title('Berhasil')
                                ->body('Subjoin finishing berhasil diselesaikan' . ($allProcessesComplete ? '. Transaksi sudah selesai!' : ''))
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            DB::rollBack();
                            
                            Notification::make()
                                ->title('Gagal menyelesaikan subjoin finishing')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                //
            ])
            ->emptyStateHeading('Tidak ada transaksi di tahap finishing')
            ->emptyStateDescription('Semua addon/finishing sudah selesai atau belum ada transaksi yang sampai tahap finishing.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageFinishings::route('/'),
        ];
    }
}
