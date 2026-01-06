<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TransaksiProsesResource\Pages;
use App\Filament\Admin\Resources\TransaksiProsesResource\RelationManagers;
use App\Models\TransaksiProses;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class TransaksiProsesResource extends Resource
{
    protected static ?string $model = TransaksiProses::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()->can('view_transaksi::proses') && Auth::user()->can('view_any_transaksi::proses');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('transaksi_produk_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('produk_proses_id')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('kloter_id')
                    ->numeric(),
                Forms\Components\TextInput::make('urutan')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('status_proses')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaksi_produk_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('produk_proses_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('kloter_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('urutan')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status_proses')
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
                    ->visible(fn () => Auth::user()->can('update_transaksi::proses')),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => Auth::user()->can('delete_transaksi::proses')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()->can('delete_any_transaksi::proses')),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageTransaksiProses::route('/'),
        ];
    }
}
