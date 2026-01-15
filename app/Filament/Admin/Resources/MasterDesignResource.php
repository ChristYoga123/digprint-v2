<?php

namespace App\Filament\Admin\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Proses;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Support\RawJs;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use App\Models\ProdukProsesKategori;
use App\Filament\Admin\Resources\MasterDesignResource\Pages;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;

class MasterDesignResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Proses::class;

    protected static ?string $navigationIcon = 'heroicon-o-paint-brush';
    protected static ?string $navigationLabel = 'Master Design';
    protected static ?string $modelLabel = 'Design';
    protected static ?string $pluralModelLabel = 'Master Design';
    protected static ?string $navigationGroup = 'Master Data';
    protected static ?int $navigationSort = 5;

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
            'delete_any',
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()->can('view_master::design') && Auth::user()->can('view_any_master::design');
    }

    public static function canViewAny(): bool
    {
        return Auth::user()->can('view_any_master::design');
    }

    public static function canView(Model $record): bool
    {
        return Auth::user()->can('view_master::design');
    }

    public static function canCreate(): bool
    {
        return Auth::user()->can('create_master::design');
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()->can('update_master::design');
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()->can('delete_master::design');
    }

    public static function canDeleteAny(): bool
    {
        return Auth::user()->can('delete_any_master::design');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Design')
                    ->schema([
                        Forms\Components\TextInput::make('nama')
                            ->label('Nama Design')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Design Simple, Design Premium, dll')
                            ->columnSpanFull(),
                        Forms\Components\ToggleButtons::make('perlu_harga')
                            ->label('Apakah perlu harga default?')
                            ->options([
                                true => 'Ya',
                                false => 'Tidak',
                            ])
                            ->colors([
                                true => 'success',
                                false => 'danger',
                            ])
                            ->default(true)
                            ->grouped()
                            ->live()
                            ->afterStateHydrated(function (Forms\Set $set, $record) {
                                // Jika edit dan ada harga_default, set toggle ke true
                                if ($record && $record->harga_default) {
                                    $set('perlu_harga', true);
                                }
                            })
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('harga_default')
                            ->label('Harga Default')
                            ->numeric()
                            ->prefix('Rp')
                            ->minValue(0)
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->helperText('Harga default yang akan otomatis terisi saat memilih design ini di Deskprint')
                            ->required(fn (Forms\Get $get): bool => (bool) $get('perlu_harga'))
                            ->visible(fn (Forms\Get $get): bool => (bool) $get('perlu_harga'))
                            ->columnSpanFull(),
                        Forms\Components\Hidden::make('produk_proses_kategori_id')
                            ->default(fn () => ProdukProsesKategori::praProduksiId()),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                Proses::query()
                    ->where('produk_proses_kategori_id', ProdukProsesKategori::praProduksiId())
            )
            ->columns([
                Tables\Columns\TextColumn::make('nama')
                    ->label('Nama Design')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('harga_default')
                    ->label('Harga')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageMasterDesigns::route('/'),
        ];
    }
}
