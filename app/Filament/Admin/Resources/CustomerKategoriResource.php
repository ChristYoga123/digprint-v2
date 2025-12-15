<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\CustomerKategoriResource\Pages;
use App\Filament\Admin\Resources\CustomerKategoriResource\RelationManagers;
use App\Models\CustomerKategori;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CustomerKategoriResource extends Resource
{
    protected static ?string $model = CustomerKategori::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Kategori Customer';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('kode')
                    ->required()
                    ->maxLength(255)
                    ->helperText(customableState())
                    ->default(fn($record) => $record?->kode ?? generateKode('CST'))
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('nama')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),
                Forms\Components\Toggle::make('perlu_data_perusahaan')
                    ->label('Perlu Data Perusahaan')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama')
                    ->searchable(query: function(Builder $query, string $search): Builder {
                        return $query->where('nama', 'like', '%' . $search . '%')
                            ->orWhere('kode', 'like', '%' . $search . '%');
                    })
                    ->description(fn(CustomerKategori $record) => "(Kode:{$record->kode})"),
                Tables\Columns\ToggleColumn::make('perlu_data_perusahaan')
                    ->label('Perlu Data Perusahaan'),
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
            'index' => Pages\ManageCustomerKategoris::route('/'),
        ];
    }
}
