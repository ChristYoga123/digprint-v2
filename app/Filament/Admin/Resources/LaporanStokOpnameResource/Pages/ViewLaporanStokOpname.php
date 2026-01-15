<?php

namespace App\Filament\Admin\Resources\LaporanStokOpnameResource\Pages;

use Filament\Tables;
use Filament\Tables\Table;
use App\Models\StokOpname;
use App\Models\StokOpnameItem;
use App\Models\BahanStokBatch;
use Filament\Resources\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use App\Filament\Admin\Resources\LaporanStokOpnameResource;
use App\Enums\StokOpname\ItemStatusEnum;

class ViewLaporanStokOpname extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = LaporanStokOpnameResource::class;

    protected static string $view = 'filament.admin.resources.laporan-stok-opname-resource.pages.view-laporan-stok-opname';

    public StokOpname $record;

    public function mount(StokOpname $record): void
    {
        $this->record = $record->load(['createdBy', 'submittedBy', 'approvedBy']);
    }

    public function getTitle(): string
    {
        return 'Detail Laporan: ' . $this->record->kode;
    }

    public function getBreadcrumbs(): array
    {
        return [
            LaporanStokOpnameResource::getUrl() => 'Laporan Stok Opname',
            '#' => $this->record->kode,
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                StokOpnameItem::query()
                    ->where('stok_opname_id', $this->record->id)
                    ->with(['bahan', 'bahan.satuanTerkecil', 'approvedBy'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('row_number')
                    ->label('No')
                    ->rowIndex(),
                Tables\Columns\TextColumn::make('bahan.kode')
                    ->label('Kode')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('bahan.nama')
                    ->label('Nama Bahan')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('bahan.satuanTerkecil.nama')
                    ->label('Satuan'),
                Tables\Columns\TextColumn::make('stock_system')
                    ->label('Stok Sistem')
                    ->formatStateUsing(fn ($state) => $state !== null ? (floor($state) == $state ? number_format($state, 0, ',', '.') : number_format($state, 2, ',', '.')) : '-')
                    ->alignRight(),
                Tables\Columns\TextColumn::make('stock_physical')
                    ->label('Stok Fisik')
                    ->formatStateUsing(fn ($state) => $state !== null ? (floor($state) == $state ? number_format($state, 0, ',', '.') : number_format($state, 2, ',', '.')) : '-')
                    ->alignRight(),
                Tables\Columns\TextColumn::make('difference')
                    ->label('Selisih')
                    ->formatStateUsing(fn ($state) => $state !== null ? (floor($state) == $state ? number_format($state, 0, ',', '.') : number_format($state, 2, ',', '.')) : '-')
                    ->color(fn ($state) => match (true) {
                        $state === null => 'gray',
                        $state > 0 => 'success',
                        $state < 0 => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn ($state) => match (true) {
                        $state === null => null,
                        $state > 0 => 'heroicon-o-arrow-trending-up',
                        $state < 0 => 'heroicon-o-arrow-trending-down',
                        default => 'heroicon-o-minus',
                    })
                    ->alignRight(),
                Tables\Columns\TextColumn::make('harga_terakhir')
                    ->label('Harga Terakhir')
                    ->getStateUsing(function (StokOpnameItem $record) {
                        $lastBatch = BahanStokBatch::where('bahan_id', $record->bahan_id)
                            ->where('harga_satuan_terkecil', '>', 0)
                            ->orderBy('tanggal_masuk', 'desc')
                            ->first();
                        
                        return $lastBatch?->harga_satuan_terkecil ?? 0;
                    })
                    ->money('IDR')
                    ->alignRight(),
                Tables\Columns\TextColumn::make('nominal_selisih')
                    ->label('Nominal Selisih')
                    ->getStateUsing(function (StokOpnameItem $record) {
                        if ($record->difference === null || $record->difference == 0) {
                            return null;
                        }
                        
                        $lastBatch = BahanStokBatch::where('bahan_id', $record->bahan_id)
                            ->where('harga_satuan_terkecil', '>', 0)
                            ->orderBy('tanggal_masuk', 'desc')
                            ->first();
                        
                        if (!$lastBatch) {
                            return null;
                        }
                        
                        return abs($record->difference) * $lastBatch->harga_satuan_terkecil;
                    })
                    ->money('IDR')
                    ->color(fn (StokOpnameItem $record) => match (true) {
                        $record->difference === null || $record->difference == 0 => 'gray',
                        $record->difference > 0 => 'success',
                        $record->difference < 0 => 'danger',
                        default => 'gray',
                    })
                    ->prefix(fn (StokOpnameItem $record) => $record->difference < 0 ? '-' : ($record->difference > 0 ? '+' : ''))
                    ->alignRight(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                Tables\Columns\TextColumn::make('approvedBy.name')
                    ->label('Diapprove Oleh')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('approved_at')
                    ->label('Tgl Approval')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('catatan')
                    ->label('Catatan')
                    ->limit(20)
                    ->tooltip(fn ($record) => $record->catatan)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status Item')
                    ->options(ItemStatusEnum::class),
                Tables\Filters\Filter::make('has_positive_diff')
                    ->label('Stok Lebih')
                    ->query(fn ($query) => $query->where('difference', '>', 0)),
                Tables\Filters\Filter::make('has_negative_diff')
                    ->label('Stok Kurang')
                    ->query(fn ($query) => $query->where('difference', '<', 0)),
                Tables\Filters\Filter::make('no_diff')
                    ->label('Tidak Ada Selisih')
                    ->query(fn ($query) => $query->where(function ($q) {
                        $q->whereNull('difference')->orWhere('difference', 0);
                    })),
            ])
            ->actions([])
            ->bulkActions([])
            ->headerActions([
                Tables\Actions\Action::make('back')
                    ->label('Kembali')
                    ->icon('heroicon-o-arrow-left')
                    ->color('gray')
                    ->url(LaporanStokOpnameResource::getUrl()),
            ]);
    }
}
