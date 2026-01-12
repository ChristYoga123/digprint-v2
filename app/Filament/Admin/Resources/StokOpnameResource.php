<?php

namespace App\Filament\Admin\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\StokOpname;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Filament\Notifications\Notification;
use App\Enums\StokOpname\StatusEnum;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use App\Filament\Admin\Resources\StokOpnameResource\Pages;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Illuminate\Database\Eloquent\Model;

class StokOpnameResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = StokOpname::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Stok Opname';

    protected static ?string $modelLabel = 'Stok Opname';

    protected static ?string $pluralModelLabel = 'Stok Opname';

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()->can('view_stok::opname') && Auth::user()->can('view_any_stok::opname');
    }

    public static function canViewAny(): bool
    {
        return Auth::user()->can('view_any_stok::opname');
    }

    public static function canView(Model $record): bool
    {
        return Auth::user()->can('view_stok::opname');
    }

    public static function canCreate(): bool
    {
        return Auth::user()->can('create_stok::opname');
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()->can('update_stok::opname');
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()->can('delete_stok::opname');
    }

    public static function canDeleteAny(): bool
    {
        return Auth::user()->can('delete_any_stok::opname');
    }

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
            'delete_any',
            'approve',
            'print',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Stok Opname')
                    ->schema([
                        Forms\Components\TextInput::make('kode')
                            ->label('Kode Stok Opname')
                            ->required()
                            ->maxLength(255)
                            ->default(fn () => generateKode('SO'))
                            ->helperText(customableState())
                            ->columnSpanFull(),
                        Forms\Components\DatePicker::make('tanggal_opname')
                            ->label('Tanggal Opname')
                            ->required()
                            ->default(now())
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->closeOnDateSelection(),
                        Forms\Components\TextInput::make('nama')
                            ->label('Nama/Deskripsi')
                            ->placeholder('Contoh: Stok Opname Januari 2026')
                            ->maxLength(255),
                        Forms\Components\Textarea::make('catatan')
                            ->label('Catatan')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
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
                    ->limit(30),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Total Item')
                    ->counts('items')
                    ->sortable(),
                Tables\Columns\TextColumn::make('approved_items')
                    ->label('Item Approved')
                    ->getStateUsing(fn (StokOpname $record) => $record->approved_items . '/' . $record->total_items),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('Dibuat Oleh')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('approvedBy.name')
                    ->label('Disetujui Oleh')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('approved_at')
                    ->label('Tanggal Approval')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Terakhir Diubah')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('tanggal_opname', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options(StatusEnum::class),
                DateRangeFilter::make('tanggal_opname')
                    ->label('Tanggal Opname'),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\Action::make('manage')
                    ->label('Kelola')
                    ->icon('heroicon-o-pencil-square')
                    ->color('primary')
                    ->url(fn (StokOpname $record) => Pages\ManageStokOpnameItems::getUrl(['record' => $record->id]))
                    ->visible(fn (StokOpname $record) => 
                        $record->status !== StatusEnum::APPROVED && 
                        Auth::user()->can('update_stok::opname')
                    ),
                Tables\Actions\Action::make('view_items')
                    ->label('Lihat Items')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn (StokOpname $record) => Pages\ManageStokOpnameItems::getUrl(['record' => $record->id]))
                    ->visible(fn (StokOpname $record) => 
                        $record->status === StatusEnum::APPROVED
                    ),
                Tables\Actions\EditAction::make()
                    ->visible(fn (StokOpname $record) => 
                        $record->status === StatusEnum::DRAFT && 
                        Auth::user()->can('update_stok::opname')
                    ),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (StokOpname $record) => 
                        $record->status === StatusEnum::DRAFT && 
                        Auth::user()->can('delete_stok::opname')
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()->can('delete_any_stok::opname')),
                ]),
            ])
            ->headerActions([
                Tables\Actions\Action::make('print_form')
                    ->label('Print Form Stok Opname')
                    ->icon('heroicon-o-printer')
                    ->color('success')
                    ->url(fn () => route('stok-opname.print-form'))
                    ->openUrlInNewTab()
                    ->visible(fn () => Auth::user()->can('print_stok::opname')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageStokOpnames::route('/'),
            'items' => Pages\ManageStokOpnameItems::route('/{record}/items'),
        ];
    }
}

