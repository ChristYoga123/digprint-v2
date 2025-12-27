<?php

namespace App\Filament\Admin\Resources;

use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\KaryawanPekerjaan;
use Filament\Resources\Resource;
use App\Enums\KaryawanPekerjaan\TipeEnum;
use App\Filament\Admin\Resources\LaporanLemburKaryawanResource\Pages;
use App\Filament\Admin\Resources\LaporanLemburKaryawanResource\Widgets\StatLemburKaryawanWidget;

class LaporanLemburKaryawanResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';
    
    protected static ?string $navigationLabel = 'Laporan Lembur Karyawan';
    
    protected static ?string $modelLabel = 'Laporan Lembur Karyawan';
    
    protected static ?string $pluralModelLabel = 'Laporan Lembur Karyawan';
    
    protected static ?string $slug = 'laporan-lembur-karyawan';

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
                Tables\Columns\TextColumn::make('total_lembur')
                    ->label('Total Lembur')
                    ->getStateUsing(function (User $record) {
                        return KaryawanPekerjaan::where('karyawan_id', $record->id)
                            ->where('tipe', TipeEnum::LEMBUR)
                            ->count();
                    })
                    ->badge()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('lembur_disetujui')
                    ->label('Disetujui')
                    ->getStateUsing(function (User $record) {
                        return KaryawanPekerjaan::where('karyawan_id', $record->id)
                            ->where('tipe', TipeEnum::LEMBUR)
                            ->where('apakah_diapprove_lembur', true)
                            ->count();
                    })
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('lembur_ditolak')
                    ->label('Ditolak')
                    ->getStateUsing(function (User $record) {
                        return KaryawanPekerjaan::where('karyawan_id', $record->id)
                            ->where('tipe', TipeEnum::LEMBUR)
                            ->where('apakah_diapprove_lembur', false)
                            ->count();
                    })
                    ->badge()
                    ->color('danger'),
                Tables\Columns\TextColumn::make('lembur_pending')
                    ->label('Pending')
                    ->getStateUsing(function (User $record) {
                        return KaryawanPekerjaan::where('karyawan_id', $record->id)
                            ->where('tipe', TipeEnum::LEMBUR)
                            ->whereNull('apakah_diapprove_lembur')
                            ->count();
                    })
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('lembur_bulan_ini')
                    ->label('Bulan Ini')
                    ->getStateUsing(function (User $record) {
                        return KaryawanPekerjaan::where('karyawan_id', $record->id)
                            ->where('tipe', TipeEnum::LEMBUR)
                            ->where('apakah_diapprove_lembur', true)
                            ->whereMonth('jam_lembur_mulai', now()->month)
                            ->whereYear('jam_lembur_mulai', now()->year)
                            ->count();
                    })
                    ->badge()
                    ->color('info'),
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
            StatLemburKaryawanWidget::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageLaporanLemburKaryawans::route('/'),
            'detail' => Pages\LaporanLemburKaryawanDetailPage::route('/{record}/detail'),
        ];
    }
}

