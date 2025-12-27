<?php

namespace App\Filament\Admin\Resources;

use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\KaryawanPekerjaan;
use Filament\Resources\Resource;
use App\Enums\KaryawanPekerjaan\TipeEnum;
use App\Filament\Admin\Resources\LaporanKerjaKaryawanResource\Pages;
use App\Filament\Admin\Resources\LaporanKerjaKaryawanResource\Widgets\StatKerjaKaryawanWidget;

class LaporanKerjaKaryawanResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';
    
    protected static ?string $navigationLabel = 'Laporan Kerja Karyawan';
    
    protected static ?string $modelLabel = 'Laporan Kerja Karyawan';
    
    protected static ?string $pluralModelLabel = 'Laporan Kerja Karyawan';
    
    protected static ?string $slug = 'laporan-kerja-karyawan';

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                User::query()
                    ->where('is_active', true)
                    ->orderBy('name', 'asc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('nik')
                    ->label('NIK')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Karyawan')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('total_pekerjaan')
                    ->label('Total Pekerjaan')
                    ->getStateUsing(function (User $record) {
                        return KaryawanPekerjaan::where('karyawan_id', $record->id)
                            ->where('tipe', TipeEnum::NORMAL)
                            ->count();
                    })
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('pekerjaan_bulan_ini')
                    ->label('Pekerjaan Bulan Ini')
                    ->getStateUsing(function (User $record) {
                        return KaryawanPekerjaan::where('karyawan_id', $record->id)
                            ->where('tipe', TipeEnum::NORMAL)
                            ->whereMonth('created_at', now()->month)
                            ->whereYear('created_at', now()->year)
                            ->count();
                    })
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('pekerjaan_hari_ini')
                    ->label('Pekerjaan Hari Ini')
                    ->getStateUsing(function (User $record) {
                        return KaryawanPekerjaan::where('karyawan_id', $record->id)
                            ->where('tipe', TipeEnum::NORMAL)
                            ->whereDate('created_at', now()->toDateString())
                            ->count();
                    })
                    ->badge()
                    ->color('warning'),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('detail')
                    ->label('Lihat Detail')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->url(fn (User $record) => static::getUrl('detail', ['record' => $record->id])),
            ])
            ->bulkActions([]);
    }

    public static function getWidgets(): array
    {
        return [
            StatKerjaKaryawanWidget::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageLaporanKerjaKaryawans::route('/'),
            'detail' => Pages\LaporanKerjaKaryawanDetailPage::route('/{record}/detail'),
        ];
    }
}

