<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\ProdukProsesKategoriResource\Pages;
use App\Filament\Admin\Resources\ProdukProsesKategoriResource\RelationManagers;
use App\Models\ProdukProsesKategori;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class ProdukProsesKategoriResource extends Resource
{
    protected static ?string $model = ProdukProsesKategori::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationLabel = 'Kategori Proses Produk';

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()->can('view_produk::proses::kategori') && Auth::user()->can('view_any_produk::proses::kategori');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nama')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn () => Auth::user()->can('update_produk::proses::kategori')),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => Auth::user()->can('delete_produk::proses::kategori')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()->can('delete_any_produk::proses::kategori')),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageProdukProsesKategoris::route('/'),
        ];
    }
}

