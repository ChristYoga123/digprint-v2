<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\SatuanResource\Pages;
use App\Filament\Admin\Resources\SatuanResource\RelationManagers;
use App\Models\Satuan;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

class SatuanResource extends Resource
{
    protected static ?string $model = Satuan::class;

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()->can('view_satuan') && Auth::user()->can('view_any_satuan');
    }

    public static function canViewAny(): bool
    {
        return Auth::user()->can('view_any_satuan');
    }

    public static function canView(Model $record): bool
    {
        return Auth::user()->can('view_satuan');
    }

    public static function canCreate(): bool
    {
        return Auth::user()->can('create_satuan');
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()->can('update_satuan');
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()->can('delete_satuan');
    }

    public static function canDeleteAny(): bool
    {
        return Auth::user()->can('delete_any_satuan');
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
                    ->visible(fn () => Auth::user()->can('update_satuan')),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => Auth::user()->can('delete_satuan')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()->can('delete_any_satuan')),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSatuans::route('/'),
        ];
    }
}

