<?php

namespace App\Filament\Admin\Resources;

use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\PencatatanKeuangan;
use App\Models\BahanMutasiFaktur;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Admin\Resources\LaporanPembayaranSupplierResource\Pages;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class LaporanPembayaranSupplierResource extends Resource
{
    protected static ?string $model = PencatatanKeuangan::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    
    protected static ?string $navigationLabel = 'Laporan Pembayaran Supplier';
    
    protected static ?string $modelLabel = 'Laporan Pembayaran Supplier';
    
    protected static ?string $pluralModelLabel = 'Laporan Pembayaran Supplier';
    
    protected static ?string $slug = 'laporan-pembayaran-supplier';

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
                PencatatanKeuangan::query()
                    ->where('pencatatan_keuangan_type', BahanMutasiFaktur::class)
                    ->with(['user'])
                    ->orderBy('created_at', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('kode_faktur')
                    ->label('Kode Faktur')
                    ->getStateUsing(function (PencatatanKeuangan $record) {
                        $faktur = BahanMutasiFaktur::find($record->pencatatan_keuangan_id);
                        return $faktur?->kode ?? '-';
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('pencatatanKeuanganable', function ($q) use ($search) {
                            $q->where('kode', 'like', "%{$search}%");
                        });
                    }),
                Tables\Columns\TextColumn::make('supplier')
                    ->label('Supplier')
                    ->getStateUsing(function (PencatatanKeuangan $record) {
                        $faktur = BahanMutasiFaktur::with('supplier')->find($record->pencatatan_keuangan_id);
                        return $faktur?->supplier?->nama_perusahaan ?? '-';
                    }),
                Tables\Columns\TextColumn::make('jumlah_bayar')
                    ->label('Jumlah Bayar')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('metode_pembayaran')
                    ->label('Metode Pembayaran')
                    ->placeholder('-')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Dibayar Oleh')
                    ->searchable(),
                Tables\Columns\TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->placeholder('-')
                    ->wrap()
                    ->limit(50),
            ])
            ->filters([
                DateRangeFilter::make('created_at')
                    ->label('Tanggal'),
                Tables\Filters\SelectFilter::make('metode_pembayaran')
                    ->label('Metode Pembayaran')
                    ->options(function () {
                        return PencatatanKeuangan::where('pencatatan_keuangan_type', BahanMutasiFaktur::class)
                            ->whereNotNull('metode_pembayaran')
                            ->distinct()
                            ->pluck('metode_pembayaran', 'metode_pembayaran')
                            ->toArray();
                    })
                    ->searchable(),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageLaporanPembayaranSuppliers::route('/'),
        ];
    }
}
