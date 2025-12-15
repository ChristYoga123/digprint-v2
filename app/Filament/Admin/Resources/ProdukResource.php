<?php

namespace App\Filament\Admin\Resources;

use Filament\Forms;
use Filament\Tables;
use App\Models\Produk;
use App\Models\Bahan;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\CustomerKategori;
use Filament\Resources\Resource;
use Filament\Support\RawJs;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Admin\Resources\ProdukResource\Pages;
use App\Filament\Admin\Resources\ProdukResource\RelationManagers;

class ProdukResource extends Resource
{
    protected static ?string $model = Produk::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationLabel = 'Produk';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make('Informasi Produk')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            Forms\Components\TextInput::make('kode')
                                ->label('Kode Produk')
                                ->helperText('Otomatis terisi tetapi bisa di-custom')
                                ->required()
                                ->maxLength(255)
                                ->default(fn ($record) => $record?->kode ?? generateKode('PRD'))
                                ->unique(ignoreRecord: true)
                                ->columnSpanFull(),
                            Forms\Components\TextInput::make('nama')
                                ->label('Nama Produk')
                                ->required()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true)
                                ->columnSpanFull(),
                            Forms\Components\ToggleButtons::make('apakah_perlu_custom_dimensi')
                                ->label('Apakah bisa custom dimensi?')
                                ->options([
                                    true => 'Ya',
                                    false => 'Tidak',
                                ])
                                ->colors([
                                    true => 'success',
                                    false => 'danger',
                                ])
                                ->default(false)
                                ->grouped()
                                ->required()
                                ->live()
                                ->columnSpanFull(),
                            Forms\Components\ToggleButtons::make('apakah_perlu_proses')
                                ->label('Apakah perlu proses?')
                                ->options([
                                    true => 'Ya',
                                    false => 'Tidak',
                                ])
                                ->colors([
                                    true => 'success',
                                    false => 'danger',
                                ])
                                ->default(false)
                                ->grouped()
                                ->required()
                                ->live()
                                ->helperText('Aktifkan jika produk ini memerlukan proses produksi atau finishing')
                                ->afterStateHydrated(function (Forms\Set $set, $state, $record) {
                                    // Jika edit dan ada ProdukProses, set toggle ke true
                                    if ($record && $record->id) {
                                        $hasProses = \App\Models\ProdukProses::where('produk_id', $record->id)->exists();
                                        if ($hasProses) {
                                            $set('apakah_perlu_proses', true);
                                        }
                                    }
                                })
                                ->columnSpanFull(),
                        ]),
                    Forms\Components\Wizard\Step::make('Pra Produksi (Design)')
                        ->icon('heroicon-o-paint-brush')
                        ->visible(fn (Forms\Get $get): bool => (bool) $get('apakah_perlu_proses'))
                        ->schema([
                            Forms\Components\Section::make('Pilihan Design')
                                ->description('Tentukan pilihan design yang tersedia untuk produk ini (Design berada di awal proses transaksi)')
                                ->visible(fn (Forms\Get $get): bool => (bool) $get('apakah_perlu_proses'))
                                ->schema([
                        Forms\Components\Repeater::make('produkProsesDesign')
                            ->label('Opsi Design')
                            ->relationship('produkProses', function ($query) {
                                return $query->where('produk_proses_kategori_id', 1); // Design
                            })
                            ->schema([
                                Forms\Components\Hidden::make('produk_proses_kategori_id')
                                    ->default(1), // Design
                                Forms\Components\Hidden::make('urutan')
                                    ->default(0), // Design selalu di awal (urutan 0)
                                Forms\Components\TextInput::make('nama')
                                    ->label('Nama Design')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Contoh: Design Simple, Design Premium, dll')
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('harga')
                                    ->label('Harga Design')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->minValue(0)
                                    ->default(0)
                                    ->required()
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->columnSpanFull(),
                            ])
                            ->itemLabel(fn (array $state): ?string => $state['nama'] ?? 'Design Baru')
                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                $data['produk_proses_kategori_id'] = 1; // Design
                                $data['urutan'] = 0; // Design selalu di awal
                                return $data;
                            })
                            ->mutateRelationshipDataBeforeSaveUsing(function (array $data): array {
                                $data['produk_proses_kategori_id'] = 1; // Design
                                $data['urutan'] = 0; // Design selalu di awal
                                return $data;
                            }),
                        ])
                        ->columnSpanFull(),
                    ]),
                    Forms\Components\Wizard\Step::make('Proses Produksi')
                        ->icon('heroicon-o-cog-6-tooth')
                        ->visible(fn (Forms\Get $get): bool => (bool) $get('apakah_perlu_proses'))
                        ->schema([
                            Forms\Components\Section::make('Proses Produksi')
                                ->description('Tentukan proses produksi yang diperlukan untuk membuat produk ini')
                                ->visible(fn (Forms\Get $get): bool => (bool) $get('apakah_perlu_proses'))
                                ->schema([
                        Forms\Components\Repeater::make('produkProsesProduksi')
                            ->label('Proses Produksi')
                            ->relationship('produkProses', function ($query) {
                                return $query->where('produk_proses_kategori_id', 2); // Produksi
                            })
                            ->reorderable()
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                // Update urutan semua item berdasarkan posisi mereka di array setelah reorder
                                $items = $get('produkProsesProduksi') ?? [];
                                if (!is_array($items) || empty($items)) {
                                    return;
                                }
                                $urutan = 1;
                                $updatedItems = [];
                                foreach ($items as $key => $item) {
                                    $item['urutan'] = $urutan;
                                    $updatedItems[$key] = $item;
                                    
                                    // Update existing record di database jika sudah ada ID
                                    if (isset($item['id']) && is_numeric($item['id'])) {
                                        \App\Models\ProdukProses::where('id', $item['id'])
                                            ->update(['urutan' => $urutan]);
                                    }
                                    $urutan++;
                                }
                                // Update state dengan urutan yang baru
                                $set('produkProsesProduksi', $updatedItems);
                            })
                            ->schema([
                                Forms\Components\Hidden::make('produk_proses_kategori_id')
                                    ->default(1), // Produksi
                                Forms\Components\Hidden::make('urutan')
                                    ->default(1),
                                Forms\Components\TextInput::make('nama')
                                    ->label('Nama Proses')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                Forms\Components\Select::make('mesin_id')
                                    ->label('Mesin')
                                    ->relationship('mesin', 'nama')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->columnSpanFull(),
                                Forms\Components\Repeater::make('produkProsesBahans')
                                    ->label('Set Bahan yang Dipakai')
                                    ->relationship('produkProsesBahans')
                                    ->defaultItems(function (Forms\Get $get) {
                                        // Cek apakah ada item dengan toggle aktif
                                        $items = $get('produkProsesBahans') ?? [];
                                        if (is_array($items)) {
                                            foreach ($items as $item) {
                                                if (isset($item['apakah_dipengaruhi_oleh_dimensi']) && $item['apakah_dipengaruhi_oleh_dimensi']) {
                                                    return 1;
                                                }
                                            }
                                        }
                                        return 0;
                                    })
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                        // Jika ada item dengan toggle aktif, pastikan minimal 1 item
                                        if (is_array($state)) {
                                            foreach ($state as $item) {
                                                if (isset($item['apakah_dipengaruhi_oleh_dimensi']) && $item['apakah_dipengaruhi_oleh_dimensi']) {
                                                    if (empty($state)) {
                                                        $set('produkProsesBahans', [[]]);
                                                    }
                                                    return;
                                                }
                                            }
                                        }
                                    })
                                    ->schema([
                                        Forms\Components\Select::make('bahan_id')
                                            ->label('Bahan')
                                            ->options(function () {
                                                return \App\Models\Bahan::query()
                                                    ->get()
                                                    ->mapWithKeys(function ($bahan) {
                                                        return [$bahan->id => "{$bahan->kode} - {$bahan->nama}"];
                                                    });
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->columnSpanFull(),
                                        Forms\Components\ToggleButtons::make('apakah_dipengaruhi_oleh_dimensi')
                                            ->label('Apakah dipengaruhi oleh custom dimensi?')
                                            ->options([
                                                true => 'Ya',
                                                false => 'Tidak',
                                            ])
                                            ->colors([
                                                true => 'success',
                                                false => 'danger',
                                            ])
                                            ->default(false)
                                            ->grouped()
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                                // Jika toggle aktif, kosongkan jumlah
                                                if ($state) {
                                                    $set('jumlah', 0);
                                                    
                                                    // Pastikan repeater memiliki minimal 1 item
                                                    $items = $get('produkProsesBahans') ?? [];
                                                    if (empty($items) || count($items) === 0) {
                                                        $set('produkProsesBahans', [[]]);
                                                    }
                                                }
                                            })
                                            ->columnSpanFull(),
                                        Forms\Components\TextInput::make('jumlah')
                                            ->label('Jumlah Bahan yang dipakai')
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(0)
                                            ->required(fn (Forms\Get $get): bool => !$get('apakah_dipengaruhi_oleh_dimensi'))
                                            ->visible(fn (Forms\Get $get): bool => !$get('apakah_dipengaruhi_oleh_dimensi'))
                                            ->hidden(fn (Forms\Get $get): bool => $get('apakah_dipengaruhi_oleh_dimensi'))
                                            ->mask(RawJs::make('$money($input)'))
                                            ->stripCharacters(',')
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpanFull()
                                    ->itemLabel(fn (array $state): ?string => 
                                        isset($state['bahan_id']) 
                                            ? Bahan::find($state['bahan_id'])?->nama 
                                            : null
                                    ),
                            ])
                            ->itemLabel(fn (array $state): ?string => $state['nama'] ?? 'Proses Baru')
                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data, $state, $get): array {
                                $data['produk_proses_kategori_id'] = 2; // Produksi
                                // Mengisi urutan dari state jika sudah ada, jika tidak gunakan posisi di array
                                if (isset($data['urutan']) && is_numeric($data['urutan'])) {
                                    $data['urutan'] = (int) $data['urutan'];
                                } else {
                                    $items = $get('produkProsesProduksi') ?? [];
                                    if (!is_array($items)) {
                                        $items = [];
                                    }
                                    // Gunakan urutan dari posisi array
                                    $currentIndex = count($items);
                                    $data['urutan'] = $currentIndex + 1;
                                }
                                return $data;
                            })
                            ->mutateRelationshipDataBeforeSaveUsing(function (array $data, $state, $get): array {
                                $data['produk_proses_kategori_id'] = 2; // Produksi
                                // Update urutan dari state jika sudah ada, jika tidak gunakan posisi di array
                                if (isset($data['urutan']) && is_numeric($data['urutan'])) {
                                    $data['urutan'] = (int) $data['urutan'];
                                } else {
                                    $items = $get('produkProsesProduksi') ?? [];
                                    if (!is_array($items)) {
                                        $items = [];
                                    }
                                    // Cari posisi item ini di array berdasarkan ID
                                    $currentIndex = 0;
                                    $found = false;
                                    foreach ($items as $index => $item) {
                                        if (isset($item['id']) && isset($data['id']) && $item['id'] == $data['id']) {
                                            $currentIndex = (int) $index;
                                            $found = true;
                                            break;
                                        }
                                    }
                                    // Jika tidak ditemukan, gunakan urutan yang sudah ada atau count
                                    if (!$found) {
                                        $currentIndex = isset($data['urutan']) ? (int) $data['urutan'] - 1 : count($items);
                                    }
                                    $data['urutan'] = $currentIndex + 1;
                                }
                                return $data;
                            }),
                        ])
                        ->columnSpanFull(),
                    ]),
                    Forms\Components\Wizard\Step::make('Finishing/Addon')
                        ->icon('heroicon-o-sparkles')
                        ->visible(fn (Forms\Get $get): bool => (bool) $get('apakah_perlu_proses'))
                        ->schema([
                            Forms\Components\Section::make('Finishing/Addon')
                                ->description('Tentukan proses finishing atau addon yang tersedia untuk produk ini')
                                ->visible(fn (Forms\Get $get): bool => (bool) $get('apakah_perlu_proses'))
                                ->schema([
                        Forms\Components\Repeater::make('produkProsesAddon')
                            ->label('Proses Finishing/Addon')
                            ->relationship('produkProses', function ($query) {
                                return $query->where('produk_proses_kategori_id', 3); // Finishing
                            })
                            ->schema([
                                Forms\Components\Hidden::make('produk_proses_kategori_id')
                                    ->default(2), // Finishing
                                Forms\Components\TextInput::make('nama')
                                    ->label('Nama Addon')
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('harga')
                                    ->label('Harga Addon')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->minValue(0)
                                    ->default(0)
                                    ->required()
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->columnSpanFull(),
                                Forms\Components\Select::make('mesin_id')
                                    ->label('Mesin')
                                    ->relationship('mesin', 'nama')
                                    ->searchable()
                                    ->preload()
                                    ->nullable()
                                    ->helperText('Kosongkan jika proses manual')
                                    ->columnSpanFull(),
                                Forms\Components\ToggleButtons::make('apakah_mengurangi_bahan')
                                    ->label('Apakah mengurangi bahan?')
                                    ->options([
                                        true => 'Ya',
                                        false => 'Tidak',
                                    ])
                                    ->colors([
                                        true => 'success',
                                        false => 'danger',
                                    ])
                                    ->default(false)
                                    ->grouped()
                                    ->required()
                                    ->live()
                                    ->columnSpanFull(),
                                Forms\Components\Repeater::make('produkProsesBahans')
                                    ->label('Set Bahan yang Dipakai')
                                    ->relationship('produkProsesBahans')
                                    ->visible(fn (Forms\Get $get): bool => $get('apakah_mengurangi_bahan'))
                                    ->required(fn (Forms\Get $get): bool => $get('apakah_mengurangi_bahan'))
                                    ->defaultItems(function (Forms\Get $get) {
                                        // Jika apakah_mengurangi_bahan aktif, set defaultItems menjadi 1
                                        return $get('apakah_mengurangi_bahan') ? 1 : 0;
                                    })
                                    ->schema([
                                        Forms\Components\Select::make('bahan_id')
                                            ->label('Bahan')
                                            ->options(function () {
                                                return \App\Models\Bahan::query()
                                                    ->get()
                                                    ->mapWithKeys(function ($bahan) {
                                                        return [$bahan->id => "{$bahan->kode} - {$bahan->nama}"];
                                                    });
                                            })
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->columnSpanFull(),
                                        Forms\Components\ToggleButtons::make('apakah_dipengaruhi_oleh_dimensi')
                                            ->label('Apakah dipengaruhi oleh custom dimensi?')
                                            ->options([
                                                true => 'Ya',
                                                false => 'Tidak',
                                            ])
                                            ->colors([
                                                true => 'success',
                                                false => 'danger',
                                            ])
                                            ->default(false)
                                            ->grouped()
                                            ->required()
                                            ->live()
                                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                                // Jika toggle aktif, kosongkan jumlah
                                                if ($state) {
                                                    $set('jumlah', 0);
                                                    
                                                    // Pastikan repeater memiliki minimal 1 item
                                                    $items = $get('produkProsesBahans') ?? [];
                                                    if (empty($items) || count($items) === 0) {
                                                        $set('produkProsesBahans', [[]]);
                                                    }
                                                }
                                            })
                                            ->columnSpanFull(),
                                        Forms\Components\TextInput::make('jumlah')
                                            ->label('Jumlah Bahan yang dipakai')
                                            ->numeric()
                                            ->minValue(0)
                                            ->default(0)
                                            ->required(fn (Forms\Get $get): bool => !$get('apakah_dipengaruhi_oleh_dimensi'))
                                            ->visible(fn (Forms\Get $get): bool => !$get('apakah_dipengaruhi_oleh_dimensi'))
                                            ->hidden(fn (Forms\Get $get): bool => $get('apakah_dipengaruhi_oleh_dimensi'))
                                            ->mask(RawJs::make('$money($input)'))
                                            ->stripCharacters(',')
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpanFull()
                                    ->itemLabel(fn (array $state): ?string => 
                                        isset($state['bahan_id']) 
                                            ? Bahan::find($state['bahan_id'])?->nama 
                                            : null
                                    ),
                            ])
                            ->itemLabel(fn (array $state): ?string => $state['nama'] ?? 'Addon Baru')
                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                $data['produk_proses_kategori_id'] = 3; // Finishing
                                return $data;
                            })
                            ->mutateRelationshipDataBeforeSaveUsing(function (array $data): array {
                                $data['produk_proses_kategori_id'] = 3; // Finishing
                                return $data;
                            }),
                        ])
                        ->columnSpanFull(),
                    ]),
                    Forms\Components\Wizard\Step::make('Harga')
                        ->icon('heroicon-o-currency-dollar')
                        ->schema([
                            Forms\Components\Repeater::make('produkHargas')
                                ->label('Set Harga')
                                ->required()
                                ->addable(false)
                                ->deletable(false)
                                ->relationship('produkHargas')
                            ->defaultItems(CustomerKategori::query()->count())
                            ->default(function () {
                                $customerKategoris = CustomerKategori::all();
                                return $customerKategoris->map(function ($kategori) {
                                    return [
                                        'customer_kategori_id' => $kategori->id,
                                    ];
                                })->toArray();
                            })
                            ->schema([
                                Forms\Components\Hidden::make('customer_kategori_id')
                                    ->required(),
                                Forms\Components\TextInput::make('customer_kategori_nama')
                                    ->label('Kategori Pelanggan')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->afterStateHydrated(function (Forms\Components\TextInput $component, $state, $record, $get) {
                                        if ($record && $record->customerKategori) {
                                            $component->state($record->customerKategori->nama);
                                        } else {
                                            $kategoriId = $get('customer_kategori_id');
                                            if ($kategoriId) {
                                                $kategori = CustomerKategori::find($kategoriId);
                                                if ($kategori) {
                                                    $component->state($kategori->nama);
                                                }
                                            }
                                        }
                                    }),
                                Forms\Components\TextInput::make('harga')
                                    ->label('Harga')
                                    ->required()
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->minValue(0)
                                    ->default(0)
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(','),
                            ])
                            ->columns(3)
                            ->itemLabel(fn (array $state): ?string => 
                                isset($state['customer_kategori_id']) 
                                    ? CustomerKategori::find($state['customer_kategori_id'])?->nama 
                                    : null
                            ),
                        ]),
                ])
                ->skippable(fn(string $operation) => $operation === 'edit')
                ->submitAction(Forms\Components\Actions\Action::make('submit')
                    ->label('Simpan Produk')
                    ->submit('submit'))
                ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        // Get all customer categories to create dynamic columns
        $customerKategoris = CustomerKategori::all();
        
        // Create dynamic price columns for each category using spread operator
        $priceColumns = $customerKategoris->map(function ($kategori) {
            return Tables\Columns\TextColumn::make("harga_{$kategori->id}")
                ->label($kategori->nama)
                ->money('IDR')
                ->getStateUsing(function (Produk $record) use ($kategori) {
                    // Use eager loaded relationship to avoid N+1 queries
                    $produkHarga = $record->produkHargas
                        ->where('customer_kategori_id', $kategori->id)
                        ->first();
                    return $produkHarga?->harga ?? 0;
                })
                ->sortable(false)
                ->searchable(false);
        })->toArray();

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('produkHargas.customerKategori'))
            ->columns([
                Tables\Columns\TextColumn::make('kode')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nama')
                    ->searchable(),
                ...$priceColumns, // Spread operator untuk menambahkan kolom harga dinamis
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
            'index' => Pages\ManageProduks::route('/'),
        ];
    }
}
