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
                            ->defaultItems(0)
                            ->schema([
                                Forms\Components\Hidden::make('produk_proses_kategori_id')
                                    ->default(1), // Design
                                Forms\Components\Hidden::make('urutan')
                                    ->default(0), // Design selalu di awal (urutan 0)
                                Forms\Components\Hidden::make('proses_id'),
                                Forms\Components\Select::make('nama')
                                    ->label('Nama Design')
                                    ->required()
                                    ->searchable()
                                    ->getSearchResultsUsing(function (string $search) {
                                        return \App\Models\Proses::where('produk_proses_kategori_id', 1)
                                            ->where('nama', 'like', "%{$search}%")
                                            ->limit(50)
                                            ->get()
                                            ->mapWithKeys(fn ($proses) => [
                                                $proses->nama => $proses->nama . ($proses->harga_default ? ' (Default: ' . formatRupiah($proses->harga_default) . ')' : '')
                                            ]);
                                    })
                                    ->getOptionLabelUsing(fn ($value) => $value)
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set, $state) {
                                        // Cari proses master dan isi harga default jika ada
                                        $proses = \App\Models\Proses::where('produk_proses_kategori_id', 1)
                                            ->where('nama', $state)
                                            ->first();
                                        if ($proses) {
                                            $set('proses_id', $proses->id);
                                            if ($proses->harga_default) {
                                                $set('harga', $proses->harga_default);
                                            }
                                        } else {
                                            $set('proses_id', null);
                                        }
                                    })
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('nama')
                                            ->label('Nama Design Baru')
                                            ->required()
                                            ->maxLength(255)
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
                                            ->default(false)
                                            ->grouped()
                                            ->live()
                                            ->columnSpanFull(),
                                        Forms\Components\TextInput::make('harga_default')
                                            ->label('Harga Default')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->minValue(0)
                                            ->mask(RawJs::make('$money($input)'))
                                            ->stripCharacters(',')
                                            ->helperText('Harga default yang akan otomatis terisi saat memilih proses ini')
                                            ->required(fn (Forms\Get $get): bool => (bool) $get('perlu_harga'))
                                            ->visible(fn (Forms\Get $get): bool => (bool) $get('perlu_harga'))
                                            ->columnSpanFull(),
                                    ])
                                    ->createOptionUsing(function (array $data) {
                                        // Cek apakah proses dengan nama yang sama sudah ada
                                        $existingProses = \App\Models\Proses::where('nama', $data['nama'])
                                            ->where('produk_proses_kategori_id', 1)
                                            ->first();
                                        
                                        if ($existingProses) {
                                            // Jika sudah ada, update harga_default jika berbeda
                                            if (isset($data['harga_default']) && $existingProses->harga_default != $data['harga_default']) {
                                                $existingProses->update(['harga_default' => $data['harga_default']]);
                                            }
                                            return $existingProses->nama;
                                        }
                                        
                                        // Jika belum ada, buat baru
                                        $proses = \App\Models\Proses::create([
                                            'nama' => $data['nama'],
                                            'produk_proses_kategori_id' => 1, // Design
                                            'harga_default' => $data['harga_default'] ?? null,
                                        ]);
                                        return $proses->nama;
                                    })
                                    ->placeholder('Ketik untuk mencari atau buat baru...')
                                    ->helperText('Pilih dari daftar yang ada atau ketik nama baru untuk membuat proses baru')
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
                                    ->helperText('Harga bisa di-custom sesuai kebutuhan produk ini')
                                    ->columnSpanFull(),
                            ])
                            ->itemLabel(fn (array $state): ?string => $state['nama'] ?? 'Design Baru')
                            ->helperText('Jika produk tidak memerlukan opsi design, kosongkan saja bagian ini.')
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
                                Forms\Components\Hidden::make('proses_id'),
                                Forms\Components\Select::make('nama')
                                    ->label('Nama Proses')
                                    ->required()
                                    ->searchable()
                                    ->getSearchResultsUsing(function (string $search) {
                                        return \App\Models\Proses::where('produk_proses_kategori_id', 2)
                                            ->where('nama', 'like', "%{$search}%")
                                            ->limit(50)
                                            ->get()
                                            ->mapWithKeys(fn ($proses) => [
                                                $proses->nama => $proses->nama
                                            ]);
                                    })
                                    ->getOptionLabelUsing(fn ($value) => $value)
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set, $state) {
                                        // Cari proses master dan link-kan
                                        $proses = \App\Models\Proses::where('produk_proses_kategori_id', 2)
                                            ->where('nama', $state)
                                            ->first();
                                        if ($proses) {
                                            $set('proses_id', $proses->id);
                                        } else {
                                            $set('proses_id', null);
                                        }
                                    })
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('nama')
                                            ->label('Nama Proses Baru')
                                            ->required()
                                            ->maxLength(255)
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
                                            ->default(false)
                                            ->grouped()
                                            ->live()
                                            ->helperText('Biasanya proses produksi tidak perlu harga default')
                                            ->columnSpanFull(),
                                        Forms\Components\TextInput::make('harga_default')
                                            ->label('Harga Default')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->minValue(0)
                                            ->mask(RawJs::make('$money($input)'))
                                            ->stripCharacters(',')
                                            ->helperText('Harga default yang akan otomatis terisi saat memilih proses ini')
                                            ->required(fn (Forms\Get $get): bool => (bool) $get('perlu_harga'))
                                            ->visible(fn (Forms\Get $get): bool => (bool) $get('perlu_harga'))
                                            ->columnSpanFull(),
                                    ])
                                    ->createOptionUsing(function (array $data) {
                                        // Cek apakah proses dengan nama yang sama sudah ada
                                        $existingProses = \App\Models\Proses::where('nama', $data['nama'])
                                            ->where('produk_proses_kategori_id', 2)
                                            ->first();
                                        
                                        if ($existingProses) {
                                            // Jika sudah ada, update harga_default jika berbeda
                                            if (isset($data['harga_default']) && $existingProses->harga_default != $data['harga_default']) {
                                                $existingProses->update(['harga_default' => $data['harga_default']]);
                                            }
                                            return $existingProses->nama;
                                        }
                                        
                                        // Jika belum ada, buat baru
                                        $proses = \App\Models\Proses::create([
                                            'nama' => $data['nama'],
                                            'produk_proses_kategori_id' => 2, // Produksi
                                            'harga_default' => $data['harga_default'] ?? null,
                                        ]);
                                        return $proses->nama;
                                    })
                                    ->placeholder('Ketik untuk mencari atau buat baru...')
                                    ->helperText('Pilih dari daftar yang ada atau ketik nama baru')
                                    ->columnSpanFull(),
                                Forms\Components\Select::make('mesin_id')
                                    ->label('Mesin')
                                    ->relationship('mesin', 'nama')
                                    ->searchable()
                                    ->preload()
                                    ->required()
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
                            ->defaultItems(0)
                            ->schema([
                                Forms\Components\Hidden::make('produk_proses_kategori_id')
                                    ->default(2), // Finishing
                                Forms\Components\Hidden::make('proses_id'),
                                Forms\Components\Select::make('nama')
                                    ->label('Nama Addon')
                                    ->required()
                                    ->searchable()
                                    ->getSearchResultsUsing(function (string $search) {
                                        return \App\Models\Proses::where('produk_proses_kategori_id', 3)
                                            ->where('nama', 'like', "%{$search}%")
                                            ->limit(50)
                                            ->get()
                                            ->mapWithKeys(fn ($proses) => [
                                                $proses->nama => $proses->nama . ($proses->harga_default ? ' (Default: ' . formatRupiah($proses->harga_default) . ')' : '')
                                            ]);
                                    })
                                    ->getOptionLabelUsing(fn ($value) => $value)
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set, $state) {
                                        // Cari proses master dan isi harga default jika ada
                                        $proses = \App\Models\Proses::where('produk_proses_kategori_id', 3)
                                            ->where('nama', $state)
                                            ->first();
                                        if ($proses) {
                                            $set('proses_id', $proses->id);
                                            if ($proses->harga_default) {
                                                $set('harga', $proses->harga_default);
                                            }
                                        } else {
                                            $set('proses_id', null);
                                        }
                                    })
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('nama')
                                            ->label('Nama Addon Baru')
                                            ->required()
                                            ->maxLength(255)
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
                                            ->default(false)
                                            ->grouped()
                                            ->live()
                                            ->columnSpanFull(),
                                        Forms\Components\TextInput::make('harga_default')
                                            ->label('Harga Default')
                                            ->numeric()
                                            ->prefix('Rp')
                                            ->minValue(0)
                                            ->mask(RawJs::make('$money($input)'))
                                            ->stripCharacters(',')
                                            ->helperText('Harga default yang akan otomatis terisi saat memilih proses ini')
                                            ->required(fn (Forms\Get $get): bool => (bool) $get('perlu_harga'))
                                            ->visible(fn (Forms\Get $get): bool => (bool) $get('perlu_harga'))
                                            ->columnSpanFull(),
                                    ])
                                    ->createOptionUsing(function (array $data) {
                                        // Cek apakah proses dengan nama yang sama sudah ada
                                        $existingProses = \App\Models\Proses::where('nama', $data['nama'])
                                            ->where('produk_proses_kategori_id', 3)
                                            ->first();
                                        
                                        if ($existingProses) {
                                            // Jika sudah ada, update harga_default jika berbeda
                                            if (isset($data['harga_default']) && $existingProses->harga_default != $data['harga_default']) {
                                                $existingProses->update(['harga_default' => $data['harga_default']]);
                                            }
                                            return $existingProses->nama;
                                        }
                                        
                                        // Jika belum ada, buat baru
                                        $proses = \App\Models\Proses::create([
                                            'nama' => $data['nama'],
                                            'produk_proses_kategori_id' => 3, // Finishing
                                            'harga_default' => $data['harga_default'] ?? null,
                                        ]);
                                        return $proses->nama;
                                    })
                                    ->placeholder('Ketik untuk mencari atau buat baru...')
                                    ->helperText('Pilih dari daftar yang ada atau ketik nama baru')
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
                                    ->helperText('Harga bisa di-custom sesuai kebutuhan produk ini')
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
                            ->helperText('Jika produk tidak memerlukan finishing/addon, kosongkan saja bagian ini.')
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
                        ->schema(function ($record) {
                            $customerKategoris = CustomerKategori::all();
                            $sections = [];
                            
                            foreach ($customerKategoris as $kategori) {
                                $kategoriId = $kategori->id;
                                $sections[] = Forms\Components\Section::make($kategori->nama)
                                    ->description('Set harga tiering untuk kategori ' . $kategori->nama)
                                    ->schema([
                                        Forms\Components\Repeater::make("hargaKategori_{$kategoriId}")
                                            ->label('Tiering Harga')
                                            ->relationship(
                                                'produkHargas',
                                                modifyQueryUsing: fn ($query) => $query->where('customer_kategori_id', $kategoriId)
                                            )
                                            ->defaultItems(1)
                                            ->minItems(1)
                                            ->reorderable(false)
                                            ->schema([
                                                Forms\Components\Hidden::make('customer_kategori_id')
                                                    ->default($kategoriId)
                                                    ->required()
                                                    ->dehydrated(),
                                                Forms\Components\TextInput::make('jumlah_pesanan_minimal')
                                                    ->label('Jumlah Pesanan Minimal')
                                                    ->numeric()
                                                    ->required()
                                                    ->minValue(1)
                                                    ->default(1)
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                                        // Validasi: minimal harus <= maksimal
                                                        $maksimal = $get('jumlah_pesanan_maksimal');
                                                        if ($maksimal && $state > $maksimal) {
                                                            $set('jumlah_pesanan_maksimal', $state);
                                                        }
                                                    })
                                                    ->columnSpan(1),
                                                Forms\Components\TextInput::make('jumlah_pesanan_maksimal')
                                                    ->label('Jumlah Pesanan Maksimal')
                                                    ->numeric()
                                                    ->required()
                                                    ->minValue(1)
                                                    ->default(1)
                                                    ->live(onBlur: true)
                                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                                        // Validasi: maksimal harus >= minimal
                                                        $minimal = $get('jumlah_pesanan_minimal');
                                                        if ($minimal && $state < $minimal) {
                                                            $set('jumlah_pesanan_minimal', $state);
                                                        }
                                                    })
                                                    ->columnSpan(1),
                                                Forms\Components\TextInput::make('harga')
                                                    ->label('Harga')
                                                    ->required()
                                                    ->numeric()
                                                    ->prefix('Rp')
                                                    ->minValue(0)
                                                    ->default(0)
                                                    ->mask(RawJs::make('$money($input)'))
                                                    ->stripCharacters([',', '.'])
                                                    ->columnSpan(1),
                                            ])
                                            ->columns(3)
                                            ->itemLabel(fn (array $state): ?string => 
                                                isset($state['jumlah_pesanan_minimal']) && isset($state['jumlah_pesanan_maksimal'])
                                                    ? "{$state['jumlah_pesanan_minimal']} - {$state['jumlah_pesanan_maksimal']} pcs"
                                                    : 'Tier Baru'
                                            )
                                            ->mutateRelationshipDataBeforeCreateUsing(function (array $data) use ($kategoriId) {
                                                $data['customer_kategori_id'] = $kategoriId;
                                                return $data;
                                            })
                                            ->mutateRelationshipDataBeforeSaveUsing(function (array $data) use ($kategoriId) {
                                                $data['customer_kategori_id'] = $kategoriId;
                                                return $data;
                                            }),
                                    ])
                                    ->collapsible()
                                    ->collapsed(false);
                            }
                            
                            return $sections;
                        })
                        ->columnSpanFull(),
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
                ->getStateUsing(function (Produk $record) use ($kategori) {
                    // Use eager loaded relationship to avoid N+1 queries
                    $produkHargas = $record->produkHargas
                        ->where('customer_kategori_id', $kategori->id)
                        ->sortBy('jumlah_pesanan_minimal');
                    
                    if ($produkHargas->isEmpty()) {
                        return '-';
                    }
                    
                    // Tampilkan informasi tiering
                    $tiers = $produkHargas->map(function ($harga) {
                        $min = number_format($harga->jumlah_pesanan_minimal, 0, ',', '.');
                        $max = $harga->jumlah_pesanan_maksimal ? number_format($harga->jumlah_pesanan_maksimal, 0, ',', '.') : '∞';
                        $hargaFormatted = 'Rp ' . number_format($harga->harga, 0, ',', '.');
                        return "{$min}-{$max}: {$hargaFormatted}";
                    })->implode('<br>');
                    
                    return new \Illuminate\Support\HtmlString($tiers);
                })
                ->html()
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
