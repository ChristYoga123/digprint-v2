<?php

namespace App\Filament\Admin\Resources;

use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\BahanMutasiFaktur;
use Illuminate\Support\HtmlString;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\BahanMutasiFaktur\StatusPembayaranEnum;
use App\Filament\Admin\Resources\LaporanPembelianHarianResource\Pages;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use Illuminate\Support\Facades\Auth;

class LaporanPembelianHarianResource extends Resource
{
    protected static ?string $model = BahanMutasiFaktur::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    
    protected static ?string $navigationLabel = 'Laporan Pembelian Harian';
    
    protected static ?string $modelLabel = 'Laporan Pembelian Harian';
    
    protected static ?string $pluralModelLabel = 'Laporan Pembelian Harian';
    
    protected static ?string $slug = 'laporan-pembelian-harian';

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()->can('view_laporan::pembelian::harian') && Auth::user()->can('view_any_laporan::pembelian::harian');
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
        return $table
            ->query(
                BahanMutasiFaktur::query()
                    ->with(['supplier', 'po'])
                    ->orderBy('created_at', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('kode')
                    ->label('Kode Faktur')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('jenis')
                    ->label('Jenis')
                    ->getStateUsing(fn (BahanMutasiFaktur $record) => $record->po_id ? 'PO' : 'Non-PO')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'PO' ? 'success' : 'warning'),
                Tables\Columns\TextColumn::make('po.kode')
                    ->label('Kode PO')
                    ->placeholder('-')
                    ->searchable(),
                Tables\Columns\TextColumn::make('supplier.nama_perusahaan')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_harga')
                    ->label('Total Harga')
                    ->getStateUsing(function (BahanMutasiFaktur $record) {
                        $totalHarga = $record->total_harga;
                        $totalDiskon = $record->total_diskon ?? 0;
                        $hargaSetelahDiskon = $record->total_harga_setelah_diskon ?? $totalHarga;
                        
                        if (empty($totalDiskon) || $totalDiskon == 0) {
                            return formatRupiah($totalHarga);
                        }
                        
                        return new HtmlString(
                            '<div>' .
                                '<span style="text-decoration: line-through; color: #ff0000; font-size: 0.875rem;">' . formatRupiah($totalHarga) . '</span><br>' .
                                '<span style="color: #16a34a; font-weight:bold;">' . formatRupiah($hargaSetelahDiskon) . '</span>' .
                            '</div>'
                        );
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('status_pembayaran')
                    ->label('Status')
                    ->badge(),
            ])
            ->filters([
                DateRangeFilter::make('created_at')
                    ->label('Tanggal'),
                Tables\Filters\SelectFilter::make('jenis_pembelian')
                    ->label('Jenis Pembelian')
                    ->options([
                        'po' => 'PO',
                        'non_po' => 'Non-PO',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if ($data['value'] === 'po') {
                            return $query->whereNotNull('po_id');
                        } elseif ($data['value'] === 'non_po') {
                            return $query->whereNull('po_id');
                        }
                        return $query;
                    }),
                Tables\Filters\SelectFilter::make('status_pembayaran')
                    ->label('Status Pembayaran')
                    ->options(StatusPembayaranEnum::class),
                Tables\Filters\SelectFilter::make('supplier_id')
                    ->label('Supplier')
                    ->relationship('supplier', 'nama_perusahaan')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageLaporanPembelianHarians::route('/'),
        ];
    }
}
