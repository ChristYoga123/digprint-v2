<?php

namespace App\Filament\Admin\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\StokOpname;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use App\Enums\StokOpname\StatusEnum;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use App\Filament\Admin\Resources\LaporanStokOpnameResource\Pages;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Illuminate\Database\Eloquent\Model;

class LaporanStokOpnameResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = StokOpname::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Laporan Stok Opname';

    protected static ?string $modelLabel = 'Laporan Stok Opname';

    protected static ?string $pluralModelLabel = 'Laporan Stok Opname';

    protected static ?string $slug = 'laporan-stok-opname';

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()->can('view_laporan::stok::opname') && Auth::user()->can('view_any_laporan::stok::opname');
    }

    public static function canViewAny(): bool
    {
        return Auth::user()->can('view_any_laporan::stok::opname');
    }

    public static function canView(Model $record): bool
    {
        return Auth::user()->can('view_laporan::stok::opname');
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

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'export',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                StokOpname::query()
                    ->withCount('items')
                    ->withSum(['items as total_positive' => function ($query) {
                        $query->where('difference', '>', 0);
                    }], 'difference')
                    ->withSum(['items as total_negative' => function ($query) {
                        $query->where('difference', '<', 0);
                    }], 'difference')
                    ->withSum('items', 'difference')
            )
            ->columns([
                Tables\Columns\TextColumn::make('kode')
                    ->label('Kode')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('tanggal_opname')
                    ->label('Tanggal Opname')
                    ->date('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama/Deskripsi')
                    ->searchable()
                    ->limit(25),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Total Item')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_positive')
                    ->label('Stok Lebih (+)')
                    ->formatStateUsing(fn ($state) => $state !== null ? '+' . (floor($state) == $state ? number_format($state, 0, ',', '.') : number_format($state, 2, ',', '.')) : '0')
                    ->color('success')
                    ->default(0),
                Tables\Columns\TextColumn::make('total_negative')
                    ->label('Stok Kurang (-)')
                    ->formatStateUsing(fn ($state) => $state !== null ? (floor($state) == $state ? number_format($state, 0, ',', '.') : number_format($state, 2, ',', '.')) : '0')
                    ->color('danger')
                    ->default(0),
                Tables\Columns\TextColumn::make('items_sum_difference')
                    ->label('Selisih Bersih')
                    ->formatStateUsing(fn ($state) => $state !== null ? (floor($state) == $state ? number_format($state, 0, ',', '.') : number_format($state, 2, ',', '.')) : '0')
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger')
                    ->default(0),
                Tables\Columns\TextColumn::make('approvedBy.name')
                    ->label('Diapprove Oleh')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('approved_at')
                    ->label('Tanggal Approval')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('tanggal_opname', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(StatusEnum::class)
                    ->multiple(),
                DateRangeFilter::make('tanggal_opname')
                    ->label('Periode Opname'),
                Tables\Filters\SelectFilter::make('approved_by')
                    ->label('Diapprove Oleh')
                    ->relationship('approvedBy', 'name')
                    ->searchable()
                    ->preload(),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\Action::make('view_detail')
                    ->label('Lihat Detail')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn (StokOpname $record) => Pages\ViewLaporanStokOpname::getUrl(['record' => $record->id])),
                Tables\Actions\Action::make('export_detail')
                    ->label('Export')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn (StokOpname $record) => route('stok-opname.export', ['id' => $record->id]))
                    ->openUrlInNewTab()
                    ->visible(fn () => Auth::user()->can('export_laporan::stok::opname')),
            ])
            ->bulkActions([])
            ->headerActions([
                Tables\Actions\Action::make('export_all')
                    ->label('Export Semua')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn () => route('stok-opname.export-all'))
                    ->openUrlInNewTab()
                    ->visible(fn () => Auth::user()->can('export_laporan::stok::opname')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageLaporanStokOpnames::route('/'),
            'view' => Pages\ViewLaporanStokOpname::route('/{record}'),
        ];
    }
}


