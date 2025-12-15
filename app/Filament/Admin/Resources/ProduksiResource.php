<?php

namespace App\Filament\Admin\Resources;

use App\Models\Kloter;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\BahanMutasi;
use Filament\Tables\Actions;
use App\Models\BahanStokBatch;
use App\Models\TransaksiProses;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use App\Enums\BahanMutasi\TipeEnum;
use Illuminate\Support\Facades\Auth;
use App\Models\TransaksiProsesSample;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use App\Models\TransaksiProsesBahanUsage;
use Filament\Tables\Filters\SelectFilter;
use App\Models\BahanMutasiPenggunaanBatch;
use App\Enums\Transaksi\StatusTransaksiEnum;
use Filament\Forms\Components\ToggleButtons;
use App\Enums\TransaksiProses\StatusProsesEnum;
use App\Enums\Kloter\StatusEnum as KloterStatusEnum;
use App\Filament\Admin\Resources\ProduksiResource\Pages;

class ProduksiResource extends Resource
{
    protected static ?string $model = TransaksiProses::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog';
    
    protected static ?string $navigationLabel = 'Produksi';
    
    protected static ?string $modelLabel = 'Produksi';
    
    protected static ?int $navigationSort = 32;

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
        // Get user's assigned machines
        $userMesinIds = Auth::user()->mesins->pluck('id')->toArray();

