<?php

namespace App\Filament\Admin\Resources;

use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\BahanMutasiFaktur;
use App\Models\PencatatanKeuangan;
use Illuminate\Support\HtmlString;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\BahanMutasiFaktur\StatusPembayaranEnum;
use App\Filament\Admin\Resources\LaporanHutangSupplierResource\Pages;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use Illuminate\Support\Facades\Auth;

class LaporanHutangSupplierResource extends Resource
{
    protected static ?string $model = BahanMutasiFaktur::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-down';
    
    protected static ?string $navigationLabel = 'Laporan Hutang Supplier';
    
    protected static ?string $modelLabel = 'Laporan Hutang Supplier';
    
    protected static ?string $pluralModelLabel = 'Laporan Hutang Supplier';
    
    protected static ?string $slug = 'laporan-hutang-supplier';

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()->can('view_laporan::hutang::supplier') && Auth::user()->can('view_any_laporan::hutang::supplier');
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
                    ->where('status_pembayaran', StatusPembayaranEnum::TERM_OF_PAYMENT)
                    ->with(['supplier'])
                    ->orderBy('tanggal_jatuh_tempo', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('kode')
                    ->label('Kode Faktur')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('supplier.nama_perusahaan')
                    ->label('Supplier')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_tagihan')
                    ->label('Total Tagihan')
                    ->getStateUsing(fn (BahanMutasiFaktur $record) => 
                        formatRupiah($record->total_harga_setelah_diskon ?? $record->total_harga)
                    )
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query->orderBy('total_harga_setelah_diskon', $direction);
                    }),
                Tables\Columns\TextColumn::make('sudah_dibayar')
                    ->label('Sudah Dibayar')
                    ->getStateUsing(function (BahanMutasiFaktur $record) {
                        $total = $record->pencatatanKeuangans()->sum('jumlah_bayar');
                        return formatRupiah($total);
                    })
                    ->color('success'),
                Tables\Columns\TextColumn::make('sisa_hutang')
                    ->label('Sisa Hutang')
                    ->getStateUsing(function (BahanMutasiFaktur $record) {
                        $totalTagihan = $record->total_harga_setelah_diskon ?? $record->total_harga;
                        $sudahBayar = $record->pencatatanKeuangans()->sum('jumlah_bayar');
                        $sisa = max(0, $totalTagihan - $sudahBayar);
                        return formatRupiah($sisa);
                    })
                    ->color('danger')
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('tanggal_jatuh_tempo')
                    ->label('Jatuh Tempo')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status_jatuh_tempo')
                    ->label('Status')
                    ->getStateUsing(function (BahanMutasiFaktur $record) {
                        if (!$record->tanggal_jatuh_tempo) return 'Tidak Ada';
                        
                        $jatuhTempo = Carbon::parse($record->tanggal_jatuh_tempo);
                        $today = Carbon::today();
                        
                        if ($today->gt($jatuhTempo)) {
                            $diff = $today->diffInDays($jatuhTempo);
                            return "Terlambat {$diff} hari";
                        } elseif ($today->diffInDays($jatuhTempo) <= 7) {
                            return 'Segera Jatuh Tempo';
                        }
                        return 'Belum Jatuh Tempo';
                    })
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        str_contains($state, 'Terlambat') => 'danger',
                        $state === 'Segera Jatuh Tempo' => 'warning',
                        default => 'success',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Faktur')
                    ->date('d M Y')
                    ->sortable(),
            ])
            ->filters([
                DateRangeFilter::make('tanggal_jatuh_tempo')
                    ->label('Jatuh Tempo'),
                Tables\Filters\SelectFilter::make('supplier_id')
                    ->label('Supplier')
                    ->relationship('supplier', 'nama_perusahaan')
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('overdue')
                    ->label('Sudah Jatuh Tempo')
                    ->query(fn (Builder $query): Builder => $query->where('tanggal_jatuh_tempo', '<', now()->toDateString())),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageLaporanHutangSuppliers::route('/'),
        ];
    }
}
