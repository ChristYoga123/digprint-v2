<?php

namespace App\Filament\Admin\Resources\BahanResource\Pages;

use Carbon\Carbon;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\BahanStokBatch;
use Filament\Resources\Pages\Page;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Admin\Resources\BahanResource;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Illuminate\Support\HtmlString;

class BahanBatchPage extends Page implements HasTable
{
    use InteractsWithTable, InteractsWithRecord;

    protected static string $resource = BahanResource::class;

    protected static string $view = 'filament.admin.resources.bahan-resource.pages.bahan-batch-page';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string
    {
        return 'Stok Batch ' . $this->record->nama;
    }


    public function table(Table $table): Table
    {
        return $table
            ->query(BahanStokBatch::query()->where('bahan_id', $this->record->id))
            ->columns([
                Tables\Columns\TextColumn::make('bahan.nama')
                    ->label('Nama Bahan')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('bahan', function (Builder $query) use ($search){
                            $query->where('nama', 'like', '%' . $search . '%')
                                ->orWhere('kode', 'like', '%' . $search . '%');
                        });
                    })
                    ->sortable()
                    ->description(fn (BahanStokBatch $record) => "({$record->bahan->kode})"),
                Tables\Columns\TextColumn::make('bahanMutasi.kode')
                    ->label('Kode Mutasi Masuk')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold')
                    ->copyableState(fn (BahanStokBatch $record) => $record->bahanMutasi->kode)
                    ->description(function(BahanStokBatch $record) {
                        // return Tanggal Mutasi, Jumlah mutasi
                        return new HtmlString('<span class="text-gray-500"> Masuk pada: ' . Carbon::parse($record->bahanMutasi->created_at)->translatedFormat('d M Y H:i:s') . '<br> Jumlah: ' . formatRupiah($record->bahanMutasi->jumlah_mutasi) . ' ' . $record->bahan->satuanTerkecil->nama . '</span>');
                    }),
                Tables\Columns\TextColumn::make('jumlah_tersedia')
                    ->label('Jumlah Tersedia')
                    ->numeric()
                    ->suffix(fn (BahanStokBatch $record) => ' ' . ($record->bahan->satuanTerkecil->nama ?? ''))
                    ->badge()
                    ->color(fn (BahanStokBatch $record) => $record->jumlah_tersedia == 0 ? 'danger' : ($record->jumlah_tersedia < $record->jumlah_masuk * 0.2 ? 'warning' : 'success'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('harga_satuan_terkecil')
                    ->label('Harga Satuan Terkecil')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('harga_satuan_terbesar')
                    ->label('Harga Satuan Terbesar')
                    ->money('IDR')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('ketersediaan')
                    ->label('Ketersediaan')
                    ->options([
                        'tersedia' => 'Tersedia',
                        'habis' => 'Habis',
                    ])
                    ->multiple()
                    ->query(fn (Builder $query): Builder => $query->where('jumlah_tersedia', '>', 0)),
            ], layout: FiltersLayout::AboveContent)
            ->defaultSort('tanggal_masuk', 'asc')
            ->actions([
                // Read-only, tidak ada actions
            ])
            ->bulkActions([
                // Read-only, tidak ada bulk actions
            ]);
    }
}