        return $table
            ->query(
                TransaksiProses::query()
                    ->whereHas('produkProses', function($query) use ($userMesinIds) {
                        $query->where('produk_proses_kategori_id', 2) // Produksi only
                            ->whereIn('mesin_id', $userMesinIds);
                    })
                    ->whereIn('status_proses', [
                        StatusProsesEnum::BELUM,
                        StatusProsesEnum::DALAM_PROSES
                    ])
                    // All design processes completed (kategori 1, urutan 1)
                    ->whereHas('transaksiProduk', function($query) {
                        $query->whereDoesntHave('transaksiProses', function($q) {
                            $q->where('urutan', 1)
                                ->whereHas('produkProses', function($prod) {
                                    $prod->where('produk_proses_kategori_id', 1); // Design
                                })
                                ->where('status_proses', '!=', StatusProsesEnum::SELESAI->value);
                        });
                    })
                    ->with([
                        'transaksiProduk.transaksi.customer',
                        'transaksiProduk.produk',
                        'produkProses.mesin',
                        'kloter'
                    ])
                    ->orderBy('created_at', 'asc')
            )
            ->columns([
                TextColumn::make('transaksiProduk.transaksi.kode')
                    ->label('Kode Transaksi')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn(TransaksiProses $record) => 
                        $record->transaksiProduk->transaksi->customer->nama ?? '-'
                    ),
                TextColumn::make('transaksiProduk.produk.nama')
                    ->label('Produk')
                    ->searchable(),
                TextColumn::make('produkProses.nama')
                    ->label('Proses')
                    ->weight('bold')
                    ->color('info'),
                TextColumn::make('produkProses.mesin.nama')
                    ->label('Mesin')
                    ->badge(),
                TextColumn::make('urutan')
                    ->label('Urutan')
                    ->badge()
                    ->color('warning'),
                TextColumn::make('status_proses')
                    ->label('Status')
                    ->badge(),
                TextColumn::make('kloter.kode')
                    ->label('Kloter')
                    ->badge()
                    ->color('success')
                    ->default('-'),
            ])
            ->filters([
                SelectFilter::make('mesin')
                    ->relationship('produkProses.mesin', 'nama')
                    ->label('Mesin'),
                SelectFilter::make('status_proses')
                    ->label('Status')
                    ->options(StatusProsesEnum::class),
                SelectFilter::make('kloter')
                    ->label('Kloter')
                    ->options(function() {
                        return Kloter::where('status', KloterStatusEnum::AKTIF)
                            ->pluck('kode', 'id');
                    }),
            ])
            ->actions([
                Actions\Action::make('lihat_design')
                    ->label('Lihat Design')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn(TransaksiProses $record) => $record->transaksiProduk->transaksi->design)
                    ->openUrlInNewTab(),
                Actions\Action::make('tambah_ke_kloter')
                    ->label('Kloter')
                    ->icon('heroicon-o-plus')
                    ->color('warning')
                    ->visible(fn(TransaksiProses $record) => $record->kloter_id === null)
                    ->form([
                        Select::make('kloter_id')
                            ->label('Pilih Kloter')
                            ->options(function(TransaksiProses $record) {
                                $mesinId = $record->produkProses->mesin_id;
                                return Kloter::where('mesin_id', $mesinId)
                                    ->where('status', KloterStatusEnum::AKTIF)
                                    ->pluck('kode', 'id');
                            })
                            ->required()
                            ->searchable(),
                    ])
                    ->action(function(TransaksiProses $record, array $data) {
                        $record->update(['kloter_id' => $data['kloter_id']]);
                        
                        Notification::make()
                            ->title('Berhasil')
                            ->body('Transaksi proses berhasil ditambahkan ke kloter')
                            ->success()
                            ->send();
                    }),
                Actions\Action::make('approve_proses')
                    ->label('Selesaikan')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Selesaikan Proses Produksi')
                    ->modalDescription(fn(TransaksiProses $record) => 'Selesaikan proses: ' . $record->produkProses->nama)
                    ->form(function(TransaksiProses $record) {
                        $form = [];
                        
                        // Semua proses produksi (kategori 2) wajib ditanyai pakai sample atau tidak
                        if ($record->produkProses 
                            && $record->produkProses->produk_proses_kategori_id == 2) {
                            $form[] = ToggleButtons::make('pakai_sample')
                                ->label('Pakai Sample?')
                                ->options([
                                    true => 'Ya',
                                    false => 'Tidak',
                                ])
                                ->colors([
                                    true => 'success',
                                    false => 'danger',
                                ])
                                ->default(false)
                                ->grouped()
                                ->boolean()
                                ->live(onBlur: false)
                                ->required()
                                ->afterStateUpdated(function (Set $set, $state) {
                                    // Reset jumlah sample jika tidak pakai sample
                                    if (!$state) {
                                        $set('jumlah_sample', 0);
                                    }
                                });
                            
                            $form[] = TextInput::make('jumlah_sample')
                                ->label('Jumlah Sample')
                                ->numeric()
                                ->minValue(1)
                                ->default(0)
                                ->required(fn(Get $get) => (bool) $get('pakai_sample'))
                                ->visible(fn(Get $get) => (bool) $get('pakai_sample'))
                                ->helperText('Masukkan jumlah sample yang akan dibuat');
                        }
                        
                        return $form;
                    })
                    ->action(function(TransaksiProses $record, array $data) {
                        try {
                            DB::beginTransaction();

                            $jumlahPesanan = $record->transaksiProduk->jumlah;
                            $jumlahSample = $data['jumlah_sample'] ?? 0;
                            $totalBahanDibutuhkan = $jumlahPesanan + $jumlahSample;

                            // Jika proses mengurangi bahan, lakukan FIFO reduction
                            if ($record->produkProses->apakah_mengurangi_bahan) {
                                $materialNeeds = $record->getMaterialNeeds();
                                $transaksiProduk = $record->transaksiProduk;
                                
                                foreach ($materialNeeds as $need) {
                                    $bahanId = $need['bahan_id'];
                                    $jumlahPerUnit = $need['jumlah_per_unit'];
                                    $dipengaruhiDimensi = $need['apakah_dipengaruhi_oleh_dimensi'] ?? false;
                                    
                                    // Jika dipengaruhi dimensi, hitung: (panjang x lebar) x jumlah
                                    if ($dipengaruhiDimensi) {
                                        $panjang = $transaksiProduk->panjang ?? 0;
                                        $lebar = $transaksiProduk->lebar ?? 0;
                                        $luasPerUnit = $panjang * $lebar; // cm²
                                        $totalJumlahBahan = $luasPerUnit * $totalBahanDibutuhkan;
                                    } else {
                                        // Jika tidak dipengaruhi dimensi, hitung normal
                                        $totalJumlahBahan = $totalBahanDibutuhkan * $jumlahPerUnit;
                                    }

                                    // Cek stok tersedia
                                    $availableStok = BahanStokBatch::where('bahan_id', $bahanId)
                                        ->where('jumlah_tersedia', '>', 0)
                                        ->sum('jumlah_tersedia');

                                    if ($availableStok < $totalJumlahBahan) {
                                        throw new \Exception("Stok bahan tidak mencukupi. Tersedia: {$availableStok}, dibutuhkan: {$totalJumlahBahan}");
                                    }

                                    // Buat BahanMutasi KELUAR
                                    $mutasi = BahanMutasi::create([
                                        'kode' => generateKode('BM'),
                                        'tipe' => TipeEnum::KELUAR->value,
                                        'bahan_id' => $bahanId,
                                        'jumlah_mutasi' => $totalJumlahBahan,
                                    ]);
                                    
                                    // Refresh untuk memastikan data tersimpan
                                    $mutasi->refresh();

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
                                        'transaksi_proses_id' => $record->id,
                                        'bahan_id' => $bahanId,
                                        'jumlah_digunakan' => $totalJumlahBahan,
                                        'hpp' => $totalHPP,
                                    ]);
                                }
                            }

                            // Jika pakai sample, buat record sample
                            if (!empty($data['pakai_sample']) && $jumlahSample > 0) {
                                TransaksiProsesSample::create([
                                    'transaksi_proses_id' => $record->id,
                                    'operator_id' => Auth::id(),
                                    'jumlah_sample' => $jumlahSample,
                                    'status' => \App\Enums\TransaksiProsesSample\StatusSampleApprovalEnum::PENDING->value,
                                ]);
                            }

                            // Update status proses
                            $record->update([
                                'status_proses' => StatusProsesEnum::SELESAI->value,
                            ]);

                            // Refresh untuk mendapatkan data terbaru
                            $record->refresh();
                            $transaksiProduk = $record->transaksiProduk;
                            $transaksiProduk->load('transaksiProses');

                            // Cek apakah semua proses transaksi sudah selesai
                            $allProcessesComplete = $transaksiProduk->transaksiProses
                                ->every(fn($tp) => $tp->status_proses === StatusProsesEnum::SELESAI);

                            if ($allProcessesComplete) {
                                // Update status transaksi menjadi SELESAI
                                $transaksiProduk->transaksi->update([
                                    'status_transaksi' => StatusTransaksiEnum::SELESAI->value,
                                ]);
                            }

                            // Jika ini proses dalam kloter, cek apakah semua proses dalam kloter sudah selesai
                            if ($record->kloter_id) {
                                $kloter = $record->kloter;
                                $allComplete = $kloter->transaksiProses()
                                    ->where('status_proses', '!=', StatusProsesEnum::SELESAI->value)
                                    ->count() === 0;

                                if ($allComplete) {
                                    $kloter->update([
                                        'status' => KloterStatusEnum::SELESAI->value,
                                        'completed_by' => Auth::id(),
                                        'completed_at' => now(),
                                    ]);
                                }
                            }

                            DB::commit();

                            Notification::make()
                                ->title('Berhasil')
                                ->body('Proses produksi berhasil diselesaikan')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            DB::rollBack();
                            
                            Notification::make()
                                ->title('Gagal menyelesaikan proses')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                //
            ])
            ->emptyStateHeading('Tidak ada proses produksi')
            ->emptyStateDescription('Belum ada transaksi yang masuk produksi atau semua proses sudah selesai.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageProduksis::route('/'),
        ];
    }
}
