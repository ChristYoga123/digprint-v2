<?php

namespace App\Filament\Admin\Resources;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use App\Models\Produk;
use App\Models\Proses;
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
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use App\Filament\Admin\Resources\DeskprintResource\Pages\ManageDeskprints;
use App\Models\ProdukProsesKategori;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;

class DeskprintResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = TransaksiKalkulasi::class;

    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $navigationLabel = 'Deskprint';
    protected static ?string $modelLabel = 'Deskprint';

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
            'delete_any',
            'lihat_seluruhnya',
            'lihat_sebagian',
        ];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()->can('view_deskprint') && Auth::user()->can('view_any_deskprint');
    }

    public static function canViewAny(): bool
    {
        return Auth::user()->can('view_any_deskprint');
    }

    public static function canView(Model $record): bool
    {
        return Auth::user()->can('view_deskprint');
    }

    public static function canCreate(): bool
    {
        return Auth::user()->can('create_deskprint');
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()->can('update_deskprint');
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()->can('delete_deskprint');
    }

    public static function canDeleteAny(): bool
    {
        return Auth::user()->can('delete_any_deskprint');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    // ==================== STEP 1: CUSTOMER ====================
                    Forms\Components\Wizard\Step::make('Customer')
                        ->icon('heroicon-o-user')
                        ->schema([
                            Forms\Components\Section::make('Informasi Customer')
                                ->description('Pilih atau tambah customer baru')
                                ->icon('heroicon-o-user-circle')
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
                                            if (!$state) return;
                                            
                                            $produks = $get('produks');
                                            if (!is_array($produks)) return;
                                            
                                            foreach ($produks as $index => $produk) {
                                                if (!isset($produk['produk_id'])) continue;
                                                
                                                $calculatedData = static::calculateTotalHargaProduk($produk, $state);
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
                                                ->live(),
                                            Forms\Components\TextInput::make('kode')
                                                ->label('Kode Pelanggan')
                                                ->required()
                                                ->maxLength(255)
                                                ->helperText(customableState())
                                                ->default(fn () => generateKode('CST')),
                                            Forms\Components\TextInput::make('nama')
                                                ->label('Nama Pelanggan')
                                                ->required()
                                                ->maxLength(255)
                                                ->columnSpanFull(),
                                            Forms\Components\Grid::make(2)
                                                ->schema([
                                                    Forms\Components\TextInput::make('no_hp1')->maxLength(255),
                                                    Forms\Components\TextInput::make('no_hp2')->maxLength(255),
                                                ]),
                                            Forms\Components\Textarea::make('alamat')->columnSpanFull(),
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
                                            if (empty($data['kode'])) {
                                                $data['kode'] = generateKode('CST');
                                            }
                                            $customer = Customer::create($data);
                                            return $customer->id;
                                        }),
                                    Forms\Components\TextInput::make('kode')
                                        ->label('Kode Kalkulasi')
                                        ->required()
                                        ->maxLength(255)
                                        ->helperText('Otomatis terisi tetapi bisa di-custom')
                                        ->default(fn ($record) => $record?->kode ?? generateKode('KAL')),
                                ])
                                ->columns(2),
                        ]),
                    
                    // ==================== STEP 2: PRODUK ====================
                    Forms\Components\Wizard\Step::make('Produk')
                        ->icon('heroicon-o-shopping-bag')
                        ->schema([
                            Forms\Components\Repeater::make('produks')
                                ->label('')
                                ->relationship('transaksiKalkulasiProduks')
                                ->live()
                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                    if (!is_array($state)) return;
                                    
                                    $customerId = $get('customer_id');
                                    if (!$customerId) return;
                                    
                                    foreach ($state as $index => $produk) {
                                        if (!isset($produk['produk_id'])) continue;
                                        
                                        $calculatedData = static::calculateTotalHargaProduk($produk, $customerId);
                                        if (isset($calculatedData['total_harga_produk'])) {
                                            $set("produks.{$index}.total_harga_produk", $calculatedData['total_harga_produk']);
                                        }
                                    }
                                })
                                ->schema([
                                    // ---- SECTION: Info Pesanan ----
                                    Forms\Components\Section::make('📝 Info Pesanan')
                                        ->schema([
                                            Forms\Components\TextInput::make('judul_pesanan')
                                                ->label('Judul Pesanan')
                                                ->placeholder('Contoh: Banner Grand Opening, Undangan Pernikahan, dll')
                                                ->required()
                                                ->columnSpanFull(),
                                        ])
                                        ->compact()
                                        ->collapsible(),
                                    
                                    // ---- SECTION: Produk & Jumlah ----
                                    Forms\Components\Section::make('📦 Produk & Jumlah')
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
                                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                                    $set('addons', []);
                                                    
                                                    $customerId = $get('../../../../customer_id') ?? $get('../../customer_id');
                                                    if (!$customerId) return;
                                                    
                                                    $produkData = [
                                                        'produk_id' => $get('produk_id'),
                                                        'jumlah' => $get('jumlah'),
                                                        'panjang' => $get('panjang'),
                                                        'lebar' => $get('lebar'),
                                                        'design_id' => $get('design_id'),
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
                                                    $customerId = $get('../../../../customer_id') ?? $get('../../customer_id');
                                                    if (!$customerId) return;
                                                    
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
                                                ->numeric()
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                                    $customerId = $get('../../../../customer_id') ?? $get('../../customer_id');
                                                    if (!$customerId) return;
                                                    
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
                                                ->numeric()
                                                ->live(onBlur: true)
                                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                                    $customerId = $get('../../../../customer_id') ?? $get('../../customer_id');
                                                    if (!$customerId) return;
                                                    
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
                                        ])
                                        ->columns(2)
                                        ->compact()
                                        ->collapsible(),
                                    
                                    // ---- SECTION: Design (GLOBAL) ----
                                    Forms\Components\Section::make('🎨 Design')
                                        ->description('Pilih design dari Master Design (berlaku untuk semua produk)')
                                        ->schema([
                                            Forms\Components\Radio::make('design_id')
                                                ->label('')
                                                ->options(function () {
                                                    $designs = Proses::where('produk_proses_kategori_id', ProdukProsesKategori::praProduksiId())
                                                        ->whereNotNull('harga_default')
                                                        ->orderBy('nama')
                                                        ->get();
                                                    
                                                    $options = ['none' => '❌ Tidak Pakai Design (Customer Punya Design Sendiri)'];
                                                    
                                                    foreach ($designs as $design) {
                                                        $options[$design->id] = "🎨 {$design->nama} - " . formatRupiah($design->harga_default);
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
                                                        $set('link_design', null);
                                                    }
                                                    
                                                    $customerId = $get('../../../../customer_id') ?? $get('../../customer_id');
                                                    if (!$customerId) return;
                                                    
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
                                                ->columnSpanFull(),
                                            Forms\Components\TextInput::make('link_design')
                                                ->label('Link Design Customer')
                                                ->url()
                                                ->placeholder('https://drive.google.com/...')
                                                ->maxLength(255)
                                                ->helperText('Masukkan link design jika customer sudah punya design sendiri')
                                                ->visible(fn (Forms\Get $get) => $get('design_id') === 'none' || $get('design_id') === null || $get('design_id') === '')
                                                ->columnSpanFull(),
                                        ])
                                        ->compact()
                                        ->collapsible()
                                        ->visible(fn (Forms\Get $get) => !empty($get('produk_id'))),
                                    
                                    // ---- SECTION: Addon/Finishing ----
                                    Forms\Components\Section::make('✨ Addon / Finishing')
                                        ->description('Pilih addon yang tersedia untuk produk ini')
                                        ->schema([
                                            Forms\Components\CheckboxList::make('addons')
                                                ->label('')
                                                ->options(function (Forms\Get $get) {
                                                    $produkId = $get('produk_id');
                                                    if (!$produkId) return [];
                                                    
                                                    $addons = ProdukProses::where('produk_id', $produkId)
                                                        ->where('produk_proses_kategori_id', ProdukProsesKategori::finishingId())
                                                        ->whereNotNull('harga')
                                                        ->get();
                                                    
                                                    if ($addons->isEmpty()) return [];
                                                    
                                                    return $addons->mapWithKeys(function ($pp) {
                                                        return [$pp->id => "✨ {$pp->nama} - " . formatRupiah($pp->harga) . "/pcs"];
                                                    });
                                                })
                                                ->columns(2)
                                                ->live()
                                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                                                    $customerId = $get('../../../../customer_id') ?? $get('../../customer_id');
                                                    if (!$customerId) return;
                                                    
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
                                                }),
                                        ])
                                        ->compact()
                                        ->collapsible()
                                        ->visible(function (Forms\Get $get) {
                                            $produkId = $get('produk_id');
                                            if (!$produkId) return false;
                                            
                                            return ProdukProses::where('produk_id', $produkId)
                                                ->where('produk_proses_kategori_id', ProdukProsesKategori::finishingId())
                                                ->whereNotNull('harga')
                                                ->exists();
                                        }),
                                    
                                    // ---- SECTION: Sample Approval ----
                                    Forms\Components\Section::make('🔍 Sample Approval')
                                        ->description('Proses yang memerlukan persetujuan sample dari customer')
                                        ->schema([
                                            Forms\Components\CheckboxList::make('proses_perlu_sample_approval')
                                                ->label('')
                                                ->options(function (Forms\Get $get) {
                                                    $produkId = $get('produk_id');
                                                    $addons = $get('addons') ?? [];
                                                    
                                                    if (!$produkId) return [];
                                                    
                                                    $options = [];
                                                    
                                                    // Proses Produksi yang mengurangi bahan
                                                    $produksiProses = ProdukProses::where('produk_id', $produkId)
                                                        ->where('produk_proses_kategori_id', ProdukProsesKategori::produksiId())
                                                        ->where('apakah_mengurangi_bahan', true)
                                                        ->get();
                                                    
                                                    foreach ($produksiProses as $pp) {
                                                        $options[$pp->id] = "🔧 [Produksi] {$pp->nama}";
                                                    }
                                                    
                                                    // Addon/Finishing yang dipilih dan mengurangi bahan
                                                    if (!empty($addons) && is_array($addons)) {
                                                        foreach ($addons as $addonId) {
                                                            $addon = ProdukProses::find($addonId);
                                                            if ($addon && $addon->apakah_mengurangi_bahan) {
                                                                $options[$addon->id] = "✨ [Finishing] {$addon->nama}";
                                                            }
                                                        }
                                                    }
                                                    
                                                    return $options;
                                                })
                                                ->columns(2),
                                        ])
                                        ->compact()
                                        ->collapsible()
                                        ->collapsed()
                                        ->visible(function (Forms\Get $get) {
                                            $produkId = $get('produk_id');
                                            if (!$produkId) return false;
                                            
                                            return ProdukProses::where('produk_id', $produkId)
                                                ->where('apakah_mengurangi_bahan', true)
                                                ->whereIn('produk_proses_kategori_id', [ProdukProsesKategori::produksiId(), ProdukProsesKategori::finishingId()])
                                                ->exists();
                                        }),
                                    
                                    // ---- SECTION: Keterangan ----
                                    Forms\Components\Section::make('📋 Keterangan')
                                        ->schema([
                                            Forms\Components\Textarea::make('keterangan')
                                                ->label('')
                                                ->placeholder('Catatan khusus untuk produk ini...')
                                                ->rows(2)
                                                ->columnSpanFull(),
                                        ])
                                        ->compact()
                                        ->collapsible()
                                        ->collapsed(),
                                    
                                    Forms\Components\Hidden::make('total_harga_produk')
                                        ->default(0)
                                        ->dehydrated(),
                                ])
                                ->itemLabel(fn (array $state): ?string => 
                                    isset($state['judul_pesanan']) && !empty($state['judul_pesanan']) 
                                        ? $state['judul_pesanan'] 
                                        : (isset($state['produk_id']) ? Produk::find($state['produk_id'])?->nama : 'Produk Baru')
                                )
                                ->addActionLabel('+ Tambah Produk')
                                ->reorderable()
                                ->collapsible()
                                ->cloneable()
                                ->mutateRelationshipDataBeforeCreateUsing(function (array $data, Forms\Get $get): array {
                                    if (isset($data['design_id']) && $data['design_id'] === 'none') {
                                        $data['design_id'] = null;
                                    } elseif (isset($data['design_id']) && is_numeric($data['design_id'])) {
                                        $data['design_id'] = (int) $data['design_id'];
                                    }
                                    
                                    if (isset($data['addons']) && is_array($data['addons']) && !empty($data['addons'])) {
                                        $data['addons'] = array_map('intval', array_filter($data['addons']));
                                    } else {
                                        $data['addons'] = null;
                                    }
                                    
                                    if (isset($data['proses_perlu_sample_approval']) && is_array($data['proses_perlu_sample_approval']) && !empty($data['proses_perlu_sample_approval'])) {
                                        $data['proses_perlu_sample_approval'] = array_map('intval', array_filter($data['proses_perlu_sample_approval']));
                                    } else {
                                        $data['proses_perlu_sample_approval'] = null;
                                    }
                                    
                                    $customerId = $get('customer_id');
                                    $data = static::calculateTotalHargaProduk($data, $customerId);
                                    
                                    return $data;
                                })
                                ->mutateRelationshipDataBeforeSaveUsing(function (array $data, Forms\Get $get): array {
                                    if (isset($data['design_id']) && $data['design_id'] === 'none') {
                                        $data['design_id'] = null;
                                    } elseif (isset($data['design_id']) && is_numeric($data['design_id'])) {
                                        $data['design_id'] = (int) $data['design_id'];
                                    }
                                    
                                    if (isset($data['addons'])) {
                                        if (is_array($data['addons']) && !empty($data['addons'])) {
                                            $data['addons'] = array_map('intval', array_filter($data['addons']));
                                        } else {
                                            $data['addons'] = null;
                                        }
                                    } else {
                                        $data['addons'] = null;
                                    }
                                    
                                    if (isset($data['proses_perlu_sample_approval'])) {
                                        if (is_array($data['proses_perlu_sample_approval']) && !empty($data['proses_perlu_sample_approval'])) {
                                            $data['proses_perlu_sample_approval'] = array_map('intval', array_filter($data['proses_perlu_sample_approval']));
                                        } else {
                                            $data['proses_perlu_sample_approval'] = null;
                                        }
                                    } else {
                                        $data['proses_perlu_sample_approval'] = null;
                                    }
                                    
                                    $customerId = $get('customer_id');
                                    $data = static::calculateTotalHargaProduk($data, $customerId);
                                    
                                    return $data;
                                }),
                        ]),
                    
                    // ==================== STEP 3: RINGKASAN ====================
                    Forms\Components\Wizard\Step::make('Ringkasan')
                        ->icon('heroicon-o-calculator')
                        ->schema([
                            Forms\Components\Section::make('💰 Ringkasan Biaya')
                                ->schema([
                                    Forms\Components\Placeholder::make('detail_biaya')
                                        ->label('')
                                        ->content(function (Forms\Get $get) {
                                            return static::generateRingkasanBiaya($get);
                                        })
                                        ->columnSpanFull(),
                                ])
                                ->columnSpanFull(),
                            Forms\Components\Hidden::make('total_harga_kalkulasi')
                                ->afterStateHydrated(function (Forms\Set $set, Forms\Get $get) {
                                    $total = static::calculateTotalKeseluruhan($get);
                                    $set('total_harga_kalkulasi', $total);
                                })
                                ->dehydrated(),
                        ]),
                ])
                ->skippable(fn(string $operation) => $operation === 'edit')
                ->columnSpanFull()
            ]);
    }

    /**
     * Generate HTML untuk ringkasan biaya
     */
    protected static function generateRingkasanBiaya(Forms\Get $get): HtmlString
    {
        $produks = $get('produks');
        $customerId = $get('customer_id');
        
        if (!is_array($produks) || empty($produks) || !$customerId) {
            return new HtmlString('<div class="text-gray-500 text-center py-8">Pilih customer dan produk terlebih dahulu</div>');
        }
        
        $customer = Customer::find($customerId);
        if (!$customer) {
            return new HtmlString('<div class="text-red-500 text-center py-8">Customer tidak ditemukan</div>');
        }
        
        $html = '<div class="space-y-4">';
        $totalKeseluruhan = 0;
        $produkCounter = 0;
        
        foreach ($produks as $index => $produk) {
            if (!isset($produk['produk_id'])) continue;
            
            $produkModel = Produk::find($produk['produk_id']);
            if (!$produkModel) continue;
            
            $produkCounter++;
            
            // Calculate values
            $jumlah = static::parseNumber($produk['jumlah'] ?? 1);
            $panjang = static::parseNumber($produk['panjang'] ?? 1, true);
            $lebar = static::parseNumber($produk['lebar'] ?? 1, true);
            
            $hargaSatuan = static::getHargaSatuanByTiering($produk['produk_id'], $customer->customer_kategori_id, $jumlah);
            $subtotalProduk = $hargaSatuan * $jumlah * $panjang * $lebar;
            
            // Design
            $totalDesign = 0;
            $designNama = null;
            if (isset($produk['design_id']) && !empty($produk['design_id']) && $produk['design_id'] !== 'none') {
                $design = Proses::find($produk['design_id']);
                if ($design) {
                    $totalDesign = (float) ($design->harga_default ?? 0);
                    $designNama = $design->nama;
                }
            }
            
            // Addons
            $totalAddon = 0;
            $addonList = [];
            if (isset($produk['addons']) && is_array($produk['addons']) && !empty($produk['addons'])) {
                $addons = ProdukProses::whereIn('id', $produk['addons'])->whereNotNull('harga')->get();
                foreach ($addons as $addon) {
                    $addonHarga = (float) $addon->harga * $jumlah;
                    $addonList[] = ['nama' => $addon->nama, 'harga' => $addon->harga, 'total' => $addonHarga];
                    $totalAddon += $addonHarga;
                }
            }
            
            $totalProdukCalc = $subtotalProduk + $totalDesign + $totalAddon;
            
            // Harga minimal
            if ($produkModel->harga_minimal > 0 && $totalProdukCalc < $produkModel->harga_minimal) {
                $totalProdukFinal = (float) $produkModel->harga_minimal;
                $isMinimal = true;
            } else {
                $totalProdukFinal = $totalProdukCalc;
                $isMinimal = false;
            }
            
            // Build HTML for this product
            $html .= '<div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden shadow-sm">';
            
            // Header with judul pesanan
            if (isset($produk['judul_pesanan']) && !empty($produk['judul_pesanan'])) {
                $html .= '<div class="px-4 py-3 bg-gradient-to-r from-emerald-500 to-emerald-600">';
                $html .= '<span class="font-semibold text-white">📌 ' . e($produk['judul_pesanan']) . '</span>';
                $html .= '</div>';
            }
            
            $html .= '<div class="p-5">';
            
            // Product title
            $html .= '<div class="flex items-center gap-2 mb-4">';
            $html .= '<span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-900 text-emerald-700 dark:text-emerald-300 font-bold text-sm">' . $produkCounter . '</span>';
            $html .= '<div>';
            $html .= '<h4 class="font-bold text-gray-900 dark:text-white text-lg">' . e($produkModel->nama) . '</h4>';
            $html .= '<span class="text-xs text-gray-500 dark:text-gray-400">' . e($produkModel->kode) . '</span>';
            $html .= '</div>';
            $html .= '</div>';
            
            // Info specs in badge style
            $html .= '<div class="flex flex-wrap gap-2 mb-4">';
            $html .= '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">';
            $html .= '📦 ' . number_format($jumlah, 0, ',', '.') . ' pcs';
            $html .= '</span>';
            if ($produkModel->apakah_perlu_custom_dimensi) {
                $html .= '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300">';
                $html .= '📐 ' . static::formatDecimal($panjang) . ' × ' . static::formatDecimal($lebar) . ' m';
                $html .= '</span>';
            }
            $html .= '</div>';
            
            // Keterangan (if any)
            if (isset($produk['keterangan']) && !empty($produk['keterangan'])) {
                $html .= '<div class="mb-4 px-3 py-2 bg-amber-50 dark:bg-amber-900/30 border-l-4 border-amber-400 rounded-r text-sm">';
                $html .= '<span class="text-amber-700 dark:text-amber-300">📝 ' . nl2br(e($produk['keterangan'])) . '</span>';
                $html .= '</div>';
            }
            
            // Price breakdown table
            $html .= '<div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">';
            $html .= '<table class="w-full text-sm">';
            
            // Harga Produk
            $html .= '<tr class="bg-gray-50 dark:bg-gray-900/50">';
            $html .= '<td class="px-4 py-3 text-gray-600 dark:text-gray-400">';
            $html .= '<div class="font-medium text-gray-900 dark:text-white">🏷️ Harga Produk</div>';
            $html .= '<div class="text-xs text-gray-500 mt-1">' . formatRupiah($hargaSatuan) . ' × ' . number_format($jumlah, 0, ',', '.');
            if ($produkModel->apakah_perlu_custom_dimensi) {
                $html .= ' × ' . static::formatDecimal($panjang) . 'm × ' . static::formatDecimal($lebar) . 'm';
            }
            $html .= '</div>';
            $html .= '</td>';
            $html .= '<td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white whitespace-nowrap">' . formatRupiah($subtotalProduk) . '</td>';
            $html .= '</tr>';
            
            // Design (if any)
            if ($designNama) {
                $html .= '<tr class="border-t border-gray-200 dark:border-gray-700">';
                $html .= '<td class="px-4 py-3 text-gray-600 dark:text-gray-400">';
                $html .= '<div class="font-medium text-gray-900 dark:text-white">🎨 Design</div>';
                $html .= '<div class="text-xs text-gray-500 mt-1">' . e($designNama) . '</div>';
                $html .= '</td>';
                $html .= '<td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white whitespace-nowrap">' . formatRupiah($totalDesign) . '</td>';
                $html .= '</tr>';
            }
            
            // Addons (if any)
            if (!empty($addonList)) {
                $html .= '<tr class="border-t border-gray-200 dark:border-gray-700">';
                $html .= '<td class="px-4 py-3 text-gray-600 dark:text-gray-400">';
                $html .= '<div class="font-medium text-gray-900 dark:text-white">✨ Addon/Finishing</div>';
                $html .= '<div class="text-xs text-gray-500 mt-1 space-y-1">';
                foreach ($addonList as $addon) {
                    $html .= '<div class="flex justify-between items-center">';
                    $html .= '<span>• ' . e($addon['nama']) . ' (' . formatRupiah($addon['harga']) . ' × ' . number_format($jumlah, 0, ',', '.') . ')</span>';
                    $html .= '</div>';
                }
                $html .= '</div>';
                $html .= '</td>';
                $html .= '<td class="px-4 py-3 text-right font-semibold text-gray-900 dark:text-white whitespace-nowrap align-top">' . formatRupiah($totalAddon) . '</td>';
                $html .= '</tr>';
            }
            
            // Total row
            $html .= '<tr class="border-t-2 border-emerald-500 bg-emerald-50 dark:bg-emerald-900/30">';
            $html .= '<td class="px-4 py-3">';
            $html .= '<span class="font-bold text-emerald-700 dark:text-emerald-300">Total Produk #' . $produkCounter . '</span>';
            if ($isMinimal) {
                $html .= '<div class="text-xs text-amber-600 dark:text-amber-400 mt-1">⚡ Min. ' . formatRupiah($produkModel->harga_minimal) . ' (kalkulasi: ' . formatRupiah($totalProdukCalc) . ')</div>';
            }
            $html .= '</td>';
            $html .= '<td class="px-4 py-3 text-right">';
            $html .= '<span class="font-bold text-lg text-emerald-700 dark:text-emerald-300">' . formatRupiah($totalProdukFinal) . '</span>';
            $html .= '</td>';
            $html .= '</tr>';
            
            $html .= '</table>';
            $html .= '</div>';
            
            $html .= '</div>'; // End p-5
            $html .= '</div>'; // End card
            
            $totalKeseluruhan += $totalProdukFinal;
        }
        
        // Grand Total
        $html .= '<div class="bg-emerald-50 dark:bg-emerald-900/50 border-2 border-emerald-500 rounded-xl p-6 shadow-lg">';
        $html .= '<div class="flex flex-col md:flex-row justify-between items-center gap-4">';
        $html .= '<div class="flex items-center gap-3">';
        $html .= '<span class="text-4xl">💰</span>';
        $html .= '<div>';
        $html .= '<div class="text-sm font-medium text-emerald-700 dark:text-emerald-300">Total Keseluruhan</div>';
        $html .= '<div class="text-xs text-emerald-600 dark:text-emerald-400">' . $produkCounter . ' produk</div>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="text-3xl md:text-4xl font-bold text-emerald-700 dark:text-emerald-300">' . formatRupiah($totalKeseluruhan) . '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        return new HtmlString($html);
    }

    /**
     * Helper: Parse number from various formats
     */
    protected static function parseNumber($value, bool $allowDecimal = false): float
    {
        if ($value === null || $value === '') {
            return $allowDecimal ? 1.0 : 1;
        }
        
        if (is_string($value)) {
            $value = str_replace([',', ' '], '', $value);
        }
        
        $result = (float) $value;
        return $result <= 0 ? ($allowDecimal ? 1.0 : 1) : $result;
    }

    /**
     * Helper: Format decimal untuk display
     */
    protected static function formatDecimal(float $value): string
    {
        return floor($value) == $value ? number_format($value, 0, ',', '.') : number_format($value, 2, ',', '.');
    }

    /**
     * Calculate total keseluruhan
     */
    protected static function calculateTotalKeseluruhan(Forms\Get $get): int
    {
        $produks = $get('produks');
        $customerId = $get('customer_id');
        
        if (!is_array($produks) || empty($produks) || !$customerId) {
            return 0;
        }
        
        $customer = Customer::find($customerId);
        if (!$customer) {
            return 0;
        }
        
        $total = 0;
        
        foreach ($produks as $produk) {
            if (!isset($produk['produk_id'])) continue;
            
            $calculated = static::calculateTotalHargaProduk($produk, $customerId);
            $total += $calculated['total_harga_produk'] ?? 0;
        }
        
        return (int) round($total);
    }

    /**
     * Helper function untuk mendapatkan harga berdasarkan tiering
     */
    public static function getHargaSatuanByTiering(int $produkId, int $customerKategoriId, int $jumlahPesanan): float
    {
        $produkHargas = ProdukHarga::where('produk_id', $produkId)
            ->where('customer_kategori_id', $customerKategoriId)
            ->orderBy('jumlah_pesanan_minimal', 'asc')
            ->get();
        
        if ($produkHargas->isEmpty()) {
            return 0.0;
        }
        
        foreach ($produkHargas as $produkHarga) {
            if ($jumlahPesanan >= $produkHarga->jumlah_pesanan_minimal && 
                $jumlahPesanan <= $produkHarga->jumlah_pesanan_maksimal) {
                return (float) $produkHarga->harga;
            }
        }
        
        $lastTier = $produkHargas->last();
        if ($jumlahPesanan > $lastTier->jumlah_pesanan_maksimal) {
            return (float) $lastTier->harga;
        }
        
        $firstTier = $produkHargas->first();
        if ($jumlahPesanan < $firstTier->jumlah_pesanan_minimal) {
            return (float) $firstTier->harga;
        }
        
        return 0.0;
    }

    /**
     * Calculate total harga produk
     */
    protected static function calculateTotalHargaProduk(array $data, $customerId = null): array
    {
        if (!$customerId || !isset($data['produk_id'])) {
            $data['total_harga_produk'] = 0;
            return $data;
        }
        
        $customer = Customer::find($customerId);
        if (!$customer) {
            $data['total_harga_produk'] = 0;
            return $data;
        }
        
        $jumlah = static::parseNumber($data['jumlah'] ?? 1);
        $panjang = static::parseNumber($data['panjang'] ?? 1, true);
        $lebar = static::parseNumber($data['lebar'] ?? 1, true);
        
        $hargaSatuan = static::getHargaSatuanByTiering(
            $data['produk_id'],
            $customer->customer_kategori_id,
            (int) $jumlah
        );
        
        $totalProduk = $hargaSatuan * $jumlah * $panjang * $lebar;
        
        // Design (from global Proses table)
        if (isset($data['design_id']) && !empty($data['design_id']) && $data['design_id'] !== 'none') {
            $design = Proses::find($data['design_id']);
            if ($design) {
                $totalProduk += (float) ($design->harga_default ?? 0);
            }
        }
        
        // Addons (from ProdukProses)
        if (isset($data['addons']) && is_array($data['addons']) && !empty($data['addons'])) {
            $addonsArray = array_filter(array_map('intval', $data['addons']), fn($id) => $id > 0);
            
            if (!empty($addonsArray)) {
                $totalAddon = ProdukProses::whereIn('id', $addonsArray)
                    ->where('produk_proses_kategori_id', ProdukProsesKategori::finishingId())
                    ->whereNotNull('harga')
                    ->sum('harga');
                $totalProduk += (float) ($totalAddon * $jumlah);
            }
        }

        // Harga Minimal
        $produkModel = Produk::find($data['produk_id']);
        if ($produkModel && $produkModel->harga_minimal > 0) {
            if ($totalProduk < $produkModel->harga_minimal) {
                $totalProduk = (float) $produkModel->harga_minimal;
            }
        }
        
        $data['total_harga_produk'] = (int) round($totalProduk);
        
        return $data;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(function () {
                $query = TransaksiKalkulasi::query();
                
                if (Auth::user()->can('lihat_seluruhnya_deskprint')) {
                    return $query;
                }
                
                if (Auth::user()->can('lihat_sebagian_deskprint')) {
                    return $query->where('created_by', Auth::id());
                }
                
                return $query->whereRaw('1 = 0');
            })
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
                    ->modalCancelActionLabel('Tutup')
                    ->visible(fn () => Auth::user()->can('view_deskprint')),
                Tables\Actions\EditAction::make()
                    ->modalHeading('Deskprint')
                    ->visible(fn(TransaksiKalkulasi $record) => $record->transaksis->isEmpty() && Auth::user()->can('update_deskprint')),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn(TransaksiKalkulasi $record) => $record->transaksis->isEmpty() && Auth::user()->can('delete_deskprint')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()->can('delete_any_deskprint')),
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
