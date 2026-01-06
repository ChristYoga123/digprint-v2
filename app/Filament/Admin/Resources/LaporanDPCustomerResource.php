<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\LaporanDPCustomerResource\Pages;
use App\Models\Transaksi;
use App\Enums\Transaksi\StatusPembayaranEnum;
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
    protected static ?string $model = Transaksi::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    
    protected static ?string $navigationLabel = 'Laporan DP Customer';
    
    protected static ?string $navigationGroup = 'Laporan';
    
    protected static ?string $modelLabel = 'DP Customer';
    
    protected static ?string $pluralModelLabel = 'Laporan DP Customer';
    
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
        return $table
            ->defaultSort('created_at', 'desc')
            ->query(
                Transaksi::query()
                    ->with(['customer', 'pencatatanKeuangans'])
                    // Transaksi TOP yang sudah ada pembayaran (DP)
                    ->where('status_pembayaran', StatusPembayaranEnum::TERM_OF_PAYMENT->value)
                    ->where('jumlah_bayar', '>', 0)
            )
            ->columns([
                TextColumn::make('kode')
                    ->label('Kode Transaksi')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('customer.nama')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Tanggal Transaksi')
                    ->dateTime('d M Y')
                    ->sortable(),
                TextColumn::make('total_harga_transaksi_setelah_diskon')
                    ->label('Total Tagihan')
                    ->money('IDR')
                    ->sortable()
                    ->summarize(Sum::make()->money('IDR')->label('Total Tagihan')),
                TextColumn::make('jumlah_bayar')
                    ->label('DP / Sudah Dibayar')
                    ->money('IDR')
                    ->sortable()
                    ->color('success')
                    ->weight('bold')
                    ->summarize(Sum::make()->money('IDR')->label('Total DP')),
                TextColumn::make('sisa_tagihan')
                    ->label('Sisa Tagihan')
                    ->getStateUsing(function (Transaksi $record) {
                        $sisa = max(0, $record->total_harga_transaksi_setelah_diskon - $record->jumlah_bayar);
                        return $sisa;
                    })
                    ->money('IDR')
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success')
                    ->weight('bold'),
                TextColumn::make('status_pembayaran')
                    ->label('Status')
                    ->badge(StatusPembayaranEnum::class)
                    ->sortable(),
                TextColumn::make('tanggal_jatuh_tempo')
                    ->label('Jatuh Tempo')
                    ->date('d M Y')
                    ->sortable()
                    ->color(fn ($record) => $record->tanggal_jatuh_tempo && Carbon::parse($record->tanggal_jatuh_tempo)->isPast() && $record->status_pembayaran === StatusPembayaranEnum::TERM_OF_PAYMENT ? 'danger' : null)
                    ->description(function ($record) {
                        if (!$record->tanggal_jatuh_tempo) return null;
                        $jatuhTempo = Carbon::parse($record->tanggal_jatuh_tempo);
                        if ($record->status_pembayaran === StatusPembayaranEnum::TERM_OF_PAYMENT) {
                            if ($jatuhTempo->isPast()) {
                                return new HtmlString('<span class="text-danger-500 font-bold">Lewat ' . $jatuhTempo->diffForHumans() . '</span>');
                            } else {
                                return $jatuhTempo->diffForHumans();
                            }
                        }
                        return null;
                    }),
            ])
            ->filters([
                DateRangeFilter::make('created_at')
                    ->label('Tanggal')
                    ->defaultThisMonth(),
                SelectFilter::make('customer_id')
                    ->relationship('customer', 'nama')
                    ->label('Customer')
                    ->searchable()
                    ->preload(),
                Filter::make('jatuh_tempo_lewat')
                    ->label('Jatuh Tempo Lewat')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereNotNull('tanggal_jatuh_tempo')
                        ->whereDate('tanggal_jatuh_tempo', '<', now())
                    )
                    ->toggle(),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn (Transaksi $record) => TransaksiResource::getUrl('index', ['tableSearch' => $record->kode])),
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
