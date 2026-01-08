<?php

namespace App\Filament\Admin\Resources;

use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\WalletMutasi;
use App\Models\Wallet;
use App\Models\Transaksi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Admin\Resources\LaporanKasPemasukanResource\Pages;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class LaporanKasPemasukanResource extends Resource
{
    protected static ?string $model = WalletMutasi::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-down-on-square';
    
    protected static ?string $navigationLabel = 'Laporan Kas Pemasukan';
    
    protected static ?string $modelLabel = 'Laporan Kas Pemasukan';
    
    protected static ?string $pluralModelLabel = 'Laporan Kas Pemasukan';
    
    protected static ?string $slug = 'laporan-kas-pemasukan';

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()->can('view_laporan::kas::pemasukan') && Auth::user()->can('view_any_laporan::kas::pemasukan');
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

    public static function table(Table $table): Table
    {
        $walletKas = Wallet::walletKasPemasukan();
        $walletKasId = $walletKas ? $walletKas->id : 0;
        
        return $table
            ->query(
                WalletMutasi::query()
                    ->where('wallet_id', $walletKasId)
                    ->whereIn('tipe', ['masuk', 'transfer', 'keluar']) // Masuk langsung, transfer dari DP, atau refund (keluar)
                    ->with(['transaksi.customer', 'sumber', 'createdByUser', 'relatedMutasi'])
                    ->orderBy('created_at', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('transaksi.kode')
                    ->label('Kode Transaksi')
                    ->searchable()
                    ->placeholder('-')
                    ->url(fn (WalletMutasi $record) => $record->transaksi_id 
                        ? TransaksiResource::getUrl('index', ['tableSearch' => $record->transaksi->kode])
                        : null),
                Tables\Columns\TextColumn::make('transaksi.customer.nama')
                    ->label('Customer')
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('sumber_pembayaran')
                    ->label('Sumber')
                    ->getStateUsing(function (WalletMutasi $record) {
                        // Jika tipe keluar, ini adalah refund
                        if ($record->tipe === 'keluar') {
                            return 'Refund';
                        }
                        // Jika tipe transfer, berarti ini dari DP
                        if ($record->tipe === 'transfer') {
                            return 'Transfer dari DP';
                        }
                        return 'Pembayaran Langsung';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Refund' => 'danger',
                        'Transfer dari DP' => 'warning',
                        'Pembayaran Langsung' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('nominal')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->sortable()
                    ->color(fn (WalletMutasi $record) => $record->tipe === 'keluar' ? 'danger' : 'success')
                    ->weight('bold')
                    ->formatStateUsing(fn (WalletMutasi $record, $state) => $record->tipe === 'keluar' ? '-' . formatRupiah($state) : formatRupiah($state)),
                Tables\Columns\TextColumn::make('saldo_sesudah')
                    ->label('Saldo Kas')
                    ->money('IDR')
                    ->weight('bold')
                    ->color('primary'),
                Tables\Columns\TextColumn::make('createdByUser.name')
                    ->label('Diterima Oleh')
                    ->searchable()
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->placeholder('-')
                    ->wrap()
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                DateRangeFilter::make('created_at')
                    ->label('Tanggal'),
                Tables\Filters\SelectFilter::make('tipe')
                    ->label('Sumber Pemasukan')
                    ->options([
                        'masuk' => 'Pembayaran Langsung (LUNAS)',
                        'transfer' => 'Transfer dari DP',
                        'keluar' => 'Refund',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->modalHeading(fn (WalletMutasi $record) => 'Detail Pemasukan - ' . $record->kode)
                    ->modalContent(function (WalletMutasi $record) {
                        $html = '<div class="space-y-4">';
                        
                        $html .= '<div class="grid grid-cols-2 gap-4">';
                        $html .= '<div><strong>Kode Mutasi:</strong><br>' . $record->kode . '</div>';
                        $html .= '<div><strong>Invoice:</strong><br>' . ($record->transaksi?->kode ?? '-') . '</div>';
                        $html .= '<div><strong>Customer:</strong><br>' . ($record->transaksi?->customer?->nama ?? '-') . '</div>';
                        $html .= '<div><strong>Nominal:</strong><br>' . formatRupiah($record->nominal) . '</div>';
                        $html .= '<div><strong>Saldo Sebelum:</strong><br>' . formatRupiah($record->saldo_sebelum) . '</div>';
                        $html .= '<div><strong>Saldo Sesudah:</strong><br>' . formatRupiah($record->saldo_sesudah) . '</div>';
                        $html .= '<div><strong>Tanggal:</strong><br>' . Carbon::parse($record->created_at)->format('d M Y H:i') . '</div>';
                        $html .= '<div><strong>Diterima Oleh:</strong><br>' . ($record->createdByUser?->name ?? '-') . '</div>';
                        $html .= '</div>';
                        
                        // Cek sumber berdasarkan tipe
                        if ($record->tipe === 'keluar') {
                            $html .= '<div class="mt-4 p-4 bg-danger-100 rounded-lg">';
                            $html .= '<strong>Sumber:</strong> Refund (Uang Keluar)';
                            $html .= '</div>';
                        } elseif ($record->tipe === 'transfer') {
                            $html .= '<div class="mt-4 p-4 bg-warning-100 rounded-lg">';
                            $html .= '<strong>Sumber:</strong> Transfer dari Wallet DP';
                            $html .= '</div>';
                        } else {
                            $html .= '<div class="mt-4 p-4 bg-success-100 rounded-lg">';
                            $html .= '<strong>Sumber:</strong> Pembayaran Langsung (LUNAS)';
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
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageLaporanKasPemasukans::route('/'),
        ];
    }
}
