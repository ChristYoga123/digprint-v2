<?php

namespace App\Filament\Admin\Resources;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use App\Models\Produk;
use App\Models\ProdukProses;
use App\Models\Customer;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\ProdukHarga;
use Filament\Support\RawJs;
use Illuminate\Support\HtmlString;
use App\Models\CustomerKategori;
use Filament\Resources\Resource;
use App\Models\TransaksiKalkulasi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Admin\Resources\DeskprintResource\Pages\ManageDeskprints;

class DeskprintResource extends Resource
{
    protected static ?string $model = TransaksiKalkulasi::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $navigationLabel = 'Deskprint';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make('Kalkulasi')
                        ->schema([
                            Forms\Components\TextInput::make('kode')
                                ->label('Kode Kalkulasi')
                                ->required()
                                ->maxLength(255)
                                ->helperText('Otomatis terisi tetapi bisa di-custom')
                                ->default(fn ($record) => $record?->kode ?? generateKode('KAL')),
                            Forms\Components\Repeater::make('produks')
                                ->label('Produk')
                                ->relationship('transaksiKalkulasiProduks')
                                ->live()
                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                    // Hitung ulang total_harga_produk untuk setiap item saat ada perubahan
                                    if (!is_array($state)) {
                                        return;
                                    }
                                    
                                    $customerId = $get('../../customer_id') ?? $get('customer_id');
                                    if (!$customerId) {
                                        return;
                                    }
                                    
                                    foreach ($state as $index => $produk) {
                                        if (!isset($produk['produk_id'])) {
                                            continue;
                                        }
                                        
                                        // Hitung total_harga_produk menggunakan calculateTotalHargaProduk
                                        $calculatedData = static::calculateTotalHargaProduk($produk, $customerId);
                                        if (isset($calculatedData['total_harga_produk'])) {
                                            $set("produks.{$index}.total_harga_produk", $calculatedData['total_harga_produk']);
                                        }
                                    }
                                })
                                ->schema([
                                    Forms\Components\TextInput::make('judul_pesanan')
                                        ->label('Judul Pesanan')
                                        ->required(),
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\Select::make('produk_id')
                                                ->label('Produk')
                                                ->options(Produk::query()->get()->mapWithKeys(function ($produk) {
                                                    return [$produk->id => "[{$produk->kode}] - {$produk->nama}"];
                                                }))
                                                ->live()
                                                ->reactive()
                                                ->searchable()
                                                ->preload()
                                                ->required()
                                                ->afterStateHydrated(function (Forms\Set $set, $state) {
                                                    // Pastikan design field ter-update saat produk_id ter-hydrate
                                                    if ($state) {
                                                        // Trigger update pada design field
                                                    }
                                                })
                                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                                    // Reset design dan addon ketika produk berubah
                                                    $set('design_id', null);
                                                    $set('addons', []);
                                                    
                                                    // Hitung ulang total_harga_produk saat produk berubah
                                                    $customerId = $get('../../../../customer_id') ?? $get('../../customer_id');
                                                    if (!$customerId) {
                                                        return;
                                                    }
                                                    
                                                    $produkData = [
                                                        'produk_id' => $get('produk_id'),
                                                        'jumlah' => $get('jumlah'),
                                                        'panjang' => $get('panjang'),
                                                        'lebar' => $get('lebar'),
                                                        'design_id' => null,
                                                        'addons' => [],
                                                    ];
                                                    
                                                    $calculatedData = static::calculateTotalHargaProduk($produkData, $customerId);
                                                    if (isset($calculatedData['total_harga_produk'])) {
                                                        $set('total_harga_produk', $calculatedData['total_harga_produk']);
                                                    }
                                                }),
                                            Forms\Components\TextInput::make('jumlah')
                                                ->label('Jumlah')
                                                ->required()
                                                ->numeric()
                                                ->minValue(1)
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                                    // Hitung ulang total_harga_produk saat jumlah berubah
                                                    $customerId = $get('../../../../customer_id') ?? $get('../../customer_id');
                                                    if (!$customerId) {
                                                        return;
                                                    }
                                                    
                                                    $produkData = [
                                                        'produk_id' => $get('produk_id'),
                                                        'jumlah' => $get('jumlah'),
                                                        'panjang' => $get('panjang'),
                                                        'lebar' => $get('lebar'),
                                                        'design_id' => $get('design_id'),
                                                        'addons' => $get('addons'),
                                                    ];
                                                    
