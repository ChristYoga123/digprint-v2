<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Bahan;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class BahanMinimalWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    
    protected int | string | array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];

    protected static ?string $heading = '⚠️ Bahan Stok Rendah';

    protected static ?string $pollingInterval = null;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Bahan::query()
                    ->with(['satuanTerkecil', 'stokBatches'])
                    ->whereNotNull('stok_minimal')
                    ->where('stok_minimal', '>', 0)
            )
            ->columns([
                Tables\Columns\TextColumn::make('kode')
                    ->label('Kode')
                    ->searchable()
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama Bahan')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn (Bahan $record) => $record->nama)
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('stok')
                    ->label('Stok Saat Ini')
                    ->getStateUsing(fn (Bahan $record) => $record->stok)
                    ->suffix(fn (Bahan $record) => ' ' . ($record->satuanTerkecil?->nama ?? ''))
                    ->badge()
                    ->color(fn (Bahan $record) => $record->stok <= $record->stok_minimal ? 'danger' : 'success')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('stok_minimal')
                    ->label('Stok Minimal')
                    ->suffix(fn (Bahan $record) => ' ' . ($record->satuanTerkecil?->nama ?? ''))
                    ->badge()
                    ->color('warning')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('selisih')
                    ->label('Selisih')
                    ->getStateUsing(fn (Bahan $record) => $record->stok - $record->stok_minimal)
                    ->formatStateUsing(fn ($state) => $state >= 0 ? '+' . $state : $state)
                    ->badge()
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger')
                    ->icon(fn ($state) => $state >= 0 ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('status_stok')
                    ->label('Status')
                    ->getStateUsing(function (Bahan $record) {
                        $stok = $record->stok;
                        $minimal = $record->stok_minimal;
                        
                        if ($stok == 0) return 'Habis';
                        if ($stok <= $minimal * 0.5) return 'Kritis';
                        if ($stok <= $minimal) return 'Rendah';
                        return 'Aman';
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'Habis' => 'danger',
                        'Kritis' => 'danger',
                        'Rendah' => 'warning',
                        'Aman' => 'success',
                        default => 'gray',
                    })
                    ->icon(fn ($state) => match ($state) {
                        'Habis', 'Kritis' => 'heroicon-m-x-circle',
                        'Rendah' => 'heroicon-m-exclamation-circle',
                        'Aman' => 'heroicon-m-check-circle',
                        default => null,
                    }),
            ])
            ->defaultSort(fn (Builder $query) => $query
                ->orderByRaw('(
                    SELECT COALESCE(SUM(jumlah_tersedia), 0) 
                    FROM bahan_stok_batches 
                    WHERE bahan_stok_batches.bahan_id = bahans.id
                ) - stok_minimal ASC')
            )
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->striped()
            ->emptyStateHeading('Semua stok aman')
            ->emptyStateDescription('Tidak ada bahan yang stoknya mendekati batas minimal.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
