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
                                ->schema([
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
                                                ->afterStateUpdated(function (Forms\Set $set) {
                                                    // Reset design dan addon ketika produk berubah
                                                    $set('design_id', null);
                                                    $set('addons', []);
                                                }),
                                            Forms\Components\TextInput::make('jumlah')
                                                ->label('Jumlah')
                                                ->required()
                                                ->numeric()
                                                ->minValue(1),
                                            Forms\Components\TextInput::make('panjang')
                                                ->label('Panjang')
                                                ->required()
                                                ->numeric()
                                                ->minValue(1)
                                                ->visible(fn (Forms\Get $get) => $get('produk_id') ? Produk::find($get('produk_id'))?->apakah_perlu_custom_dimensi : false),
                                            Forms\Components\TextInput::make('lebar')
                                                ->label('Lebar')
                                                ->required()
                                                ->numeric()
                                                ->minValue(1)
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
                                        ->afterStateUpdated(function (Forms\Set $set, $state) {
                                            if ($state === 'none') {
                                                $set('design_id', null);
                                            }
                                        })
                                        ->dehydrated(fn ($state) => $state !== 'none' && $state !== null)
                                        ->live()
                                        ->visible(fn (Forms\Get $get) => !empty($get('produk_id')))
                                        ->helperText('Pilih design jika diperlukan. Pilih "Tidak Pakai Design" jika customer sudah punya design sendiri.')
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
                                            
                                            $html = '<div style="font-family: sans-serif;">';
                                            $totalKeseluruhan = 0;
                                            $produkCounter = 0; // Counter untuk numbering produk yang benar
                                            
                                            foreach ($produks as $index => $produk) {
                                                if (!isset($produk['produk_id'])) continue;
                                                
                                                $produkModel = Produk::find($produk['produk_id']);
                                                if (!$produkModel) continue;
                                                
                                                $produkCounter++; // Increment counter untuk setiap produk valid
                                                
                                                // Ambil harga satuan berdasarkan kategori customer
                                                $produkHarga = ProdukHarga::where('produk_id', $produk['produk_id'])
                                                    ->where('customer_kategori_id', $customer->customer_kategori_id)
                                                    ->first();
                                                
                                                $hargaSatuan = $produkHarga ? (float) $produkHarga->harga : 0.0;
                                                
                                                // Parse jumlah - handle string dengan format atau numeric
                                                $jumlahRaw = $produk['jumlah'] ?? 1;
                                                if (is_string($jumlahRaw)) {
                                                    $jumlah = (float) str_replace([',', ' ', '.'], '', $jumlahRaw);
                                                } else {
                                                    $jumlah = (float) $jumlahRaw;
                                                }
                                                if ($jumlah <= 0) $jumlah = 1;
                                                
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
                                                
                                                // HITUNG TOTAL PRODUK
                                                // Formula: Harga Satuan × Jumlah × Panjang × Lebar
                                                // Jika tidak ada dimensi custom (panjang/lebar null), maka panjang = 1 dan lebar = 1
                                                $totalProduk = $hargaSatuan * $jumlah * $panjang * $lebar;
                                                
                                                $html .= '<div style="border: 1px solid #e5e7eb; padding: 16px; margin-bottom: 16px; border-radius: 8px;">';
                                                $html .= '<h4 style="margin: 0 0 12px 0; color: #374151;">Produk #' . $produkCounter . ': [' . $produkModel->kode . '] - ' . $produkModel->nama . '</h4>';
                                                $html .= '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 12px;">';
                                                $html .= '<div><strong>Harga Satuan:</strong> ' . formatRupiah($hargaSatuan) . '</div>';
                                                $html .= '<div><strong>Jumlah:</strong> ' . $jumlah . '</div>';
                                                
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
                                                $html .= '<div style="font-size: 12px; color: #6b7280; margin-top: 4px;">(' . formatRupiah($hargaSatuan) . ' × ' . $jumlah . ' × ' . $panjangDisplay . ' × ' . $lebarDisplay . ')</div>';
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
                                                // Formula: Subtotal Produk + Design + Total Addon
                                                $totalProdukFinal = $totalProduk + $totalDesign + $totalAddon;
                                                
                                                $html .= '<div style="margin-top: 12px; padding-top: 8px; border-top: 2px solid #3b82f6; font-weight: bold; color: #1d4ed8;">';
                                                $html .= 'Total Produk #' . $produkCounter . ': ' . formatRupiah($totalProdukFinal);
                                                $html .= '</div>';
                                                $html .= '</div>';
                                                
                                                $totalKeseluruhan += $totalProdukFinal;
                                            }
                                            
                                            $html .= '<div style="background: #f8fafc; border: 2px solid #3b82f6; padding: 16px; border-radius: 8px; text-align: center;">';
                                            $html .= '<h3 style="margin: 0; color: #1d4ed8; font-size: 20px;">TOTAL KESELURUHAN: ' . formatRupiah($totalKeseluruhan) . '</h3>';
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
                                        
                                        // Ambil harga satuan berdasarkan kategori customer
                                        $produkHarga = ProdukHarga::where('produk_id', $produk['produk_id'])
                                            ->where('customer_kategori_id', $customer->customer_kategori_id)
                                            ->first();
                                        
                                        $hargaSatuan = $produkHarga ? (float) $produkHarga->harga : 0.0;
                                        
                                        // Parse jumlah - handle string dengan format atau numeric
                                        $jumlahRaw = $produk['jumlah'] ?? 1;
                                        if (is_string($jumlahRaw)) {
                                            $jumlah = (float) str_replace([',', ' ', '.'], '', $jumlahRaw);
                                        } else {
                                            $jumlah = (float) $jumlahRaw;
                                        }
                                        if ($jumlah <= 0) $jumlah = 1;
                                        
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
                                        $totalProduk = $hargaSatuan * $jumlah * $panjang * $lebar;
                                        
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
        
        // Ambil harga satuan berdasarkan kategori customer
        $produkHarga = ProdukHarga::where('produk_id', $data['produk_id'])
            ->where('customer_kategori_id', $customer->customer_kategori_id)
            ->first();
        
        $hargaSatuan = $produkHarga ? (float) $produkHarga->harga : 0.0;
        
        // Parse jumlah
        $jumlahRaw = $data['jumlah'] ?? 1;
        if (is_string($jumlahRaw)) {
            $jumlah = (float) str_replace([',', ' ', '.'], '', $jumlahRaw);
        } else {
            $jumlah = (float) $jumlahRaw;
        }
        if ($jumlah <= 0) $jumlah = 1;
        
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
        $totalProduk = $hargaSatuan * $jumlah * $panjang * $lebar;
        
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
            // Ambil harga satuan berdasarkan kategori customer
            $produkHarga = ProdukHarga::where('produk_id', $produk->produk_id)
                ->where('customer_kategori_id', $customer->customer_kategori_id)
                ->first();

            $hargaSatuan = $produkHarga ? (float) $produkHarga->harga : 0.0;

            // Parse nilai
            $jumlah = (float) ($produk->jumlah ?? 1);
            if ($jumlah <= 0) $jumlah = 1;

            $panjang = $produk->panjang ? (float) $produk->panjang : 1.0;
            if ($panjang <= 0) $panjang = 1.0;

            $lebar = $produk->lebar ? (float) $produk->lebar : 1.0;
            if ($lebar <= 0) $lebar = 1.0;

            // Hitung total produk
            $totalProduk = $hargaSatuan * $jumlah * $panjang * $lebar;

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