                                                    $calculatedData = static::calculateTotalHargaProduk($produkData, $customerId);
                                                    if (isset($calculatedData['total_harga_produk'])) {
                                                        $set('total_harga_produk', $calculatedData['total_harga_produk']);
                                                    }
                                                }),
                                            Forms\Components\TextInput::make('panjang')
                                                ->label('Panjang')
                                                ->required()
                                                ->numeric()
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                                    // Hitung ulang total_harga_produk saat panjang berubah
                                                    $customerId = $get('../../../../customer_id') ?? $get('../../customer_id');
                                                    if (!$customerId) {
                                                        return;
                                                    }
                                                    
                                                    $produkData = [
                                                        'produk_id' => $get('produk_id'),
                                                        'jumlah' => $get('jumlah'),
                                                        'panjang' => $get('panjang'),
                                                        'lebar' => $get('lebar'),
                                                        'design_id' => $get('design_id'),
                                                        'addons' => $get('addons'),
                                                    ];
                                                    
                                                    $calculatedData = static::calculateTotalHargaProduk($produkData, $customerId);
                                                    if (isset($calculatedData['total_harga_produk'])) {
                                                        $set('total_harga_produk', $calculatedData['total_harga_produk']);
                                                    }
                                                })
                                                ->visible(fn (Forms\Get $get) => $get('produk_id') ? Produk::find($get('produk_id'))?->apakah_perlu_custom_dimensi : false),
                                            Forms\Components\TextInput::make('lebar')
                                                ->label('Lebar')
                                                ->required()
                                                ->numeric()
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                                    // Hitung ulang total_harga_produk saat lebar berubah
                                                    $customerId = $get('../../../../customer_id') ?? $get('../../customer_id');
                                                    if (!$customerId) {
                                                        return;
                                                    }
                                                    
                                                    $produkData = [
                                                        'produk_id' => $get('produk_id'),
                                                        'jumlah' => $get('jumlah'),
                                                        'panjang' => $get('panjang'),
                                                        'lebar' => $get('lebar'),
                                                        'design_id' => $get('design_id'),
                                                        'addons' => $get('addons'),
                                                    ];
                                                    
                                                    $calculatedData = static::calculateTotalHargaProduk($produkData, $customerId);
                                                    if (isset($calculatedData['total_harga_produk'])) {
                                                        $set('total_harga_produk', $calculatedData['total_harga_produk']);
                                                    }
                                                })
                                                ->visible(fn (Forms\Get $get) => $get('produk_id') ? Produk::find($get('produk_id'))?->apakah_perlu_custom_dimensi : false),
                                        ]),
                                    Forms\Components\Hidden::make('total_harga_produk')
                                        ->default(0)
                                        ->dehydrated(),
                                    Forms\Components\Radio::make('design_id')
                                        ->label('Pilih Design (Opsional)')
                                        ->options(function (Forms\Get $get) {
                                            $produkId = $get('produk_id');

                                            if (!$produkId) {
                                                return ['none' => 'Tidak Pakai Design (Customer Punya Design Sendiri)'];
                                            }

                                            $designs = ProdukProses::where('produk_id', $produkId)
                                                ->where('produk_proses_kategori_id', 1) // Design
                                                ->whereNotNull('harga')
                                                ->get();

                                            $options = ['none' => 'Tidak Pakai Design (Customer Punya Design Sendiri)'];

                                            foreach ($designs as $produkProses) {
                                                $options[$produkProses->id] = $produkProses->nama . ' - ' . formatRupiah($produkProses->harga);
                                            }

                                            return $options;
                                        })
                                        ->default('none')
                                        ->afterStateHydrated(function (Forms\Set $set, $state) {
                                            if (empty($state) || $state === null) {
                                                $set('design_id', 'none');
                                            }
                                        })
                                        ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                            if ($state === 'none') {
                                                $set('design_id', null);
                                            } else {
                                                // Jika memilih design, reset link_design
                                                $set('link_design', null);
                                            }
                                            
                                            // Hitung ulang total_harga_produk saat design berubah
                                            $customerId = $get('../../../../customer_id') ?? $get('../../customer_id');
                                            if (!$customerId) {
                                                return;
                                            }
                                            
                                            $produkData = [
                                                'produk_id' => $get('produk_id'),
                                                'jumlah' => $get('jumlah'),
                                                'panjang' => $get('panjang'),
                                                'lebar' => $get('lebar'),
                                                'design_id' => $state === 'none' ? null : $state,
                                                'addons' => $get('addons'),
                                            ];
                                            
                                            $calculatedData = static::calculateTotalHargaProduk($produkData, $customerId);
                                            if (isset($calculatedData['total_harga_produk'])) {
                                                $set('total_harga_produk', $calculatedData['total_harga_produk']);
                                            }
                                        })
                                        ->dehydrated(fn ($state) => $state !== 'none' && $state !== null)
                                        ->live(onBlur: false)
                                        ->visible(fn (Forms\Get $get) => !empty($get('produk_id')))
                                        ->helperText('Pilih design jika diperlukan. Pilih "Tidak Pakai Design" jika customer sudah punya design sendiri.')
                                        ->columnSpanFull(),
                                    Forms\Components\TextInput::make('link_design')
                                        ->label('Link Design')
                                        ->url()
                                        ->placeholder('https://example.com/design')
                                        ->maxLength(255)
                                        ->live(onBlur: false)
                                        ->visible(function (Forms\Get $get) {
                                            $produkId = $get('produk_id');
                                            if (empty($produkId)) {
                                                return false;
                                            }
                                            
                                            $designId = $get('design_id');
                                            // Tampilkan jika design_id adalah 'none', null, atau empty
                                            // Perlu cek dengan lebih eksplisit karena bisa jadi string 'none' atau null
                                            if ($designId === 'none' || $designId === null || $designId === '') {
                                                return true;
                                            }
                                            
                                            return false;
                                        })
                                        ->helperText('Masukkan link design jika customer sudah punya design sendiri')
                                        ->columnSpanFull(),
                                    Forms\Components\CheckboxList::make('addons')
                                        ->label('Pilih Addons')
                                        ->options(function (Forms\Get $get) {
                                            $produkId = $get('produk_id');
                                            
                                            // Jika produk belum dipilih, return empty
                                            if (!$produkId) {
                                                return [];
                                            }
                                            
                                            // Ambil addon yang terhubung dengan produk ini (ProdukProses dengan kategori Finishing)
                                            $addons = ProdukProses::where('produk_id', $produkId)
                                                ->where('produk_proses_kategori_id', 3) // Finishing/Addon
                                                ->whereNotNull('harga')
                                                ->get();
                                            
                                            // Jika tidak ada addon, return empty
                                            if ($addons->isEmpty()) {
                                                return [];
                                            }
                                            
                                            // Return addon dengan format id => nama
                                            return $addons->mapWithKeys(function ($produkProses) {
                                                return [
                                                    $produkProses->id => $produkProses->nama
                                                ];
                                            });
                                        })
                                        ->descriptions(function (Forms\Get $get) {
                                            $produkId = $get('produk_id');
                                            
                                            if (!$produkId) {
                                                return [];
                                            }
                                            
                                            $addons = ProdukProses::where('produk_id', $produkId)
                                                ->where('produk_proses_kategori_id', 3) // Finishing/Addon
                                                ->whereNotNull('harga')
                                                ->get();
                                            
                                            if ($addons->isEmpty()) {
                                                return [];
                                            }
                                            
                                            $descriptions = [];
                                            foreach ($addons as $produkProses) {
                                                $descriptions[$produkProses->id] = 'Harga: ' . formatRupiah($produkProses->harga);
                                            }
                                            return $descriptions;
                                        })
                                        ->searchable()
                                        ->columns(1)
                                        ->live()
                                        ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                            // Hitung ulang total_harga_produk saat addons berubah
                                            $customerId = $get('../../../../customer_id') ?? $get('../../customer_id');
                                            if (!$customerId) {
                                                return;
                                            }
                                            
                                            $produkData = [
                                                'produk_id' => $get('produk_id'),
                                                'jumlah' => $get('jumlah'),
                                                'panjang' => $get('panjang'),
                                                'lebar' => $get('lebar'),
                                                'design_id' => $get('design_id'),
                                                'addons' => $state,
                                            ];
                                            
                                            $calculatedData = static::calculateTotalHargaProduk($produkData, $customerId);
                                            if (isset($calculatedData['total_harga_produk'])) {
                                                $set('total_harga_produk', $calculatedData['total_harga_produk']);
                                            }
                                        })
                                        ->visible(fn (Forms\Get $get) => !empty($get('produk_id')))
                                        ->helperText(function (Forms\Get $get) {
                                            $produkId = $get('produk_id');
                                            if (!$produkId) {
                                                return 'Pilih produk terlebih dahulu untuk melihat addon yang tersedia';
                                            }
                                            
                                            $addonCount = ProdukProses::where('produk_id', $produkId)
                                                ->where('produk_proses_kategori_id', 3) // Finishing/Addon
                                                ->whereNotNull('harga')
                                                ->count();
                                            if ($addonCount === 0) {
                                                return 'Produk ini tidak memiliki addon yang tersedia';
                                            }
                                            
                                            return 'Pilih addon yang tersedia untuk produk ini';
                                        })
                                        ->columnSpanFull(),
                                    Forms\Components\CheckboxList::make('proses_perlu_sample_approval')
                                        ->label('Proses Perlu Sample Approval')
                                        ->options(function (Forms\Get $get) {
                                            $produkId = $get('produk_id');
                                            $designId = $get('design_id');
                                            $addons = $get('addons') ?? [];
                                            
                                            if (!$produkId) {
                                                return [];
                                            }
                                            
                                            $options = [];
                                            
                                            // 1. Design (jika dipilih dan mengurangi bahan)
                                            if ($designId && $designId !== 'none') {
                                                $design = ProdukProses::find($designId);
                                                if ($design && $design->apakah_mengurangi_bahan) {
                                                    $options[$design->id] = "[Design] {$design->nama}";
                                                }
                                            }
                                            
                                            // 2. Proses Produksi (selalu tampil karena wajib dan mengurangi bahan)
                                            $produksiProses = ProdukProses::where('produk_id', $produkId)
                                                ->where('produk_proses_kategori_id', 2) // Produksi
                                                ->where('apakah_mengurangi_bahan', true)
                                                ->get();
                                            
                                            foreach ($produksiProses as $pp) {
                                                $options[$pp->id] = "[Produksi] {$pp->nama}";
                                            }
                                            
                                            // 3. Addon/Finishing (hanya yang dipilih dan mengurangi bahan)
                                            if (!empty($addons) && is_array($addons)) {
                                                foreach ($addons as $addonId) {
                                                    $addon = ProdukProses::find($addonId);
                                                    if ($addon && $addon->apakah_mengurangi_bahan) {
                                                        $options[$addon->id] = "[Finishing] {$addon->nama}";
                                                    }
                                                }
                                            }
                                            
                                            return $options;
                                        })
                                        ->columns(1)
                                        ->visible(function (Forms\Get $get) {
                                            $produkId = $get('produk_id');
                                            if (!$produkId) {
                                                return false;
                                            }
                                            
                                            // Tampilkan jika ada proses yang mengurangi bahan
                                            return ProdukProses::where('produk_id', $produkId)
                                                ->where('apakah_mengurangi_bahan', true)
                                                ->whereIn('produk_proses_kategori_id', [2, 3])
                                                ->exists();
                                        })
                                        ->helperText('Pilih proses yang memerlukan approval sample dari customer sebelum produksi penuh')
                                        ->columnSpanFull()
                                        ->live(),
                                    Forms\Components\Textarea::make('keterangan')
                                        ->label('Keterangan')
                                        ->rows(2)
                                        ->columnSpanFull()
                                        ->helperText('Keterangan khusus untuk produk ini (opsional)'),
                                ])
                                ->mutateRelationshipDataBeforeCreateUsing(function (array $data, $state, Forms\Get $get): array {
                                    // Normalize design_id: jika "none", set menjadi null; jika numeric, cast int
                                    if (isset($data['design_id']) && $data['design_id'] === 'none') {
                                        $data['design_id'] = null;
                                    } elseif (isset($data['design_id']) && is_numeric($data['design_id'])) {
                                        $data['design_id'] = (int) $data['design_id'];
                                    }
                                    
                                    // Normalize addons menjadi integer array
                                    // Laravel akan otomatis encode ke JSON karena cast 'json' di model
                                    if (isset($data['addons']) && is_array($data['addons']) && !empty($data['addons'])) {
                                        $data['addons'] = array_map('intval', array_filter($data['addons']));
                                    } else {
                                        $data['addons'] = null; // Set null jika kosong
                                    }
                                    
                                    // Normalize proses_perlu_sample_approval menjadi integer array
                                    if (isset($data['proses_perlu_sample_approval']) && is_array($data['proses_perlu_sample_approval']) && !empty($data['proses_perlu_sample_approval'])) {
                                        $data['proses_perlu_sample_approval'] = array_map('intval', array_filter($data['proses_perlu_sample_approval']));
                                    } else {
                                        $data['proses_perlu_sample_approval'] = null;
                                    }
                                    
                                    // Hitung total_harga_produk sebelum create
                                    // Ambil customer_id dari parent form
                                    $customerId = $get('../../customer_id') ?? $get('customer_id');
                                    $data = static::calculateTotalHargaProduk($data, $customerId);
                                    
                                    return $data;
                                })
                                ->mutateRelationshipDataBeforeSaveUsing(function (array $data, $state, Forms\Get $get): array {
                                    // Normalize design_id: jika "none", set menjadi null; jika numeric, cast int
                                    if (isset($data['design_id']) && $data['design_id'] === 'none') {
                                        $data['design_id'] = null;
                                    } elseif (isset($data['design_id']) && is_numeric($data['design_id'])) {
                                        $data['design_id'] = (int) $data['design_id'];
                                    }
                                    
                                    // Normalize addons menjadi integer array
                                    // Laravel akan otomatis encode ke JSON karena cast 'json' di model
                                    // Handle jika addons sudah array (dari form) atau null (dari database)
                                    if (isset($data['addons'])) {
                                        if (is_array($data['addons']) && !empty($data['addons'])) {
                                            $data['addons'] = array_map('intval', array_filter($data['addons']));
                                        } else {
                                            $data['addons'] = null; // Set null jika kosong
                                        }
                                    } else {
                                        $data['addons'] = null;
                                    }
                                    
                                    // Normalize proses_perlu_sample_approval menjadi integer array
                                    if (isset($data['proses_perlu_sample_approval'])) {
                                        if (is_array($data['proses_perlu_sample_approval']) && !empty($data['proses_perlu_sample_approval'])) {
                                            $data['proses_perlu_sample_approval'] = array_map('intval', array_filter($data['proses_perlu_sample_approval']));
                                        } else {
                                            $data['proses_perlu_sample_approval'] = null;
                                        }
                                    } else {
                                        $data['proses_perlu_sample_approval'] = null;
                                    }
                                    
                                    // Hitung total_harga_produk sebelum save
                                    // Ambil customer_id dari parent form
                                    $customerId = $get('../../customer_id') ?? $get('customer_id');
                                    $data = static::calculateTotalHargaProduk($data, $customerId);
                                    
                                    return $data;
                                }),
                        ]),
                    Forms\Components\Wizard\Step::make('Customer')
                        ->schema([
                            Forms\Components\Select::make('customer_id')
                                ->label('Customer')
                                ->options(Customer::query()->get()->mapWithKeys(function ($customer) {
                                    return [$customer->id => "[{$customer->customerKategori->kode}] - {$customer->nama}"];
                                }))
                                ->searchable(['customerKategori.kode', 'nama', 'no_hp1', 'no_hp2'])
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                    // Hitung ulang total_harga_produk untuk semua produk saat customer berubah
                                    if (!$state) {
                                        return;
                                    }
                                    
                                    $produks = $get('produks');
                                    if (!is_array($produks)) {
                                        return;
                                    }
                                    
                                    foreach ($produks as $index => $produk) {
                                        if (!isset($produk['produk_id'])) {
                                            continue;
                                        }
                                        
                                        $produkData = [
                                            'produk_id' => $produk['produk_id'],
                                            'jumlah' => $produk['jumlah'] ?? 1,
                                            'panjang' => $produk['panjang'] ?? null,
                                            'lebar' => $produk['lebar'] ?? null,
                                            'design_id' => $produk['design_id'] ?? null,
                                            'addons' => $produk['addons'] ?? null,
                                        ];
                                        
                                        $calculatedData = static::calculateTotalHargaProduk($produkData, $state);
                                        if (isset($calculatedData['total_harga_produk'])) {
                                            $set("produks.{$index}.total_harga_produk", $calculatedData['total_harga_produk']);
                                        }
                                    }
                                })
                                ->createOptionForm([
                                    Forms\Components\Select::make('customer_kategori_id')
                                        ->label('Kategori Pelanggan')
                                        ->required()
                                        ->options(CustomerKategori::query()->pluck('nama', 'id'))
                                        ->searchable()
                                        ->preload()
                                        ->live()
                                        ->createOptionForm([
                                            Forms\Components\TextInput::make('nama')
                                                ->label('Nama Kategori')
                                                ->required()
                                                ->unique(ignoreRecord: true)
                                                ->maxLength(255),
                                            Forms\Components\Toggle::make('perlu_data_perusahaan')
                                                ->label('Perlu Data Perusahaan')
                                                ->default(false),
                                        ])
                                        ->createOptionUsing(function (array $data): int {
                                            $kategori = CustomerKategori::create([
                                                'nama' => $data['nama'],
                                                'perlu_data_perusahaan' => $data['perlu_data_perusahaan'] ?? false,
                                            ]);
                                            return $kategori->id;
                                        }),
                                    Forms\Components\TextInput::make('kode')
                                        ->label('Kode Pelanggan')
                                        ->required()
                                        ->maxLength(255)
                                        ->helperText(customableState())
                                        ->default(fn ($record) => $record?->kode ?? generateKode('CST')),
                                    Forms\Components\TextInput::make('nama')
                                        ->label('Nama Pelanggan')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpanFull(),
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\TextInput::make('no_hp1')
                                                ->maxLength(255),
                                            Forms\Components\TextInput::make('no_hp2')
                                                ->maxLength(255),
                                        ]),
                                    Forms\Components\Textarea::make('alamat')
                                        ->columnSpanFull(),
                                    Forms\Components\TextInput::make('nama_perusahaan')
                                        ->maxLength(255)
            ->columnSpanFull()
                                        ->required(fn(Forms\Get $get) => CustomerKategori::find($get('customer_kategori_id'))?->perlu_data_perusahaan ?? false)
                                        ->visible(fn(Forms\Get $get) => CustomerKategori::find($get('customer_kategori_id'))?->perlu_data_perusahaan ?? false),
                                    Forms\Components\Textarea::make('alamat_perusahaan')
                                        ->columnSpanFull()
                                        ->required(fn(Forms\Get $get) => CustomerKategori::find($get('customer_kategori_id'))?->perlu_data_perusahaan ?? false)
                                        ->visible(fn(Forms\Get $get) => CustomerKategori::find($get('customer_kategori_id'))?->perlu_data_perusahaan ?? false),
                                ])
                                ->columns(2)
                                ->createOptionUsing(function (array $data): int {
                                    // Generate kode jika tidak ada
                                    if (empty($data['kode'])) {
                                        $data['kode'] = generateKode('CST');
                                    }
                                    
                                    $customer = Customer::create($data);
                                    return $customer->id;
                                }),
                        ]),
                    Forms\Components\Wizard\Step::make('Ringkasan Biaya')
                        ->schema([
                            Forms\Components\Section::make('Detail Biaya Per Produk')
                                ->schema([
                                    Forms\Components\Placeholder::make('detail_biaya')
                                        ->label('')
                                        ->content(function (Forms\Get $get) {
                                            $produks = $get('produks');
                                            $customerId = $get('customer_id');
                                            
                                            if (!is_array($produks) || empty($produks) || !$customerId) {
                                                return 'Pilih produk dan customer terlebih dahulu';
                                            }
                                            
                                            $customer = Customer::find($customerId);
                                            if (!$customer) {
                                                return 'Customer tidak ditemukan';
                                            }
                                            
                                            // Pastikan total_harga_produk sudah dihitung untuk setiap produk
                                            // Gunakan logika yang sama dengan calculateTotalHargaProduk
                                            foreach ($produks as $index => $produk) {
                                                if (!isset($produk['produk_id']) || isset($produk['total_harga_produk'])) {
                                                    continue;
                                                }
                                                
                                                // Hitung total_harga_produk jika belum ada
                                                $calculatedData = static::calculateTotalHargaProduk($produk, $customerId);
                                                if (isset($calculatedData['total_harga_produk'])) {
                                                    $produks[$index]['total_harga_produk'] = $calculatedData['total_harga_produk'];
                                                }
                                            }
                                            
                                            $html = '<div style="font-family: sans-serif;">';
                                            $totalKeseluruhan = 0;
                                            $produkCounter = 0; // Counter untuk numbering produk yang benar
                                            
                                            foreach ($produks as $index => $produk) {
                                                if (!isset($produk['produk_id'])) continue;
                                                
                                                $produkModel = Produk::find($produk['produk_id']);
                                                if (!$produkModel) continue;
                                                
                                                $produkCounter++; // Increment counter untuk setiap produk valid
                                                
                                                // Gunakan total_harga_produk yang sudah dihitung jika ada, jika tidak hitung ulang
                                                $totalProdukFromState = isset($produk['total_harga_produk']) ? (float) $produk['total_harga_produk'] : null;
                                                
                                                // Jika total_harga_produk belum ada, hitung menggunakan logika yang sama dengan calculateTotalHargaProduk
                                                if ($totalProdukFromState === null || $totalProdukFromState === 0) {
                                                    // Parse jumlah untuk menentukan tier
                                                    $jumlahRaw = $produk['jumlah'] ?? 1;
                                                    if (is_string($jumlahRaw)) {
                                                        $jumlah = (int) str_replace([',', ' ', '.'], '', $jumlahRaw);
                                                    } else {
                                                        $jumlah = (int) $jumlahRaw;
                                                    }
                                                    if ($jumlah <= 0) $jumlah = 1;
                                                    
                                                    // Ambil harga satuan berdasarkan tiering
                                                    $hargaSatuan = static::getHargaSatuanByTiering(
                                                        $produk['produk_id'],
                                                        $customer->customer_kategori_id,
                                                        $jumlah
                                                    );
                                                    
                                                    // Konversi ke float untuk perhitungan
                                                    $jumlahFloat = (float) $jumlah;
                                                    
                                                    // Parse panjang - handle nullable, TETAPKAN DECIMAL (jangan hilangkan titik)
                                                    $panjangRaw = $produk['panjang'] ?? null;
                                                    if ($panjangRaw === null || $panjangRaw === '') {
                                                        $panjang = 1.0;
                                                    } else {
                                                        if (is_string($panjangRaw)) {
                                                            // Hanya hilangkan koma dan spasi, JANGAN hilangkan titik (decimal separator)
                                                            $panjang = (float) str_replace([',', ' '], '', $panjangRaw);
                                                        } else {
                                                            $panjang = (float) $panjangRaw;
                                                        }
                                                        if ($panjang <= 0) $panjang = 1.0;
                                                    }
                                                    
                                                    // Parse lebar - handle nullable, TETAPKAN DECIMAL (jangan hilangkan titik)
                                                    $lebarRaw = $produk['lebar'] ?? null;
                                                    if ($lebarRaw === null || $lebarRaw === '') {
                                                        $lebar = 1.0;
                                                    } else {
                                                        if (is_string($lebarRaw)) {
                                                            // Hanya hilangkan koma dan spasi, JANGAN hilangkan titik (decimal separator)
                                                            $lebar = (float) str_replace([',', ' '], '', $lebarRaw);
                                                        } else {
                                                            $lebar = (float) $lebarRaw;
                                                        }
                                                        if ($lebar <= 0) $lebar = 1.0;
                                                    }
                                                    
                                                    // HITUNG TOTAL PRODUK (tanpa design dan addon, karena akan ditambahkan terpisah)
                                                    // Formula: Harga Satuan × Jumlah × Panjang × Lebar
                                                    $totalProduk = $hargaSatuan * $jumlahFloat * $panjang * $lebar;
                                                } else {
                                                    // Gunakan total_harga_produk yang sudah dihitung
                                                    // Tapi perlu dikurangi design dan addon karena total_harga_produk sudah termasuk semuanya
                                                    // Untuk tampilan, kita perlu breakdown, jadi hitung ulang bagian produk saja
                                                    // Parse jumlah untuk menentukan tier
                                                    $jumlahRaw = $produk['jumlah'] ?? 1;
                                                    if (is_string($jumlahRaw)) {
                                                        $jumlah = (int) str_replace([',', ' ', '.'], '', $jumlahRaw);
                                                    } else {
                                                        $jumlah = (int) $jumlahRaw;
                                                    }
                                                    if ($jumlah <= 0) $jumlah = 1;
                                                    
                                                    // Ambil harga satuan berdasarkan tiering
                                                    $hargaSatuan = static::getHargaSatuanByTiering(
                                                        $produk['produk_id'],
                                                        $customer->customer_kategori_id,
                                                        $jumlah
                                                    );
                                                    
                                                    // Konversi ke float untuk perhitungan
                                                    $jumlahFloat = (float) $jumlah;
                                                    
                                                    // Parse panjang dan lebar untuk display
                                                    $panjangRaw = $produk['panjang'] ?? null;
                                                    if ($panjangRaw === null || $panjangRaw === '') {
                                                        $panjang = 1.0;
                                                    } else {
                                                        if (is_string($panjangRaw)) {
                                                            $panjang = (float) str_replace([',', ' '], '', $panjangRaw);
                                                        } else {
                                                            $panjang = (float) $panjangRaw;
                                                        }
                                                        if ($panjang <= 0) $panjang = 1.0;
                                                    }
                                                    
                                                    $lebarRaw = $produk['lebar'] ?? null;
                                                    if ($lebarRaw === null || $lebarRaw === '') {
                                                        $lebar = 1.0;
                                                    } else {
                                                        if (is_string($lebarRaw)) {
                                                            $lebar = (float) str_replace([',', ' '], '', $lebarRaw);
                                                        } else {
                                                            $lebar = (float) $lebarRaw;
                                                        }
                                                        if ($lebar <= 0) $lebar = 1.0;
                                                    }
                                                    
                                                    // Hitung subtotal produk (tanpa design dan addon)
                                                    $totalProduk = $hargaSatuan * $jumlahFloat * $panjang * $lebar;
                                                }
                                                
                                                $html .= '<div style="border: 1px solid #e5e7eb; padding: 16px; margin-bottom: 16px; border-radius: 8px;">';
                                                
                                                // Tampilkan judul pesanan jika ada
                                                if (isset($produk['judul_pesanan']) && !empty($produk['judul_pesanan'])) {
                                                    $html .= '<div style="margin-bottom: 8px; padding: 8px; background: #f0fdf4; border-left: 4px solid #10b981; border-radius: 4px;">';
                                                    $html .= '<strong style="color: #059669;">Judul Pesanan:</strong> ' . e($produk['judul_pesanan']);
                                                    $html .= '</div>';
                                                }
                                                
                                                $html .= '<h4 style="margin: 0 0 12px 0; color: #374151;">Produk #' . $produkCounter . ': [' . $produkModel->kode . '] - ' . $produkModel->nama . '</h4>';
                                                $html .= '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 12px;">';
                                                $html .= '<div><strong>Harga Satuan:</strong> ' . formatRupiah($hargaSatuan) . '</div>';
                                                $html .= '<div><strong>Jumlah:</strong> ' . $jumlahFloat . '</div>';
                                                
                                                if (isset($produk['panjang']) && isset($produk['lebar'])) {
                                                    // Tampilkan dimensi dengan decimal jika ada (format: hapus trailing zero)
                                                    $panjangDisplay = rtrim(rtrim(number_format($panjang, 2, '.', ''), '0'), '.');
                                                    $lebarDisplay = rtrim(rtrim(number_format($lebar, 2, '.', ''), '0'), '.');
                                                    $html .= '<div><strong>Dimensi:</strong> ' . $panjangDisplay . ' x ' . $lebarDisplay . '</div>';
                                                } else {
                                                    $html .= '<div><strong>Dimensi:</strong> Standar</div>';
                                                }
                                                
                                                $html .= '<div><strong>Subtotal Produk:</strong> ' . formatRupiah($totalProduk) . '</div>';
                                                // Tampilkan breakdown dengan decimal yang benar
                                                $panjangDisplay = rtrim(rtrim(number_format($panjang, 2, '.', ''), '0'), '.');
                                                $lebarDisplay = rtrim(rtrim(number_format($lebar, 2, '.', ''), '0'), '.');
                                                $html .= '<div style="font-size: 12px; color: #6b7280; margin-top: 4px;">(' . formatRupiah($hargaSatuan) . ' × ' . $jumlahFloat . ' × ' . $panjangDisplay . ' × ' . $lebarDisplay . ')</div>';
                                                $html .= '</div>';
                                                
                                                // Hitung dan tampilkan design
                                                $totalDesign = 0.0;
                                                if (isset($produk['design_id']) && !empty($produk['design_id'])) {
                                                $design = ProdukProses::where('id', $produk['design_id'])->where('produk_proses_kategori_id', 1)->first();
                                                    if ($design) {
                                                        $html .= '<div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #f3f4f6;">';
                                                        $html .= '<strong>Design:</strong><br>';
                                                    $designHarga = (float) ($design->harga ?? 0);
                                                    $html .= '• ' . $design->nama . ': ' . formatRupiah($designHarga);
                                                    $totalDesign = $designHarga;
                                                        $html .= '</div>';
                                                    }
                                                }
                                                
                                                // Hitung dan tampilkan addon
                                                $totalAddon = 0.0;
                                                if (isset($produk['addons']) && is_array($produk['addons']) && !empty($produk['addons'])) {
                                                    $addons = ProdukProses::whereIn('id', $produk['addons'])->whereNotNull('harga')->where('produk_proses_kategori_id', 3)->get();
                                                    if ($addons->count() > 0) {
                                                        $html .= '<div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #f3f4f6;">';
                                                        $html .= '<strong>Addons:</strong><br>';
                                                        foreach ($addons as $addon) {
                                                            $html .= '• ' . $addon->nama . ': ' . formatRupiah($addon->harga) . '<br>';
                                                            $totalAddon += (float) $addon->harga;
                                                        }
                                                        $html .= '<strong>Total Addon: ' . formatRupiah($totalAddon) . '</strong>';
                                                        $html .= '</div>';
                                                    }
                                                }
                                                
                                                // Tampilkan keterangan jika ada
                                                if (isset($produk['keterangan']) && !empty($produk['keterangan'])) {
                                                    $html .= '<div style="margin-top: 8px; padding: 8px; background: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 4px;">';
                                                    $html .= '<strong>Keterangan:</strong> ' . nl2br(e($produk['keterangan']));
                                                    $html .= '</div>';
                                                }
                                                
                                                // HITUNG TOTAL PRODUK FINAL
                                                $realCalculated = $totalProduk + $totalDesign + $totalAddon;
                                                
                                                if ($totalProdukFromState !== null && $totalProdukFromState > 0) {
                                                    $totalProdukFinal = $totalProdukFromState;
                                                } else {
                                                    // Jika belum ada di state, terapkan logika harga minimal
                                                    if ($produkModel->harga_minimal > 0 && $realCalculated < $produkModel->harga_minimal) {
                                                        $totalProdukFinal = (float) $produkModel->harga_minimal;
                                                    } else {
                                                        $totalProdukFinal = $realCalculated;
                                                    }
                                                }
                                                
                                                $html .= '<div style="margin-top: 12px; padding-top: 8px; border-top: 2px solid #10b981; font-weight: bold; color: #059669;">';
                                                $html .= 'Total Produk #' . $produkCounter . ': ' . formatRupiah($totalProdukFinal);
                                                $html .= '</div>';
                                                
                                                // Tampilkan info jika terkena harga minimal
                                                if ($produkModel->harga_minimal > 0 && $realCalculated < $produkModel->harga_minimal) {
                                                     $html .= '<div style="font-size: 11px; color: #d97706; margin-top: 4px; font-style: italic;">
                                                        * Harga auto-adjust ke minimum: ' . formatRupiah($produkModel->harga_minimal) . ' 
                                                        (Kalkulasi asli: ' . formatRupiah($realCalculated) . ')
                                                     </div>';
                                                }
                                                $html .= '</div>';
                                                
                                                $totalKeseluruhan += $totalProdukFinal;
                                            }
                                            
                                            $html .= '<div style="background: #f0fdf4; border: 2px solid #10b981; padding: 16px; border-radius: 8px; text-align: center;">';
                                            $html .= '<h3 style="margin: 0; color: #059669; font-size: 20px;">TOTAL KESELURUHAN: ' . formatRupiah($totalKeseluruhan) . '</h3>';
                                            $html .= '</div>';
                                            $html .= '</div>';
                                            
                                            return new \Illuminate\Support\HtmlString($html);
                                        })
                                        ->columnSpanFull(),
                                ])
                                ->columnSpanFull(),
                            Forms\Components\Hidden::make('total_harga_kalkulasi')
                                ->afterStateHydrated(function (Forms\Set $set, Forms\Get $get) {
                                    // Hitung dan simpan total untuk database
                                    $produks = $get('produks');
                                    $customerId = $get('customer_id');
                                    
                                    if (!is_array($produks) || empty($produks) || !$customerId) {
                                        $set('total_harga_kalkulasi', 0);
                                        return;
                                    }
                                    
                                    $customer = Customer::find($customerId);
                                    if (!$customer) {
                                        $set('total_harga_kalkulasi', 0);
                                        return;
                                    }
                                    
                                    $totalKeseluruhan = 0;
                                    
                                    foreach ($produks as $index => $produk) {
                                        if (!isset($produk['produk_id'])) continue;
                                        
                                        // Parse jumlah untuk menentukan tier
                                        $jumlahRaw = $produk['jumlah'] ?? 1;
                                        if (is_string($jumlahRaw)) {
                                            $jumlah = (int) str_replace([',', ' ', '.'], '', $jumlahRaw);
                                        } else {
                                            $jumlah = (int) $jumlahRaw;
                                        }
                                        if ($jumlah <= 0) $jumlah = 1;
                                        
                                        // Ambil harga satuan berdasarkan tiering
                                        $hargaSatuan = static::getHargaSatuanByTiering(
                                            $produk['produk_id'],
                                            $customer->customer_kategori_id,
                                            $jumlah
                                        );
                                        
                                        // Konversi ke float untuk perhitungan
                                        $jumlahFloat = (float) $jumlah;
                                        
                                        // Parse panjang - handle nullable, TETAPKAN DECIMAL (jangan hilangkan titik)
                                        $panjangRaw = $produk['panjang'] ?? null;
                                        if ($panjangRaw === null || $panjangRaw === '') {
                                            $panjang = 1.0;
                                        } else {
                                            if (is_string($panjangRaw)) {
                                                // Hanya hilangkan koma dan spasi, JANGAN hilangkan titik (decimal separator)
                                                $panjang = (float) str_replace([',', ' '], '', $panjangRaw);
                                            } else {
                                                $panjang = (float) $panjangRaw;
                                            }
                                            if ($panjang <= 0) $panjang = 1.0;
                                        }
                                        
                                        // Parse lebar - handle nullable, TETAPKAN DECIMAL (jangan hilangkan titik)
                                        $lebarRaw = $produk['lebar'] ?? null;
                                        if ($lebarRaw === null || $lebarRaw === '') {
                                            $lebar = 1.0;
                                        } else {
                                            if (is_string($lebarRaw)) {
                                                // Hanya hilangkan koma dan spasi, JANGAN hilangkan titik (decimal separator)
                                                $lebar = (float) str_replace([',', ' '], '', $lebarRaw);
                                            } else {
                                                $lebar = (float) $lebarRaw;
                                            }
                                            if ($lebar <= 0) $lebar = 1.0;
                                        }
                                        
                                        // Hitung total produk
                                        $totalProduk = $hargaSatuan * $jumlahFloat * $panjang * $lebar;
                                        
                                        // Tambah harga design (single value)
                                        if (isset($produk['design_id']) && !empty($produk['design_id'])) {
                                            $designProses = ProdukProses::where('id', $produk['design_id'])
                                                ->where('produk_proses_kategori_id', 1) // Design
                                                ->first();
                                            if ($designProses) {
                                                $totalProduk += (float) ($designProses->harga ?? 0);
                                            }
                                        }
                                        
                                        // Tambah harga addon
                                        // Data dari form akan selalu array atau null
                                        if (isset($produk['addons']) && is_array($produk['addons']) && !empty($produk['addons'])) {
                                            // Filter untuk memastikan semua ID adalah integer positif
                                            $addonsArray = array_filter(array_map('intval', $produk['addons']), fn($id) => $id > 0);
                                            
                                            if (!empty($addonsArray)) {
                                                $totalAddon = ProdukProses::whereIn('id', $addonsArray)
                                                    ->where('produk_proses_kategori_id', 3) // Finishing/Addon
                                                    ->whereNotNull('harga')
                                                    ->sum('harga');
                                                $totalProduk += (float) $totalAddon;
                                            }
                                        }
                                        
                                        $totalKeseluruhan += $totalProduk;
                                        
                                        // Update total_harga_produk untuk setiap item
                                        $set('produks.' . $index . '.total_harga_produk', $totalProduk);
                                    }
                                    
                                    $set('total_harga_kalkulasi', $totalKeseluruhan);
                                })
                                ->dehydrated(),
                        ])
                ])
                ->skippable(fn(string $operation) => $operation === 'edit')
                ->columnSpanFull()
            ]);
    }

    /**
     * Helper function untuk mendapatkan harga berdasarkan tiering
     * 
     * @param int $produkId
     * @param int $customerKategoriId
     * @param int $jumlahPesanan
     * @return float
     */
    public static function getHargaSatuanByTiering(int $produkId, int $customerKategoriId, int $jumlahPesanan): float
    {
        // Ambil semua tier harga untuk produk dan kategori customer
        $produkHargas = ProdukHarga::where('produk_id', $produkId)
            ->where('customer_kategori_id', $customerKategoriId)
            ->orderBy('jumlah_pesanan_minimal', 'asc')
            ->get();
        
        if ($produkHargas->isEmpty()) {
            return 0.0;
        }
        
        // Cari tier yang sesuai dengan jumlah pesanan
        foreach ($produkHargas as $produkHarga) {
            if ($jumlahPesanan >= $produkHarga->jumlah_pesanan_minimal && 
                $jumlahPesanan <= $produkHarga->jumlah_pesanan_maksimal) {
                return (float) $produkHarga->harga;
            }
        }
        
        // Jika tidak ada tier yang sesuai, ambil tier terdekat (yang jumlah_pesanan_minimal terdekat)
        // Atau bisa juga ambil tier terakhir jika jumlah pesanan melebihi semua tier
        $lastTier = $produkHargas->last();
        if ($jumlahPesanan > $lastTier->jumlah_pesanan_maksimal) {
            return (float) $lastTier->harga;
        }
        
        // Jika jumlah pesanan kurang dari tier pertama, gunakan tier pertama
        $firstTier = $produkHargas->first();
        if ($jumlahPesanan < $firstTier->jumlah_pesanan_minimal) {
            return (float) $firstTier->harga;
        }
        
        // Fallback: return 0
        return 0.0;
    }

    protected static function calculateTotalHargaProduk(array $data, $customerId = null): array
    {
        // Jika customer_id tidak diberikan, coba ambil dari record jika ada
        if (!$customerId && isset($data['transaksi_kalkulasi_id'])) {
            $transaksiKalkulasi = \App\Models\TransaksiKalkulasi::find($data['transaksi_kalkulasi_id']);
            if ($transaksiKalkulasi) {
                $customerId = $transaksiKalkulasi->customer_id;
            }
        }
        
        if (!$customerId || !isset($data['produk_id'])) {
            $data['total_harga_produk'] = 0;
            return $data;
        }
        
        $customer = Customer::find($customerId);
        if (!$customer) {
            $data['total_harga_produk'] = 0;
            return $data;
        }
        
        // Parse jumlah untuk menentukan tier
        $jumlahRaw = $data['jumlah'] ?? 1;
        if (is_string($jumlahRaw)) {
            $jumlah = (int) str_replace([',', ' ', '.'], '', $jumlahRaw);
        } else {
            $jumlah = (int) $jumlahRaw;
        }
        if ($jumlah <= 0) $jumlah = 1;
        
        // Ambil harga satuan berdasarkan tiering
        $hargaSatuan = static::getHargaSatuanByTiering(
            $data['produk_id'],
            $customer->customer_kategori_id,
            $jumlah
        );
        
        // Parse jumlah (sudah di-parse di atas untuk tiering)
        // Konversi ke float untuk perhitungan
        $jumlahFloat = (float) $jumlah;
        
        // Parse panjang - TETAPKAN DECIMAL
        $panjangRaw = $data['panjang'] ?? null;
        if ($panjangRaw === null || $panjangRaw === '') {
            $panjang = 1.0;
        } else {
            if (is_string($panjangRaw)) {
                $panjang = (float) str_replace([',', ' '], '', $panjangRaw);
            } else {
                $panjang = (float) $panjangRaw;
            }
            if ($panjang <= 0) $panjang = 1.0;
        }
        
        // Parse lebar - TETAPKAN DECIMAL
        $lebarRaw = $data['lebar'] ?? null;
        if ($lebarRaw === null || $lebarRaw === '') {
            $lebar = 1.0;
        } else {
            if (is_string($lebarRaw)) {
                $lebar = (float) str_replace([',', ' '], '', $lebarRaw);
            } else {
                $lebar = (float) $lebarRaw;
            }
            if ($lebar <= 0) $lebar = 1.0;
        }
        
        // Hitung total produk
        $totalProduk = $hargaSatuan * $jumlahFloat * $panjang * $lebar;
        
        // Tambah harga design (single value, bukan array)
        if (isset($data['design_id']) && !empty($data['design_id'])) {
            $designProses = ProdukProses::where('id', $data['design_id'])
                ->where('produk_proses_kategori_id', 1) // Design
                ->first();
            if ($designProses) {
                $totalProduk += (float) ($designProses->harga ?? 0);
            }
        }
        
        // Tambah harga addon
        // Laravel akan otomatis decode JSON ke array karena cast 'json' di model
        // Di sini data dari form akan selalu array, atau null
        if (isset($data['addons']) && is_array($data['addons']) && !empty($data['addons'])) {
            // Filter untuk memastikan semua ID adalah integer positif
            $addonsArray = array_filter(array_map('intval', $data['addons']), fn($id) => $id > 0);
            
            if (!empty($addonsArray)) {
                $totalAddon = ProdukProses::whereIn('id', $addonsArray)
                    ->where('produk_proses_kategori_id', 3) // Finishing/Addon
                    ->whereNotNull('harga')
                    ->sum('harga');
                $totalProduk += (float) $totalAddon;
            }
        }

        // Cek Harga Minimal Produk
        $produkModel = Produk::find($data['produk_id']);
        if ($produkModel && $produkModel->harga_minimal > 0) {
            if ($totalProduk < $produkModel->harga_minimal) {
                $totalProduk = (float) $produkModel->harga_minimal;
            }
        }
        
        // Set total_harga_produk (convert ke integer untuk database)
        $data['total_harga_produk'] = (int) round($totalProduk);
        
        return $data;
    }

    protected static function updateTotalHargaProdukForRecord($record): void
    {
        if (!$record || !$record->customer_id) {
            return;
        }

        $customer = Customer::find($record->customer_id);
        if (!$customer) {
            return;
        }

        foreach ($record->transaksiKalkulasiProduks as $produk) {
            // Parse jumlah untuk menentukan tier
            $jumlah = (int) ($produk->jumlah ?? 1);
            if ($jumlah <= 0) $jumlah = 1;
            
            // Ambil harga satuan berdasarkan tiering
            $hargaSatuan = static::getHargaSatuanByTiering(
                $produk->produk_id,
                $customer->customer_kategori_id,
                $jumlah
            );

            // Konversi ke float untuk perhitungan
            $jumlahFloat = (float) $jumlah;

            $panjang = $produk->panjang ? (float) $produk->panjang : 1.0;
            if ($panjang <= 0) $panjang = 1.0;

            $lebar = $produk->lebar ? (float) $produk->lebar : 1.0;
            if ($lebar <= 0) $lebar = 1.0;

            // Hitung total produk
            $totalProduk = $hargaSatuan * $jumlahFloat * $panjang * $lebar;

            // Tambah harga design (single value)
            if ($produk->design_id) {
                $designProses = ProdukProses::where('id', $produk->design_id)
                    ->where('produk_proses_kategori_id', 1) // Design
                    ->whereNotNull('harga')
                    ->first();
                if ($designProses) {
                    $totalProduk += (float) $designProses->harga;
                }
            }

            // Tambah harga addon
            // Model sudah punya cast 'addons' => 'json', jadi Laravel auto-decode JSON ke array
            if ($produk->addons && is_array($produk->addons) && !empty($produk->addons)) {
                // Filter untuk memastikan semua ID adalah integer positif
                $addonsArray = array_filter(array_map('intval', $produk->addons), fn($id) => $id > 0);
                
                if (!empty($addonsArray)) {
                    $totalAddon = ProdukProses::whereIn('id', $addonsArray)
                        ->where('produk_proses_kategori_id', 3) // Finishing/Addon
                        ->whereNotNull('harga')
                        ->sum('harga');
                    $totalProduk += (float) $totalAddon;
                }
            }

            // Cek Harga Minimal Produk
            $produkModel = Produk::find($produk->produk_id);
            if ($produkModel && $produkModel->harga_minimal > 0) {
                if ($totalProduk < $produkModel->harga_minimal) {
                    $totalProduk = (float) $produkModel->harga_minimal;
                }
            }

            // Update total_harga_produk
            $produk->update([
                'total_harga_produk' => (int) round($totalProduk)
            ]);
        }
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('kode')
                    ->label('Kode Kalkulasi')
                    ->searchable()
                    ->description(fn(TransaksiKalkulasi $record) => "({$record->customer->nama})")
                    ->weight('bold')
                    ->searchable(query: fn(Builder $query, string $search) => $query->where('kode', 'like', '%' . $search . '%')->orWhereHas('customer', function ($query) use ($search) {
                        $query->where('nama', 'like', '%' . $search . '%');
                    })),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Kalkulasi')
                    ->dateTime('d F Y')
                    ->description(fn(TransaksiKalkulasi $record) => Carbon::parse($record->created_at)->format('H:i')),
                Tables\Columns\TextColumn::make('total_harga')
                    ->label('Total Harga Kalkulasi')
                    ->money('IDR')
                    ->weight('bold')
                    ->getStateUsing(fn(TransaksiKalkulasi $record) => formatRupiah($record->transaksiKalkulasiProduks->sum('total_harga_produk')))
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat Kalkulasi')
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->modalHeading(fn(TransaksiKalkulasi $record) => 'Ringkasan Biaya - ' . $record->kode)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup'),
                Tables\Actions\EditAction::make()
                    ->after(function ($record) {
                        // Update total_harga_produk untuk setiap produk setelah edit
                        static::updateTotalHargaProdukForRecord($record);
                    })
                    ->modalHeading('Deskprint')
                    ->visible(fn(TransaksiKalkulasi $record) => $record->transaksis->isEmpty()),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn(TransaksiKalkulasi $record) => $record->transaksis->isEmpty()),
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
            'index' => ManageDeskprints::route('/'),
        ];
    }
}
