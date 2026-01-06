<?php

namespace App\Filament\Admin\Resources;

use Carbon\Carbon;
use App\Models\Mesin;
use App\Models\Kloter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use App\Enums\TransaksiProses\StatusProsesEnum;
use App\Enums\Kloter\StatusEnum as KloterStatusEnum;
use App\Filament\Admin\Resources\KloterResource\Pages;

class KloterResource extends Resource
{
    protected static ?string $model = Kloter::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';
    
    protected static ?string $navigationLabel = 'Kloter';

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()->can('view_kloter') && Auth::user()->can('view_any_kloter');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('kode')
                    ->label('Kode Kloter')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->default(fn($record) => $record ? $record->kode : generateKode('KLT')),
                Select::make('mesin_id')
                    ->label('Mesin')
                    ->options(
                        // get mesin by user has mesins
                        Mesin::query()
                            ->whereHas('karyawans', function ($query) {
                                $query->where('karyawan_id', Auth::id());
                            })
                            ->get()
                            ->mapWithKeys(function ($mesin) {
                                return [$mesin->id => '[' . $mesin->kode . '] ' . $mesin->nama];
                            })
                    )
                    ->required()
                    ->searchable(),
                DatePicker::make('tanggal')
                    ->label('Tanggal')
                    ->default(now())
                    ->required(),
                Select::make('status')
                    ->label('Status')
                    ->options(KloterStatusEnum::class)
                    ->default(KloterStatusEnum::AKTIF)
                    ->required(),
                Textarea::make('keterangan')
                    ->label('Keterangan')
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode')
                    ->label('Kode Kloter')
                    ->searchable()
                    ->weight('bold')
                    ->sortable(),
                TextColumn::make('mesin.nama')
                    ->label('Mesin')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tanggal')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('transaksiProses')
                    ->badge()
                    ->color('info')
                    ->suffix(' Proses')
                    ->getStateUsing(function (Kloter $record) {
                        return $record->transaksiProses->count();
                    }),
                TextColumn::make('createdBy.name')
                    ->label('Dibuat Oleh')
                    ->default('-'),
                TextColumn::make('completedBy.name')
                    ->label('Diselesaikan Oleh')
                    ->default('-'),
                TextColumn::make('completed_at')
                    ->label('Selesai Pada')
                    ->getStateUsing(function (Kloter $record) {
                        return $record->completed_at ? Carbon::parse($record->completed_at)->format('d M Y H:i:s') : '-';
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('mesin')
                    ->relationship('mesin', 'nama')
                    ->label('Mesin'),
                SelectFilter::make('status')
                    ->options(KloterStatusEnum::class)
                    ->label('Status'),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Action::make('selesaikan')
                    ->label('Selesaikan')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (Kloter $record) => $record->status === KloterStatusEnum::AKTIF && Auth::user()->can('update_kloter'))
                    ->requiresConfirmation()
                    ->modalHeading('Selesaikan Kloter')
                    ->modalDescription(fn (Kloter $record) => 'Tandai kloter ' . $record->kode . ' sebagai selesai?')
                    ->form([
                        Forms\Components\Toggle::make('ada_helper')
                            ->label('Ada teman yang membantu?')
                            ->live()
                            ->default(false),
                        Forms\Components\Select::make('helper_ids')
                            ->label('Pilih Karyawan yang Membantu')
                            ->options(\App\Models\User::where('is_active', true)->pluck('name', 'id'))
                            ->multiple()
                            ->searchable()
                            ->visible(fn (Forms\Get $get) => $get('ada_helper') === true)
                            ->helperText('Pilih karyawan yang ikut membantu mengerjakan kloter ini'),
                    ])
                    ->action(function (Kloter $record, array $data) {
                        try {
                            DB::beginTransaction();
                            
                            // Simpan ID produk yang terlibat sebelum update
                            $transaksiProdukIds = $record->transaksiProses()->pluck('transaksi_produk_id')->unique();

                            // Update Status Kloter
                            $record->update([
                                'status' => KloterStatusEnum::SELESAI->value,
                                'completed_by' => Auth::id(),
                                'completed_at' => now(),
                            ]);

                            // Update Status Proses dan proses pengurangan bahan untuk setiap proses
                            foreach ($record->transaksiProses as $transaksiProses) {
                                $jumlahPesanan = $transaksiProses->transaksiProduk->jumlah;
                                $jumlahSample = $transaksiProses->jumlah_sample ?? 0;
                                $totalBahanDibutuhkan = $jumlahPesanan + $jumlahSample;

                                // Jika proses mengurangi bahan, lakukan FIFO reduction
                                if ($transaksiProses->produkProses->apakah_mengurangi_bahan) {
                                    $materialNeeds = $transaksiProses->getMaterialNeeds();
                                    $transaksiProduk = $transaksiProses->transaksiProduk;
                                    
                                    foreach ($materialNeeds as $need) {
                                        $bahanId = $need['bahan_id'];
                                        $jumlahPerUnit = $need['jumlah_per_unit'];
                                        $dipengaruhiDimensi = $need['apakah_dipengaruhi_oleh_dimensi'] ?? false;
                                        
                                        // Jika dipengaruhi dimensi, hitung: (panjang x lebar) x jumlah
                                        if ($dipengaruhiDimensi) {
                                            $panjang = (float) ($transaksiProduk->panjang ?? 0);
                                            $lebar = (float) ($transaksiProduk->lebar ?? 0);
                                            
                                            // Tambahkan 0.05m (5cm) untuk kelebihan/toleransi
                                            $panjangDenganToleransi = $panjang + 0.05;
                                            $lebarDenganToleransi = $lebar + 0.05;
                                            
                                            $luasPerUnit = $panjangDenganToleransi * $lebarDenganToleransi; // m²
                                            $totalJumlahBahan = round($luasPerUnit * $totalBahanDibutuhkan, 2);
                                        } else {
                                            // Jika tidak dipengaruhi dimensi, hitung normal
                                            $totalJumlahBahan = round($totalBahanDibutuhkan * $jumlahPerUnit, 2);
                                        }

                                        // Cek stok tersedia
                                        $availableStok = \App\Models\BahanStokBatch::where('bahan_id', $bahanId)
                                            ->where('jumlah_tersedia', '>', 0)
                                            ->sum('jumlah_tersedia');

                                        if ($availableStok < $totalJumlahBahan) {
                                            throw new \Exception("Stok bahan tidak mencukupi. Tersedia: {$availableStok}, dibutuhkan: {$totalJumlahBahan}");
                                        }

                                        // Buat BahanMutasi KELUAR
                                        $mutasi = \App\Models\BahanMutasi::create([
                                            'kode' => generateKode('BM'),
                                            'tipe' => \App\Enums\BahanMutasi\TipeEnum::KELUAR->value,
                                            'bahan_id' => $bahanId,
                                            'jumlah_mutasi' => $totalJumlahBahan,
                                        ]);
                                        
                                        // Refresh untuk memastikan data tersimpan
                                        $mutasi->refresh();

                                        // FIFO: ambil dari batch yang paling lama
                                        $sisaKeluar = $totalJumlahBahan;
                                        $batches = \App\Models\BahanStokBatch::getAvailableBatches($bahanId, $totalJumlahBahan);
                                        $totalHPP = 0;

                                        foreach ($batches as $batch) {
                                            if ($sisaKeluar <= 0) break;

                                            $jumlahDigunakan = min($sisaKeluar, $batch->jumlah_tersedia);
                                            
                                            // Buat record usage untuk bahan mutasi
                                            \App\Models\BahanMutasiPenggunaanBatch::create([
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
                                        \App\Models\TransaksiProsesBahanUsage::create([
                                            'transaksi_proses_id' => $transaksiProses->id,
                                            'bahan_id' => $bahanId,
                                            'jumlah_digunakan' => $totalJumlahBahan,
                                            'hpp' => $totalHPP,
                                        ]);
                                    }
                                }

                                // Update status proses dengan completed_by
                                $transaksiProses->update([
                                    'status_proses' => StatusProsesEnum::SELESAI->value,
                                    'completed_by' => Auth::id(),
                                    'completed_at' => now(),
                                ]);

                                // Catat karyawan yang mengerjakan proses ini
                                // 1. Karyawan utama (user yang login)
                                \App\Models\KaryawanPekerjaan::create([
                                    'karyawan_id' => Auth::id(),
                                    'tipe' => \App\Enums\KaryawanPekerjaan\TipeEnum::NORMAL,
                                    'karyawan_pekerjaan_type' => \App\Models\TransaksiProses::class,
                                    'karyawan_pekerjaan_id' => $transaksiProses->id,
                                ]);

                                // 2. Helper (jika ada)
                                if (!empty($data['helper_ids'])) {
                                    foreach ($data['helper_ids'] as $helperId) {
                                        \App\Models\KaryawanPekerjaan::create([
                                            'karyawan_id' => $helperId,
                                            'tipe' => \App\Enums\KaryawanPekerjaan\TipeEnum::NORMAL,
                                            'karyawan_pekerjaan_type' => \App\Models\TransaksiProses::class,
                                            'karyawan_pekerjaan_id' => $transaksiProses->id,
                                        ]);
                                    }
                                }
                            }

                            // Refresh status untuk setiap produk yang terlibat
                            $produkList = \App\Models\TransaksiProduk::whereIn('id', $transaksiProdukIds)->get();
                            foreach ($produkList as $produk) {
                                $produk->refreshStatus();
                                $produk->transaksi->updateStatusFromProduks();
                            }

                            DB::commit();

                            Notification::make()
                                ->title('Berhasil')
                                ->body('Kloter berhasil diselesaikan')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            DB::rollBack();
                            
                            Notification::make()
                                ->title('Gagal menyelesaikan kloter')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                EditAction::make()
                    ->visible(fn (Kloter $record) => $record->status === KloterStatusEnum::AKTIF && Auth::user()->can('update_kloter')),
                DeleteAction::make()
                    ->visible(fn (Kloter $record) => $record->status === KloterStatusEnum::AKTIF && Auth::user()->can('delete_kloter')),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()->can('delete_any_kloter')),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageKloters::route('/'),
        ];
    }
}
