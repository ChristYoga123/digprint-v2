<?php

namespace App\Filament\Admin\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Support\RawJs;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use App\Models\TransaksiProdukSubjoin;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use App\Filament\Admin\Resources\PengajuanSubjoinResource\Pages;
use Illuminate\Support\Facades\Auth;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;

class PengajuanSubjoinResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = TransaksiProdukSubjoin::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';

    protected static ?string $navigationLabel = 'Pengajuan Subjoin';

    protected static ?string $modelLabel = 'Pengajuan Subjoin';

    protected static ?string $pluralModelLabel = 'Pengajuan Subjoin';

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()->can('view_pengajuan::subjoin') && Auth::user()->can('view_any_pengajuan::subjoin');
    }

    public static function canViewAny(): bool
    {
        return Auth::user()->can('view_any_pengajuan::subjoin');
    }

    public static function canView(Model $record): bool
    {
        return Auth::user()->can('view_pengajuan::subjoin');
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
                TransaksiProdukSubjoin::query()
                    ->where('apakah_subjoin_diapprove', false)
                    ->with([
                        'transaksiProduk.transaksi.customer',
                        'transaksiProduk.produk',
                        'produkProses.produkProsesKategori',
                    ])
                    ->orderBy('created_at', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('transaksiProduk.transaksi.kode')
                    ->label('Kode Transaksi')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn(TransaksiProdukSubjoin $record) => $record->transaksiProduk?->transaksi?->customer?->nama ?? '-'),
                Tables\Columns\TextColumn::make('transaksiProduk.produk.nama')
                    ->label('Produk')
                    ->searchable()
                    ->description(fn(TransaksiProdukSubjoin $record) => 'Qty: ' . ($record->transaksiProduk?->jumlah ?? 0) . ' pcs'),
                Tables\Columns\TextColumn::make('produkProses.nama')
                    ->label('Proses Subjoin')
                    ->weight('bold')
                    ->color('info')
                    ->description(fn(TransaksiProdukSubjoin $record) => $record->produkProses?->produkProsesKategori?->nama ?? '-'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Pengajuan')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                Tables\Columns\IconColumn::make('apakah_subjoin_diapprove')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('approve_subjoin')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Pengajuan Subjoin')
                    ->modalDescription(fn(TransaksiProdukSubjoin $record) => 'Approve subjoin untuk proses: ' . $record->produkProses?->nama)
                    ->visible(fn () => Auth::user()->can('approve_pengajuan::subjoin'))
                    ->form([
                        TextInput::make('nama_vendor')
                            ->label('Nama Vendor')
                            ->placeholder('Masukkan nama vendor...')
                            ->required()
                            ->visible()
                            ->maxLength(255),
                        TextInput::make('harga_vendor')
                            ->label('Harga Vendor')
                            ->placeholder('0')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->visible()
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(','),
                        SpatieMediaLibraryFileUpload::make('faktur')
                            ->label('Upload Faktur/Invoice')
                            ->collection('faktur_subjoin')
                            ->image()
                            ->maxSize(5120) // 5MB
                            ->visible()
                            ->required()
                            ->optimize('webp')
                            ->helperText('Upload faktur/invoice dari vendor (gambar, max 5MB)'),
                    ])
                    ->action(function(TransaksiProdukSubjoin $record, array $data) {
                        try {
                            // Update record subjoin dengan data vendor
                            $record->update([
                                'apakah_subjoin_diapprove' => true,
                                'nama_vendor' => $data['nama_vendor'],
                                'harga_vendor' => $data['harga_vendor'],
                            ]);

                            // Update transaksi_proses untuk set apakah_menggunakan_subjoin = true
                            \App\Models\TransaksiProses::where('transaksi_produk_id', $record->transaksi_produk_id)
                                ->where('produk_proses_id', $record->produk_proses_id)
                                ->update([
                                    'apakah_menggunakan_subjoin' => true
                                ]);

                            Notification::make()
                                ->title('Berhasil')
                                ->body('Pengajuan subjoin berhasil di-approve.')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal approve subjoin')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('reject_subjoin')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Tolak Pengajuan Subjoin')
                    ->modalDescription(fn(TransaksiProdukSubjoin $record) => 'Tolak subjoin untuk proses: ' . $record->produkProses?->nama . '? Data akan dihapus.')
                    ->visible(fn () => Auth::user()->can('reject_pengajuan::subjoin'))
                    ->action(function(TransaksiProdukSubjoin $record) {
                        try {
                            $record->delete();

                            Notification::make()
                                ->title('Berhasil')
                                ->body('Pengajuan subjoin berhasil ditolak.')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Gagal menolak subjoin')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Tolak Terpilih'),
                ]),
            ])
            ->emptyStateHeading('Tidak ada pengajuan subjoin')
            ->emptyStateDescription('Belum ada pengajuan subjoin yang menunggu approval.')
            ->emptyStateIcon('heroicon-o-arrow-path-rounded-square');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePengajuanSubjoins::route('/'),
        ];
    }
}
