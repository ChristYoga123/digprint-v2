<?php

namespace App\Filament\Admin\Resources;

use Carbon\Carbon;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\TransaksiProduk;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use App\Enums\Transaksi\StatusTransaksiEnum;
use App\Enums\TransaksiProses\StatusProsesEnum;
use App\Filament\Admin\Resources\PraProduksiResource\Pages;

class PraProduksiResource extends Resource
{
    protected static ?string $model = TransaksiProduk::class;

    protected static ?string $navigationIcon = 'heroicon-o-paint-brush';
    
    protected static ?string $navigationLabel = 'Pra Produksi';
    
    protected static ?string $modelLabel = 'Pra Produksi';
    
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
                    ->whereHas('transaksiProses', function($query) {
                        $query->where('urutan', 1)
                            ->whereHas('produkProses', function($q) {
                                $q->where('produk_proses_kategori_id', 1); // Design
                            })
                            ->where('status_proses', StatusProsesEnum::BELUM);
                    })
                    ->with([
                        'transaksi.customer',
                        'produk',
                        'transaksiProses' => function($query) {
                            $query->where('urutan', 1);
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
                    ->description(fn(TransaksiProduk $record) => $record->transaksi->customer->nama ?? '-'),
                TextColumn::make('produk.nama')
                    ->label('Produk')
                    ->searchable(),
                TextColumn::make('design_name')
                    ->label('Design')
                    ->getStateUsing(function(TransaksiProduk $record) {
                        $designProses = $record->transaksiProses->where('urutan', 1)->first();
                        return $designProses?->produkProses?->nama ?? '-';
                    })
                    ->weight('bold')
                    ->color('info'),
                TextColumn::make('jumlah')
                    ->label('Jumlah')
                    ->suffix(' pcs'),
                TextColumn::make('dimensi')
                    ->label('Dimensi')
                    ->getStateUsing(fn(TransaksiProduk $record) => 
                        $record->panjang && $record->lebar 
                            ? "{$record->panjang} x {$record->lebar} cm" 
                            : '-'
                    ),
                TextColumn::make('transaksi.created_at')
                    ->label('Tanggal Order')
                    ->dateTime('d M Y')
                    ->description(fn(TransaksiProduk $record) => Carbon::parse($record->transaksi->created_at)->format('H:i'))
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('approve_design')
                    ->label('Approve Design')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Design')
                    ->modalDescription(fn(TransaksiProduk $record) => 'Approve design untuk transaksi ' . $record->transaksi->kode . '?')
                    ->form([
                        TextInput::make('design')
                            ->label('Link Design')
                            ->url()
                            ->required()
                            ->columnSpanFull(),
                    ])
                    ->visible(function(TransaksiProduk $record) {
                        // Hidden jika proses adalah subjoin
                        $designProses = $record->transaksiProses->where('urutan', 1)->first();
                        return $designProses && !$designProses->is_subjoin;
                    })
                    ->action(function(TransaksiProduk $record, array $data) {
                        try {
                            // Update status proses design menjadi SELESAI
                            $designProses = $record->transaksiProses->where('urutan', 1)->first();
                            
                            if (!$designProses) {
                                Notification::make()
                                    ->title('Error')
                                    ->body('Proses design tidak ditemukan')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            $designProses->update([
                                'status_proses' => StatusProsesEnum::SELESAI->value,
                            ]);

                            $record->transaksi->update([
                                'design' => $data['design'],
                            ]);

                            // Update status transaksi menjadi PRODUKSI jika masih BELUM
                            if ($record->transaksi->status_transaksi === StatusTransaksiEnum::BELUM) {
                                $record->transaksi->update([
                                    'status_transaksi' => StatusTransaksiEnum::PRODUKSI->value,
                                ]);
                            }

                            Notification::make()
                                ->title('Berhasil')
                                ->body('Design berhasil di-approve. Transaksi siap masuk produksi.')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal approve design')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('selesaikan_subjoin')
                    ->label('Selesaikan Subjoin')
                    ->icon('heroicon-o-check')
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Selesaikan Subjoin Design')
                    ->modalDescription(fn(TransaksiProduk $record) => 'Selesaikan subjoin design untuk transaksi ' . $record->transaksi->kode . '?')
                    ->visible(function(TransaksiProduk $record) {
                        // Hanya tampil jika proses adalah subjoin
                        $designProses = $record->transaksiProses->where('urutan', 1)->first();
                        return $designProses && $designProses->is_subjoin;
                    })
                    ->action(function(TransaksiProduk $record) {
                        try {
                            $designProses = $record->transaksiProses->where('urutan', 1)->first();
                            
                            if (!$designProses) {
                                Notification::make()
                                    ->title('Error')
                                    ->body('Proses design tidak ditemukan')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            $designProses->update([
                                'status_proses' => StatusProsesEnum::SELESAI->value,
                            ]);

                            // Update status transaksi menjadi PRODUKSI jika masih BELUM
                            if ($record->transaksi->status_transaksi === StatusTransaksiEnum::BELUM) {
                                $record->transaksi->update([
                                    'status_transaksi' => StatusTransaksiEnum::PRODUKSI->value,
                                ]);
                            }

                            Notification::make()
                                ->title('Berhasil')
                                ->body('Subjoin design berhasil diselesaikan.')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal menyelesaikan subjoin')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                //
            ])
            ->emptyStateHeading('Tidak ada transaksi yang menunggu approval design')
            ->emptyStateDescription('Semua transaksi dengan design sudah di-approve atau belum ada transaksi baru.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePraProduksis::route('/'),
        ];
    }
}
