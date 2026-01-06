<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PengajuanDiskonResource\Pages;
use App\Models\Transaksi;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Enums\Transaksi\JenisDiskonEnum;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;

class PengajuanDiskonResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Transaksi::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';

    protected static ?string $navigationLabel = 'Pengajuan Diskon';

    protected static ?string $modelLabel = 'Pengajuan Diskon';

    protected static ?string $pluralModelLabel = 'Pengajuan Diskon';

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()->can('view_pengajuan::diskon') && Auth::user()->can('view_any_pengajuan::diskon');
    }

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'approve',
            'reject'
        ];
    }

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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                Transaksi::query()
                    ->whereNotNull('total_diskon_transaksi')
                    ->where('total_diskon_transaksi', '>', 0)
                    ->whereNull('approved_diskon_by')
                    ->with([
                        'customer',
                        'transaksiProduks.produk',
                    ])
                    ->orderBy('created_at', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('kode')
                    ->label('Kode Transaksi')
                    ->searchable()
                    ->weight('bold')
                    ->copyable(),
                Tables\Columns\TextColumn::make('customer.nama')
                    ->label('Customer')
                    ->searchable()
                    ->description(fn(Transaksi $record) => $record->customer?->telepon ?? '-'),
                Tables\Columns\TextColumn::make('jenis_diskon')
                    ->label('Jenis Diskon')
                    ->badge()
                    ->color(fn(Transaksi $record) => match($record->jenis_diskon) {
                        JenisDiskonEnum::PER_ITEM => 'info',
                        JenisDiskonEnum::PER_INVOICE => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('total_harga_transaksi')
                    ->label('Total Harga')
                    ->money('IDR')
                    ->description('Sebelum diskon'),
                Tables\Columns\TextColumn::make('total_diskon_transaksi')
                    ->label('Total Diskon')
                    ->money('IDR')
                    ->weight('bold')
                    ->color('danger'),
                Tables\Columns\TextColumn::make('total_harga_transaksi_setelah_diskon')
                    ->label('Total Bayar')
                    ->money('IDR')
                    ->weight('bold')
                    ->color('success'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('jenis_diskon')
                    ->label('Jenis Diskon')
                    ->options(JenisDiskonEnum::class),
            ])
            ->actions([
                Action::make('lihat_detail')
                    ->label('Detail')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading(fn(Transaksi $record) => 'Detail Transaksi: ' . $record->kode)
                    ->modalContent(fn(Transaksi $record) => view('filament.modals.detail-diskon-transaksi', ['transaksi' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->visible(fn () => Auth::user()->can('view_pengajuan::diskon')),
                Action::make('approve_diskon')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Pengajuan Diskon')
                    ->modalDescription(function(Transaksi $record) {
                        $diskonFormatted = 'Rp ' . number_format($record->total_diskon_transaksi, 0, ',', '.');
                        $jenisDiskon = $record->jenis_diskon?->getLabel() ?? '-';
                        return "Approve diskon sebesar {$diskonFormatted} ({$jenisDiskon}) untuk transaksi {$record->kode}?";
                    })
                    ->visible(fn () => Auth::user()->can('approve_pengajuan::diskon'))
                    ->action(function(Transaksi $record) {
                        try {
                            $record->update([
                                'approved_diskon_by' => Auth::id(),
                            ]);

                            Notification::make()
                                ->title('Berhasil')
                                ->body('Diskon berhasil di-approve.')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal approve diskon')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('reject_diskon')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Tolak Pengajuan Diskon')
                    ->modalDescription(fn(Transaksi $record) => 'Tolak diskon untuk transaksi ' . $record->kode . '? Diskon akan di-reset ke 0.')
                    ->visible(fn () => Auth::user()->can('reject_pengajuan::diskon'))
                    ->action(function(Transaksi $record) {
                        try {
                            $record->update([
                                'jenis_diskon' => null,
                                'total_diskon_transaksi' => 0,
                                'total_harga_transaksi_setelah_diskon' => $record->total_harga_transaksi,
                            ]);

                            Notification::make()
                                ->title('Berhasil')
                                ->body('Diskon berhasil ditolak dan di-reset.')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal menolak diskon')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Action::make('bulk_approve')
                        ->label('Approve Terpilih')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            try {
                                $count = 0;
                                foreach ($records as $record) {
                                    $record->update([
                                        'approved_diskon_by' => Auth::id(),
                                    ]);
                                    $count++;
                                }

                                Notification::make()
                                    ->title('Berhasil')
                                    ->body("Berhasil approve {$count} diskon.")
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Gagal approve diskon')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                ]),
            ])
            ->emptyStateHeading('Tidak ada pengajuan diskon')
            ->emptyStateDescription('Belum ada transaksi dengan diskon yang menunggu approval.')
            ->emptyStateIcon('heroicon-o-receipt-percent');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePengajuanDiskons::route('/'),
        ];
    }
}
