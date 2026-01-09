<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Produk;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class TopProdukWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    
    protected int | string | array $columnSpan = [
        'md' => 2,
        'xl' => 3,
    ];

    protected static ?string $heading = '🏆 10 Produk Terlaris';

    protected static ?string $pollingInterval = null;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Produk::query()
                    ->withCount('transaksiProduks as total_transaksi')
                    ->withSum('transaksiProduks as total_quantity', 'jumlah')
                    ->withSum('transaksiProduks as total_nominal', 'total_harga_produk_setelah_diskon')
                    ->having('total_transaksi', '>', 0)
                    ->orderByDesc('total_nominal')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('rank')
                    ->label('#')
                    ->rowIndex()
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        1 => 'warning',
                        2 => 'gray',
                        3 => 'primary',
                        default => 'secondary',
                    })
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama Produk')
                    ->searchable()
                    ->limit(25)
                    ->tooltip(fn (Produk $record) => $record->nama)
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('total_transaksi')
                    ->label('Transaksi')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('total_quantity')
                    ->label('Qty')
                    ->numeric()
                    ->badge()
                    ->color('success')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('total_nominal')
                    ->label('Total Nominal')
                    ->money('IDR')
                    ->color('success')
                    ->weight('bold')
                    ->alignRight(),
            ])
            ->paginated(false)
            ->striped()
            ->emptyStateHeading('Belum ada data transaksi')
            ->emptyStateDescription('Data produk terlaris akan muncul setelah ada transaksi.')
            ->emptyStateIcon('heroicon-o-shopping-cart');
    }
}

