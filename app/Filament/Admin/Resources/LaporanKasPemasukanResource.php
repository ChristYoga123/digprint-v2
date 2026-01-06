<?php

namespace App\Filament\Admin\Resources;

use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\PencatatanKeuangan;
use App\Models\Transaksi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Admin\Resources\LaporanKasPemasukanResource\Pages;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use Illuminate\Support\Facades\Auth;

class LaporanKasPemasukanResource extends Resource
{
    protected static ?string $model = PencatatanKeuangan::class;

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
        return $table
            ->query(
                PencatatanKeuangan::query()
                    ->where('pencatatan_keuangan_type', Transaksi::class)
                    ->with(['user'])
                    ->orderBy('created_at', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('kode_transaksi')
                    ->label('Kode Transaksi')
                    ->getStateUsing(function (PencatatanKeuangan $record) {
                        $transaksi = Transaksi::find($record->pencatatan_keuangan_id);
                        return $transaksi?->kode ?? '-';
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('pencatatanKeuanganable', function ($q) use ($search) {
                            $q->where('kode', 'like', "%{$search}%");
                        });
                    }),
                Tables\Columns\TextColumn::make('customer')
                    ->label('Customer')
                    ->getStateUsing(function (PencatatanKeuangan $record) {
                        $transaksi = Transaksi::with('customer')->find($record->pencatatan_keuangan_id);
                        return $transaksi?->customer?->nama ?? '-';
                    }),
                Tables\Columns\TextColumn::make('jenis_pembayaran')
                    ->label('Jenis')
                    ->getStateUsing(function (PencatatanKeuangan $record) {
                        // Cek apakah ini DP, Pelunasan, atau Cicilan berdasarkan keterangan atau urutan pembayaran
                        $transaksi = Transaksi::find($record->pencatatan_keuangan_id);
                        if (!$transaksi) return '-';
                        
                        $pembayaranSebelumnya = PencatatanKeuangan::where('pencatatan_keuangan_type', Transaksi::class)
                            ->where('pencatatan_keuangan_id', $transaksi->id)
                            ->where('created_at', '<', $record->created_at)
                            ->count();
                        
                        $totalTagihan = $transaksi->total_harga_transaksi_setelah_diskon;
                        $totalDibayar = PencatatanKeuangan::where('pencatatan_keuangan_type', Transaksi::class)
                            ->where('pencatatan_keuangan_id', $transaksi->id)
                            ->where('created_at', '<=', $record->created_at)
                            ->sum('jumlah_bayar');
                        
                        if ($pembayaranSebelumnya == 0) {
                            if ($totalDibayar >= $totalTagihan) {
                                return 'Pelunasan';
                            }
                            return 'DP';
                        }
                        
                        if ($totalDibayar >= $totalTagihan) {
                            return 'Pelunasan';
                        }
                        
                        return 'Cicilan';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'DP' => 'warning',
                        'Cicilan' => 'info',
                        'Pelunasan' => 'success',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('jumlah_bayar')
                    ->label('Jumlah Bayar')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('metode_pembayaran')
                    ->label('Metode')
                    ->placeholder('-')
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Diterima Oleh')
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
                        return PencatatanKeuangan::where('pencatatan_keuangan_type', Transaksi::class)
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
            'index' => Pages\ManageLaporanKasPemasukans::route('/'),
        ];
    }
}
