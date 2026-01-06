<?php

namespace App\Filament\Admin\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Supplier;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Illuminate\Support\HtmlString;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Admin\Resources\SupplierResource\Pages;
use App\Filament\Admin\Resources\SupplierResource\RelationManagers;
use Illuminate\Support\Facades\Auth;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationLabel = 'Supplier';

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()->can('view_supplier') && Auth::user()->can('view_any_supplier');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('kode')
                    ->label('Kode Supplier')
                    ->required()
                    ->maxLength(255)
                    ->helperText(customableState())
                    ->default(fn ($record) => $record?->kode ?? generateKode('SPL')),
                Forms\Components\TextInput::make('nama_perusahaan')
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->label('Nama Perusahaan'),
                Forms\Components\TextInput::make('nama_sales')
                    ->required()
                    ->maxLength(255)
                    ->label('Nama Sales'),
                Forms\Components\TextInput::make('no_hp_sales')
                    ->required()
                    ->inputMode('numeric')
                    ->maxLength(255)
                    ->label('No. HP Sales'),
                Forms\Components\Textarea::make('alamat_perusahaan')
                    ->required()
                    ->label('Alamat Perusahaan'),
                Forms\Components\Textarea::make('alamat_gudang')
                    ->required()
                    ->label('Alamat Gudang'),
                Forms\Components\Select::make('metode_pembayaran1')
                    ->required()
                    ->preload()
                    ->searchable()
                    ->options(function (Forms\Get $get) {
                        $allOptions = getBankData();
                        $selectedMethod2 = $get('metode_pembayaran2');
                        if ($selectedMethod2) {
                            unset($allOptions[$selectedMethod2]);
                        }
                        return $allOptions;
                    })
                    ->label('Metode Pembayaran 1')
                    ->live(),
                Forms\Components\TextInput::make('nomor_rekening1')
                    ->required(fn ($get) => $get('metode_pembayaran1') !== 'Cash')
                    ->maxLength(255)
                    ->label('Nomor Rekening 1')
                    ->inputMode('numeric')
                    ->visible(fn ($get) => $get('metode_pembayaran1') !== 'Cash'),
                Forms\Components\TextInput::make('nama_rekening1')
                    ->required(fn ($get) => $get('metode_pembayaran1') !== 'Cash')
                    ->maxLength(255)
                    ->columnSpanFull()
                    ->label('Nama Rekening 1')
                    ->visible(fn ($get) => $get('metode_pembayaran1') !== 'Cash'),
                Forms\Components\Select::make('metode_pembayaran2')
                    ->options(function (Forms\Get $get) {
                        $allOptions = getBankData();
                        $selectedMethod1 = $get('metode_pembayaran1');
                        if ($selectedMethod1) {
                            unset($allOptions[$selectedMethod1]);
                        }
                        return $allOptions;
                    })
                    ->required()
                    ->preload()
                    ->searchable()
                    ->label('Metode Pembayaran 2')
                    ->live(),
                Forms\Components\TextInput::make('nomor_rekening2')
                    ->required(fn ($get) => $get('metode_pembayaran2') !== 'Cash')
                    ->maxLength(255)
                    ->label('Nomor Rekening 2')
                    ->inputMode('numeric')
                    ->visible(fn ($get) => $get('metode_pembayaran2') !== 'Cash'),
                Forms\Components\TextInput::make('nama_rekening2')
                    ->required(fn ($get) => $get('metode_pembayaran2') !== 'Cash')
                    ->maxLength(255)
                    ->columnSpanFull()
                    ->label('Nama Rekening 2')
                    ->visible(fn ($get) => $get('metode_pembayaran2') !== 'Cash'),
                Forms\Components\Grid::make(1)
                    ->schema([
                        Forms\Components\ToggleButtons::make('is_active')
                            ->required()
                            ->label('Apakah supplier ini aktif?')
                            ->boolean()
                            ->grouped(),
                        Forms\Components\ToggleButtons::make('is_pkp')
                            ->required()
                            ->label('Apakah supplier ini PKP?')
                            ->boolean()
                            ->grouped(),
                    ]),
                Forms\Components\TextInput::make('npwp')
                    ->maxLength(255)
                    ->columnSpanFull()
                    ->label('Nomor Pokok Wajib Pajak')
                    ->inputMode('numeric'),
                Forms\Components\ToggleButtons::make('is_po')
                    ->required()
                    ->label('Apakah supplier ini merupakan supplier PO?')
                    ->boolean()
                    ->grouped()
                    ->helperText('Supplier yang memiliki PO akan muncul di menu PO'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nama_perusahaan')
                    ->searchable(query: function(Builder $query, string $search): Builder {
                        return $query->where('nama_perusahaan', 'like', '%' . $search . '%')
                            ->orWhere('kode', 'like', '%' . $search . '%');
                    })
                    ->label('Nama Perusahaan')
                    ->description(fn(Supplier $record) => "(Kode:{$record->kode})"),
                Tables\Columns\TextColumn::make('nama_sales')
                    ->copyable()
                    ->copyableState(fn(Supplier $record) => $record->no_hp_sales)
                    ->copyMessage('No. HP Sales berhasil disalin')
                    ->copyMessageDuration(1500)
                    ->searchable(query: function(Builder $query, string $search): Builder {
                        return $query->where('nama_sales', 'like', '%' . $search . '%')
                            ->orWhere('no_hp_sales', 'like', '%' . $search . '%');
                    })
                    ->label('Nama Sales')
                    ->description(fn(Supplier $record) => "(No. HP:{$record->no_hp_sales})"),
                Tables\Columns\TextColumn::make('metode_pembayaran')
                    ->label('Metode Pembayaran')
                    ->getStateUsing(function(Supplier $record) {
                        /**
                         * new HtmlString -> <ul class="list-disc list-inside"><li>...</li><li>...</li></ul>
                         */
                        return new HtmlString('<ul class="list-disc list-inside"><li>' . $record->metode_pembayaran1 . '</li><li>' . $record->metode_pembayaran2 . '</li></ul>');
                    }),
                Tables\Columns\ToggleColumn::make('is_active')
                    ->label('Status Keaktifan'),
                Tables\Columns\TextColumn::make('is_po')
                    ->badge()
                    ->label('Kategori Supplier')
                    ->color(fn(Supplier $record) => $record->is_po ? 'warning' : 'info')
                    ->getStateUsing(fn(Supplier $record) => $record->is_po ? 'PO' : 'Non PO'),
                Tables\Columns\TextColumn::make('alamat_perusahaan')
                    ->label('Alamat Perusahaan')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('alamat_gudang')
                    ->label('Alamat Gudang')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('metode_pembayaran1')
                    ->label('Metode Pembayaran 1')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('nomor_rekening1')
                    ->label('Nomor Rekening 1')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('nama_rekening1')
                    ->label('Nama Rekening 1')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('metode_pembayaran2')
                    ->label('Metode Pembayaran 2')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('nomor_rekening2')
                    ->label('Nomor Rekening 2')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('nama_rekening2')
                    ->label('Nama Rekening 2')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_pkp')
                    ->label('Status PKP')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('npwp')
                    ->label('NPWP')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Tables\Filters\SelectFilter::make('is_active')
                    ->label('Status Keaktifan')
                    ->options([
                        true => 'Aktif',
                        false => 'Tidak Aktif',
                    ]),
                Tables\Filters\SelectFilter::make('is_po')
                    ->label('Kategori Supplier')
                    ->options([
                        true => 'PO',
                        false => 'Non PO',
                    ]),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn () => Auth::user()->can('update_supplier')),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => Auth::user()->can('delete_supplier')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()->can('delete_any_supplier')),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSuppliers::route('/'),
        ];
    }
}
