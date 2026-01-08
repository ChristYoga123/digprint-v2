<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\LaporanDPCustomerResource\Pages;
use App\Models\WalletMutasi;
use App\Models\Wallet;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use Carbon\Carbon;

class LaporanDPCustomerResource extends Resource
{
    protected static ?string $model = WalletMutasi::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    
    protected static ?string $navigationLabel = 'Laporan Mutasi DP';
    
    protected static ?string $navigationGroup = 'Laporan';
    
    protected static ?string $modelLabel = 'Mutasi DP';
    
    protected static ?string $pluralModelLabel = 'Laporan Mutasi DP';
    
    protected static ?int $navigationSort = 11;

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
                // No form - read only
            ]);
    }

    public static function table(Table $table): Table
    {
        $walletDP = Wallet::walletDP();
        $walletDPId = $walletDP ? $walletDP->id : 0;
        
        return $table
            ->defaultSort('created_at', 'desc')
            ->query(
                WalletMutasi::query()
                    ->where('wallet_id', $walletDPId)
                    ->with(['transaksi.customer', 'walletTujuan', 'createdByUser'])
            )
            ->columns([
                TextColumn::make('transaksi.kode')
                    ->label('Invoice')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->url(fn (WalletMutasi $record) => $record->transaksi_id 
                        ? TransaksiResource::getUrl('index', ['tableSearch' => $record->transaksi->kode])
                        : null),
                TextColumn::make('transaksi.customer.nama')
                    ->label('Nama Customer')
                    ->searchable()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('created_at')
                    ->label('Tanggal Mutasi')
                    ->dateTime('d/m/Y')
                    ->sortable(),
                TextColumn::make('status_dp')
                    ->label('Status DP')
                    ->getStateUsing(function (WalletMutasi $record) {
                        if ($record->tipe === 'masuk') {
                            return $record->nominal;
                        }
                        return null;
                    })
                    ->money('IDR')
                    ->color('success')
                    ->placeholder('-'),
                TextColumn::make('mutasi_ke_kas')
                    ->label('Mutasi ke Kas Pemasukan')
                    ->getStateUsing(function (WalletMutasi $record) {
                        if ($record->tipe === 'transfer' && $record->wallet_tujuan_id) {
                            $walletKas = Wallet::walletKasPemasukan();
                            if ($walletKas && $record->wallet_tujuan_id === $walletKas->id) {
                                return $record->nominal;
                            }
                        }
                        return null;
                    })
                    ->money('IDR')
                    ->color('warning')
                    ->placeholder('-'),
                TextColumn::make('refund_keluar')
                    ->label('Refund (Keluar)')
                    ->getStateUsing(function (WalletMutasi $record) {
                        if ($record->tipe === 'keluar') {
                            return $record->nominal;
                        }
                        return null;
                    })
                    ->money('IDR')
                    ->color('danger')
                    ->placeholder('-'),
                TextColumn::make('saldo_sesudah')
                    ->label('Saldo (last balance)')
                    ->money('IDR')
                    ->weight('bold')
                    ->color(fn ($state) => $state > 0 ? 'primary' : 'gray'),
                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->limit(30)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                DateRangeFilter::make('created_at')
                    ->label('Tanggal')
                    ->defaultThisMonth(),
                SelectFilter::make('transaksi_id')
                    ->label('Filter Invoice')
                    ->relationship('transaksi', 'kode')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('tipe')
                    ->label('Tipe Mutasi')
                    ->options([
                        'masuk' => 'Masuk (DP)',
                        'transfer' => 'Transfer ke Kas',
                        'keluar' => 'Refund (Keluar)',
                    ]),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading(fn (WalletMutasi $record) => 'Detail Mutasi - ' . $record->kode)
                    ->modalContent(function (WalletMutasi $record) {
                        $html = '<div class="space-y-4">';
                        
                        $html .= '<div class="grid grid-cols-2 gap-4">';
                        $html .= '<div><strong>Kode Mutasi:</strong><br>' . $record->kode . '</div>';
                        $html .= '<div><strong>Tipe:</strong><br>' . ucfirst($record->tipe) . '</div>';
                        $html .= '<div><strong>Invoice:</strong><br>' . ($record->transaksi?->kode ?? '-') . '</div>';
                        $html .= '<div><strong>Customer:</strong><br>' . ($record->transaksi?->customer?->nama ?? '-') . '</div>';
                        $html .= '<div><strong>Nominal:</strong><br>' . formatRupiah($record->nominal) . '</div>';
                        $html .= '<div><strong>Saldo Sebelum:</strong><br>' . formatRupiah($record->saldo_sebelum) . '</div>';
                        $html .= '<div><strong>Saldo Sesudah:</strong><br>' . formatRupiah($record->saldo_sesudah) . '</div>';
                        $html .= '<div><strong>Tanggal:</strong><br>' . Carbon::parse($record->created_at)->format('d M Y H:i') . '</div>';
                        $html .= '</div>';
                        
                        if ($record->tipe === 'keluar') {
                            $html .= '<div class="mt-4 p-4 bg-danger-100 rounded-lg">';
                            $html .= '<strong>Tipe:</strong> Refund (Uang Keluar)';
                            $html .= '</div>';
                        } elseif ($record->tipe === 'transfer' && $record->walletTujuan) {
                            $html .= '<div class="mt-4 p-4 bg-warning-100 rounded-lg">';
                            $html .= '<strong>Transfer ke:</strong> ' . $record->walletTujuan->nama;
                            $html .= '</div>';
                        } elseif ($record->tipe === 'masuk') {
                            $html .= '<div class="mt-4 p-4 bg-success-100 rounded-lg">';
                            $html .= '<strong>Tipe:</strong> Masuk (DP)';
                            $html .= '</div>';
                        }
                        
                        if ($record->keterangan) {
                            $html .= '<div class="mt-4"><strong>Keterangan:</strong><br>' . nl2br(e($record->keterangan)) . '</div>';
                        }
                        
                        $html .= '</div>';
                        
                        return new HtmlString($html);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
            ])
            ->bulkActions([
                // No bulk actions - read only
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageLaporanDPCustomers::route('/'),
        ];
    }
}
