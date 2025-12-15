<?php

namespace App\Filament\Admin\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Customer;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Admin\Resources\CustomerResource\Pages;
use App\Filament\Admin\Resources\CustomerKategoriResource;
use App\Filament\Admin\Resources\CustomerResource\RelationManagers;
use App\Models\CustomerKategori;
use Filament\Forms\Get;
use Illuminate\Support\HtmlString;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Pelanggan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('customer_kategori_id')
                    ->label('Kategori Pelanggan')
                    ->required()
                    ->relationship('customerKategori', 'nama')
                    ->searchable()
                    ->preload()
                    ->live()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('kode')
                            ->label('Kode Kategori')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->default(generateKode('CST')),
                        Forms\Components\TextInput::make('nama')
                            ->label('Nama Kategori')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\Toggle::make('perlu_data_perusahaan')
                            ->label('Perlu Data Perusahaan')
                            ->default(false),
                    ])
                    ->helperText(creatableState()),
                Forms\Components\TextInput::make('nama')
                    ->label('Nama Pelanggan')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('no_hp1')
                    ->maxLength(255),
                Forms\Components\TextInput::make('no_hp2')
                    ->maxLength(255),
                Forms\Components\Textarea::make('alamat')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('nama_perusahaan')
                    ->maxLength(255)
                    ->columnSpanFull()
                    ->required(fn(Get $get) => CustomerKategori::find($get('customer_kategori_id'))->perlu_data_perusahaan ?? false)
                    ->visible(fn(Get $get) => CustomerKategori::find($get('customer_kategori_id'))->perlu_data_perusahaan ?? false),
                Forms\Components\Textarea::make('alamat_perusahaan')
                    ->columnSpanFull()
                    ->required(fn(Get $get) => CustomerKategori::find($get('customer_kategori_id'))->perlu_data_perusahaan ?? false)
                    ->visible(fn(Get $get) => CustomerKategori::find($get('customer_kategori_id'))->perlu_data_perusahaan ?? false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama')
                    ->label('Identitas Pelanggan')
                    ->searchable(query: fn(Builder $query, string $search) => $query->where('nama', 'like', '%' . $search . '%')->orWhereHas('customerKategori', function ($query) use ($search) {
                        $query->where('nama', 'like', '%' . $search . '%');
                    }))
                    ->description(fn(Customer $record) => "({$record->customerKategori->kode}) - {$record->customerKategori->nama}"),
                Tables\Columns\TextColumn::make('kontak')
                    ->label('Kontak')
                    ->copyable()
                    ->copyableState(fn(Customer $record) => $record->no_hp1 ? $record->no_hp1 : $record->no_hp2)
                    ->copyMessage('Kontak berhasil disalin')
                    ->copyMessageDuration(1500)
                    ->getStateUsing(function(Customer $record) {
                        return new HtmlString('<ul class="list-disc list-inside"><li>No. HP 1: ' . $record->no_hp1 . '</li><li>No. HP 2: ' . $record->no_hp2 . '</li></ul>');
                    }),
                Tables\Columns\TextColumn::make('nama_perusahaan')
                    ->searchable()
                    ->getStateUsing(fn(Customer $record) => $record->nama_perusahaan ?? '-'),
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
            'index' => Pages\ManageCustomers::route('/'),
        ];
    }
}
