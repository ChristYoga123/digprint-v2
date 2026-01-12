<?php

namespace App\Filament\Admin\Resources;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Enums\FiltersLayout;
use App\Models\TransaksiProsesBahanUsage;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Columns\Summarizers\Sum;
use App\Filament\Admin\Resources\LaporanHPPResource\Pages;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class LaporanHPPResource extends Resource
{
    protected static ?string $model = TransaksiProsesBahanUsage::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    
    protected static ?string $navigationLabel = 'Laporan HPP';
    
    protected static ?string $navigationGroup = 'Laporan';
    
    protected static ?string $modelLabel = 'Laporan HPP';
    
    protected static ?string $pluralModelLabel = 'Laporan HPP';
    
    protected static ?int $navigationSort = 10;
    
    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()->can('view_laporan::h::p::p');
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
        return $table
            ->defaultSort('created_at', 'desc')
            ->query(
                TransaksiProsesBahanUsage::query()
                    ->with([
                        'transaksiProses.transaksiProduk.transaksi.customer',
                        'transaksiProses.produkProses',
                        'bahan',
                    ])
            )
            ->columns([
                TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('transaksiProses.transaksiProduk.transaksi.kode')
                    ->label('Kode Transaksi')
                    ->searchable()
                    ->sortable()
                    ->url(fn ($record) => $record->transaksiProses?->transaksiProduk?->transaksi 
                        ? TransaksiResource::getUrl('index', ['tableSearch' => $record->transaksiProses->transaksiProduk->transaksi->kode]) 
                        : null),
                TextColumn::make('transaksiProses.transaksiProduk.transaksi.customer.nama')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('transaksiProses.produkProses.nama')
                    ->label('Proses')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('bahan.nama')
                    ->label('Bahan')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('jumlah_digunakan')
                    ->label('Jumlah')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(fn ($record) => ' ' . ($record->bahan?->satuan_terkecil ?? ''))
                    ->sortable()
                    ->summarize(Sum::make()->label('Total Jumlah')),
                TextColumn::make('hpp')
                    ->label('HPP')
                    ->money('IDR')
                    ->sortable()
                    ->summarize(Sum::make()->money('IDR')->label('Total HPP')),
            ])
            ->filters([
                SelectFilter::make('bahan_id')
                    ->relationship('bahan', 'nama')
                    ->label('Bahan')
                    ->searchable()
                    ->preload(),
                DateRangeFilter::make('created_at')
                    ->label('Tanggal')
                    ->defaultThisMonth(),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                // No actions - read only
            ])
            ->bulkActions([
                // No bulk actions - read only
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageLaporanHPPS::route('/'),
        ];
    }
}
