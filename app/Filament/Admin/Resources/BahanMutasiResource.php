<?php

namespace App\Filament\Admin\Resources;

use App\Models\PO;
use Filament\Forms;
use Filament\Tables;
use App\Models\Bahan;
use App\Models\Satuan;
use Filament\Forms\Get;
use App\Models\Supplier;
use Filament\Forms\Form;
use Filament\Tables\Table;
use App\Models\BahanMutasi;
use Filament\Support\RawJs;
use Filament\Resources\Resource;
use App\Enums\BahanMutasi\TipeEnum;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\BahanMutasiFaktur\StatusPembayaranEnum;
use App\Filament\Admin\Resources\BahanMutasiResource\Pages;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use App\Filament\Admin\Resources\BahanMutasiResource\RelationManagers;
use App\Models\BahanMutasiFaktur;
use App\Models\PencatatanKeuangan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class BahanMutasiResource extends Resource
{
    protected static ?string $model = BahanMutasi::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path-rounded-square';
    protected static ?string $navigationLabel = 'Bahan Mutasi';

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()->can('view_bahan::mutasi') && Auth::user()->can('view_any_bahan::mutasi');
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('kode')
                    ->label('Kode Mutasi')
                    ->required()
                    ->maxLength(255)
                    ->helperText(customableState())
                    ->default(fn ($record) => $record?->kode ?? generateKode('BM'))
                    ->columnSpanFull(),
                Forms\Components\Select::make('tipe')
                    ->label('Tipe Mutasi')    
                    ->required()
                    ->options(TipeEnum::class)
                    ->columnSpanFull()
                    ->live(),
                Forms\Components\Toggle::make('is_po')
                    ->label('Apakah dari PO?')
                    ->required()
                    ->inline(false)
                    ->live()
                    ->afterStateUpdated(function (Forms\Set $set, $state) {
                        $set('po_id', null);
                        $set('supplier_id', null);
                        if ($state === false) {
                            // Non-PO: set 1 empty item
                            $set('bahanMutasiDetails', [[]]);
                        } else {
                            // PO: set empty array, akan diisi saat pilih PO
                            $set('bahanMutasiDetails', []);
                        }
                        $set('total_harga_faktur', 0);
                    })
                    ->visible(fn(Forms\Get $get) => $get('tipe') == TipeEnum::MASUK->value),
                Forms\Components\Select::make('po_id')
                    ->label('Pilih PO')
                    ->options(function (Get $get) {
                        if (! $get('is_po')) {
                            return [];
                        }
                        return PO::query()
                            ->whereIsApproved(true)
                            ->whereDoesntHave('bahanMutasiFaktur')
                            ->with('supplier')
                            ->get()
                            ->mapWithKeys(function ($po) {
                                return [$po->id => "{$po->kode} - {$po->supplier->nama_perusahaan}"];
                            });
                    })
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, $state) {
                        if ($state) {
                            $po = PO::with('bahanPO.bahan')->find($state);
                            if ($po) {
                                $set('supplier_id', $po->supplier_id);
                                
                                // Isi repeater dengan data dari bahanPO
                                $bahanMutasiDetails = [];
                                foreach ($po->bahanPO as $bahanPO) {
                                    $bahan = $bahanPO->bahan;
                                    $satuanTerbesar = $bahan ? Satuan::find($bahan->satuan_terbesar_id) : null;
                                    $satuanTerkecil = $bahan ? Satuan::find($bahan->satuan_terkecil_id) : null;
                                    
                                    $bahanMutasiDetails[] = [
                                        'bahan_id' => $bahanPO->bahan_id,
                                        'jumlah_satuan_terbesar' => $bahanPO->jumlah_terbesar,
                                        'jumlah_satuan_terkecil' => $bahanPO->jumlah_terkecil,
                                        'total_harga_mutasi' => $bahanPO->total_harga_po,
                                        'harga_satuan_terbesar' => $bahanPO->harga_satuan_terbesar,
                                        'harga_satuan_terkecil' => $bahanPO->harga_satuan_terkecil,
                                        'satuan_terbesar_id' => $satuanTerbesar ? $satuanTerbesar->nama : '',
                                        'satuan_terkecil_id' => $satuanTerkecil ? $satuanTerkecil->nama : '',
                                        'input_via' => 'Nominal', // Set default ke "Nominal" saat memilih PO
                                    ];
                                }
                                
                                $set('bahanMutasiDetails', $bahanMutasiDetails);
                                
                                // Update total harga faktur
                                $total = $po->bahanPO->sum('total_harga_po');
                                $set('total_harga_faktur', $total);
                            }
                        } else {
                            // PO dibatalkan: kosongkan repeater
                            $set('bahanMutasiDetails', []);
                            $set('total_harga_faktur', 0);
                            $set('supplier_id', null);
                        }
                    })
                    ->visible(fn(Forms\Get $get) => $get('tipe') == TipeEnum::MASUK->value && $get('is_po') == true)
                    ->required(fn(Forms\Get $get) => $get('tipe') == TipeEnum::MASUK->value && $get('is_po') == true),
                Forms\Components\Select::make('supplier_id')
                    ->label('Supplier')
                    ->columnSpanFull()
                    ->options(function(Get $get) {
                        // Jika dari PO, tampilkan supplier dari PO yang dipilih
                        if ($get('is_po') === true && $get('po_id')) {
                            $po = PO::with('supplier')->find($get('po_id'));
                            if ($po && $po->supplier) {
                                return [$po->supplier->id => "SPL-{$po->supplier->kode} - {$po->supplier->nama_perusahaan}"];
                            }
                            return [];
                        }
                        
                        // Jika non-PO, tampilkan supplier dengan filter is_po = false
                        $supplier = Supplier::query();
                        if($get('tipe') == TipeEnum::MASUK->value && $get('is_po') == false) {
                            $supplier->where('is_po', false);
                        }
                        return $supplier->get()->mapWithKeys(function($item) {
                            return [$item->id => "SPL-{$item->kode} - {$item->nama_perusahaan}"];
                        });
                    })
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required(fn(Forms\Get $get) => $get('tipe') == TipeEnum::MASUK->value)
                    ->visible(fn(Forms\Get $get) => $get('tipe') == TipeEnum::MASUK->value)
                    ->helperText(function(Forms\Get $get) {
                        if ($get('is_po') === true && $get('po_id')) {
                            return 'Supplier otomatis terisi dari PO yang dipilih';
                        }
                        if ($get('is_po') === false) {
                            return 'Pilih supplier untuk mutasi non-PO';
                        }
                        return '';
                    }),
                Forms\Components\Repeater::make('bahanMutasiDetails')
                    ->label('Detail Mutasi')
                    ->columnSpanFull()
                    ->visible(fn(Forms\Get $get) => $get('tipe'))
                    ->defaultItems(fn(Forms\Get $get) => ($get('is_po') === true) ? 0 : 1)
                    ->live()
                    ->schema([
                        Forms\Components\Select::make('bahan_id')
                            ->label('Bahan')
                            ->options(function (Forms\Get $get) {
                                // Get all selected bahan_id from other items in the repeater
                                $items = $get('../../bahanMutasiDetails') ?? [];
                                $excludedIds = [];
                                $currentBahanId = $get('bahan_id');
                                
                                if (is_array($items)) {
                                    foreach ($items as $item) {
                                        if (isset($item['bahan_id']) && $item['bahan_id'] && $item['bahan_id'] != $currentBahanId) {
                                            $excludedIds[] = $item['bahan_id'];
                                        }
                                    }
                                }
                                
                                // Get all bahan and exclude already selected ones
                                $query = Bahan::query();
                                if (!empty($excludedIds)) {
                                    $query->whereNotIn('id', $excludedIds);
                                }
                                
                                return $query->get()->mapWithKeys(function($item) {
                                    return [$item->id => "{$item->kode} - {$item->nama}"];
                                });
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateHydrated(function (Forms\Set $set, $state) {
                                static::fillSatuanFields($set, $state);
                            })
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                static::fillSatuanFields($set, $state);
                            })
                            ->columnSpanFull(),
                        // Jika mutasi keluar
                        Forms\Components\ToggleButtons::make('input_via_keluar')
                            ->label('Input Via')
                            ->options([
                                'Nominal' => 'Nominal',
                                'Dimensi' => 'Dimensi',
                            ])
                            ->default('Nominal')
                            ->grouped()
                            ->required()
                            ->live()
                            ->columnSpanFull()
                            ->visible(fn(Get $get) => $get('../../tipe') == TipeEnum::KELUAR->value),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('panjang_keluar')
                                    ->label('Panjang (p)')
                                    ->required()
                                    ->numeric()
                                    ->step(0.01)
                                    ->live()
                                    ->visible(fn (Forms\Get $get) => $get('input_via_keluar') === 'Dimensi')
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                        $parse = fn ($value) => (float) str_replace([',', ' '], '', (string) ($value ?? 0));
                                        $panjang = $parse($get('panjang_keluar'));
                                        $lebar = $parse($get('lebar_keluar'));
                                        
                                        if ($panjang > 0 && $lebar > 0) {
                                            $luas = $panjang * $lebar;
                                            $set('jumlah_mutasi', (string) $luas);
                                        }
                                    }),
                                Forms\Components\TextInput::make('lebar_keluar')
                                    ->label('Lebar (l)')
                                    ->required()
                                    ->numeric()
                                    ->step(0.01)
                                    ->live()
                                    ->visible(fn (Forms\Get $get) => $get('input_via_keluar') === 'Dimensi')
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                        $parse = fn ($value) => (float) str_replace([',', ' '], '', (string) ($value ?? 0));
                                        $panjang = $parse($get('panjang_keluar'));
                                        $lebar = $parse($get('lebar_keluar'));
                                        
                                        if ($panjang > 0 && $lebar > 0) {
                                            $luas = $panjang * $lebar;
                                            $set('jumlah_mutasi', (string) $luas);
                                        }
                                    }),
                            ])
                            ->visible(fn (Forms\Get $get) => $get('../../tipe') == TipeEnum::KELUAR->value && $get('input_via_keluar') === 'Dimensi')
                            ->columnSpanFull(),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('jumlah_mutasi')
                                    ->label('Jumlah Mutasi')
                                    ->required()
                                    ->numeric()
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->live()
                                    ->readOnly(fn (Forms\Get $get) => $get('input_via_keluar') === 'Dimensi'),
                                Forms\Components\TextInput::make('satuan_terkecil_id')
                                    ->label('Satuan')
                                    ->required()
                                    ->readOnly()
                                    ->dehydrated(false),
                            ])
                            ->visible(fn(Get $get) => $get('../../tipe') == TipeEnum::KELUAR->value),
                        // Jika mutasi masuk
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('jumlah_satuan_terbesar')
                                    ->label('Jumlah Beli')
                                    ->required()
                                    ->numeric()
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                        $parse = fn ($value) => (float) str_replace([',', ' '], '', (string) ($value ?? 0));
                                        $jumlah = $parse($get('jumlah_satuan_terbesar'));
                                        $total = $parse($get('total_harga_mutasi'));
                                        $harga = $jumlah > 0 ? round($total / $jumlah) : 0;
                                        $set('harga_satuan_terbesar', (string) $harga);
                                        
                                        // Jika input via Dimensi, recalculate jumlah_satuan_terkecil
                                        if ($get('input_via') === 'Dimensi') {
                                            $panjang = $parse($get('panjang'));
                                            $lebar = $parse($get('lebar'));
                                            
                                            if ($panjang > 0 && $lebar > 0) {
                                                // Hanya p * l untuk jumlah_terkecil (luas per satuan terbesar)
                                                $luas = $panjang * $lebar;
                                                $set('jumlah_satuan_terkecil', (string) $luas);
                                                
                                                // Update harga satuan terkecil (perlu dikali jumlah_terbesar untuk total)
                                                $totalTerkecil = $luas * $jumlah;
                                                $hargaTerkecil = $totalTerkecil > 0 ? round($total / $totalTerkecil) : 0;
                                                $set('harga_satuan_terkecil', (string) $hargaTerkecil);
                                            }
                                        }
                                    }),
                                Forms\Components\TextInput::make('satuan_terbesar_id')
                                    ->label('Satuan Terbesar')
                                    ->required()
                                    ->readOnly()
                                    ->dehydrated(false),
                                Forms\Components\ToggleButtons::make('input_via')
                                    ->label('Input Via')
                                    ->options([
                                        'Nominal' => 'Nominal',
                                        'Dimensi' => 'Dimensi',
                                    ])
                                    ->default('Nominal')
                                    ->grouped()
                                    ->required()
                                    ->live()
                                    ->columnSpanFull()
                                    ->afterStateHydrated(function (Forms\Set $set, Forms\Get $get, $state) {
                                        // Jika tidak ada data dimensi (panjang/lebar kosong), set ke "Nominal"
                                        $panjang = $get('panjang');
                                        $lebar = $get('lebar');
                                        if (empty($panjang) && empty($lebar)) {
                                            $set('input_via', 'Nominal');
                                        }
                                    }),
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('panjang')
                                            ->label('Panjang (p)')
                                            ->required()
                                            ->numeric()
                                            ->step(0.01)
                                            ->live()
                                            ->visible(fn (Forms\Get $get) => $get('input_via') === 'Dimensi')
                                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                                $parse = fn ($value) => (float) str_replace([',', ' '], '', (string) ($value ?? 0));
                                                $panjang = $parse($get('panjang'));
                                                $lebar = $parse($get('lebar'));
                                                
                                                if ($panjang > 0 && $lebar > 0) {
                                                    // Hanya p * l untuk jumlah_terkecil (luas per satuan terbesar)
                                                    $luas = $panjang * $lebar;
                                                    $set('jumlah_satuan_terkecil', (string) $luas);
                                                    
                                                    // Update harga satuan terkecil (perlu dikali jumlah_terbesar untuk total)
                                                    $jumlahTerbesar = $parse($get('jumlah_satuan_terbesar'));
                                                    $totalTerkecil = $luas * $jumlahTerbesar;
                                                    $total = $parse($get('total_harga_mutasi'));
                                                    $harga = $totalTerkecil > 0 ? round($total / $totalTerkecil) : 0;
                                                    $set('harga_satuan_terkecil', (string) $harga);
                                                }
                                            }),
                                        Forms\Components\TextInput::make('lebar')
                                            ->label('Lebar (l)')
                                            ->required()
                                            ->numeric()
                                            ->step(0.01)
                                            ->live()
                                            ->visible(fn (Forms\Get $get) => $get('input_via') === 'Dimensi')
                                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                                $parse = fn ($value) => (float) str_replace([',', ' '], '', (string) ($value ?? 0));
                                                $panjang = $parse($get('panjang'));
                                                $lebar = $parse($get('lebar'));
                                                
                                                if ($panjang > 0 && $lebar > 0) {
                                                    // Hanya p * l untuk jumlah_terkecil (luas per satuan terbesar)
                                                    $luas = $panjang * $lebar;
                                                    $set('jumlah_satuan_terkecil', (string) $luas);
                                                    
                                                    // Update harga satuan terkecil (perlu dikali jumlah_terbesar untuk total)
                                                    $jumlahTerbesar = $parse($get('jumlah_satuan_terbesar'));
                                                    $totalTerkecil = $luas * $jumlahTerbesar;
                                                    $total = $parse($get('total_harga_mutasi'));
                                                    $harga = $totalTerkecil > 0 ? round($total / $totalTerkecil) : 0;
                                                    $set('harga_satuan_terkecil', (string) $harga);
                                                }
                                            }),
                                    ])
                                    ->visible(fn (Forms\Get $get) => $get('input_via') === 'Dimensi')
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('jumlah_satuan_terkecil')
                                    ->label(function (Forms\Get $get) {
                                        $bahanId = $get('bahan_id');
                                        if ($bahanId) {
                                            $bahan = Bahan::find($bahanId);
                                            if ($bahan && $bahan->satuanTerbesar) {
                                                return 'Isi/' . $bahan->satuanTerbesar->nama;
                                            }
                                        }
                                        return 'Jumlah Terkecil';
                                    })
                                    ->required()
                                    ->numeric()
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->live()
                                    ->readOnly(fn (Forms\Get $get) => $get('input_via') === 'Dimensi')
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                        $parse = fn ($value) => (float) str_replace([',', ' '], '', (string) ($value ?? 0));
                                        $jumlah = $parse($get('jumlah_satuan_terkecil'));
                                        $total = $parse($get('total_harga_mutasi'));
                                        $harga = $jumlah > 0 ? round($total / $jumlah) : 0;
                                        $set('harga_satuan_terkecil', (string) $harga);
                                    }),
                                Forms\Components\TextInput::make('satuan_terkecil_id')
                                    ->label('Satuan Terkecil')
                                    ->required()
                                    ->readOnly()
                                    ->dehydrated(false),
                            ])
                            ->visible(fn(Get $get) => $get('../../tipe') == TipeEnum::MASUK->value),
                        Forms\Components\TextInput::make('total_harga_mutasi')
                            ->label('Total Harga Mutasi')
                            ->prefix('Rp')
                            ->suffix(',-')
                            ->required()
                            ->numeric()
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->columnSpanFull()
                            ->live(onBlur: true)
                            ->visible(fn(Get $get) => $get('../../tipe') == TipeEnum::MASUK->value)
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                $parse = fn ($value) => (float) str_replace([',', ' '], '', (string) ($value ?? 0));
                                $jumlahTerbesar = $parse($get('jumlah_satuan_terbesar'));
                                $jumlahTerkecil = $parse($get('jumlah_satuan_terkecil'));
                                $total = $parse($get('total_harga_mutasi'));

                                $hargaTerbesar = $jumlahTerbesar > 0 ? round($total / $jumlahTerbesar) : 0;
                                $hargaTerkecil = $jumlahTerkecil > 0 ? round($total / $jumlahTerkecil) : 0;

                                $set('harga_satuan_terbesar', (string) $hargaTerbesar);
                                $set('harga_satuan_terkecil', (string) $hargaTerkecil);
                                
                                // Jika input via Dimensi, pastikan jumlah_terkecil sudah terhitung
                                if ($get('input_via') === 'Dimensi') {
                                    $panjang = $parse($get('panjang'));
                                    $lebar = $parse($get('lebar'));
                                    
                                    if ($panjang > 0 && $lebar > 0) {
                                        // Hanya p * l untuk jumlah_terkecil (luas per satuan terbesar)
                                        $luas = $panjang * $lebar;
                                        $set('jumlah_satuan_terkecil', (string) $luas);
                                        
                                        // Recalculate harga satuan terkecil (perlu dikali jumlah_terbesar untuk total)
                                        $totalTerkecil = $luas * $jumlahTerbesar;
                                        $hargaTerkecil = $totalTerkecil > 0 ? round($total / $totalTerkecil) : 0;
                                        $set('harga_satuan_terkecil', (string) $hargaTerkecil);
                                    }
                                }
                            }),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('harga_satuan_terbesar')
                                    ->label('Harga Satuan Terbesar')
                                    ->required()
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->suffix(',-')
                                    ->stripCharacters(',')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->helperText(customableState()),
                                Forms\Components\TextInput::make('harga_satuan_terkecil')
                                    ->label('Harga Satuan Terkecil')
                                    ->required()
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->suffix(',-')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->helperText(customableState()),
                            ])
                            ->visible(fn(Get $get) => $get('../../tipe') == TipeEnum::MASUK->value),
                ])
                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                    $parse = fn ($value) => (float) str_replace([',', ' '], '', (string) ($value ?? 0));
                    $items = $get('bahanMutasiDetails') ?? [];
                    $total = 0;
                    if (is_array($items)) {
                        foreach ($items as $item) {
                            $total += $parse($item['total_harga_mutasi'] ?? 0);
                        }
                    }
                    $set('total_harga_faktur', $total);
                }),
                Forms\Components\Hidden::make('total_harga_faktur')
                    ->default(0)
                    ->dehydrated(),
                Forms\Components\Placeholder::make('total_harga_faktur_display')
                    ->visible(fn(Get $get) => $get('tipe') == TipeEnum::MASUK->value)
                    ->label('Total Harga Faktur')
                    ->content(function (Forms\Get $get) {
                        $parse = fn ($value) => (float) str_replace([',', ' '], '', (string) ($value ?? 0));
                        $total = (float) $get('total_harga_faktur');
                        
                        if ($total === 0) {
                            $items = $get('bahanMutasiDetails') ?? [];
                            if (is_array($items)) {
                                foreach ($items as $item) {
                                    $total += $parse($item['total_harga_mutasi'] ?? 0);
                                }
                            }
                        }
                        
                        return formatRupiah($total);
                    })
                    ->live()
                    ->columnSpanFull(),
                // Field untuk faktur (hanya muncul jika tipe MASUK)
                Forms\Components\Section::make('Informasi Faktur')
                    ->schema([
                        Forms\Components\FileUpload::make('bukti_faktur')
                            ->label('Bukti Faktur')
                            ->image()
                            ->optimize('webp')
                            ->required()
                            ->maxSize(2048)
                            ->directory('temp/bukti-faktur')
                            ->disk('public')
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])
                            ->helperText('Upload bukti faktur (maks 2MB)')
                            ->columnSpanFull(),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('total_diskon')
                                    ->label('Total Diskon')
                                    ->prefix('Rp')
                                    ->suffix(',-')
                                    ->numeric()
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->default(0),
                                Forms\Components\Select::make('status_pembayaran')
                                    ->label('Status Pembayaran')
                                    ->options(StatusPembayaranEnum::class)
                                    ->required()
                                    ->live()
                                    ->default(StatusPembayaranEnum::LUNAS->value),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('tanggal_pembayaran')
                                    ->label('Tanggal Pembayaran')
                                    ->required(function (Forms\Get $get) {
                                        $status = $get('status_pembayaran');
                                        return $status === StatusPembayaranEnum::LUNAS->value;
                                    })
                                    ->visible(function (Forms\Get $get) {
                                        $status = $get('status_pembayaran');
                                        return $status === StatusPembayaranEnum::LUNAS->value;
                                    })
                                    ->default(now()),
                                Forms\Components\Select::make('metode_pembayaran')
                                    ->label('Metode Pembayaran')
                                    ->options(function (Forms\Get $get) {
                                        $supplierId = $get('supplier_id');
                                        return getSupplierPaymentMethods($supplierId);
                                    })
                                    ->searchable()
                                    ->required(function (Forms\Get $get) {
                                        $status = $get('status_pembayaran');
                                        return $status === StatusPembayaranEnum::LUNAS->value;
                                    })
                                    ->visible(function (Forms\Get $get) {
                                        $status = $get('status_pembayaran');
                                        return $status === StatusPembayaranEnum::LUNAS->value;
                                    }),
                                Forms\Components\DatePicker::make('tanggal_jatuh_tempo')
                                    ->label('Tanggal Jatuh Tempo')
                                    ->required(function (Forms\Get $get) {
                                        $status = $get('status_pembayaran');
                                        return $status === StatusPembayaranEnum::TERM_OF_PAYMENT->value;
                                    })
                                    ->visible(function (Forms\Get $get) {
                                        $status = $get('status_pembayaran');
                                        return $status === StatusPembayaranEnum::TERM_OF_PAYMENT->value;
                                    }),
                            ]),
                    ])
                    ->visible(fn(Forms\Get $get) => $get('tipe') == TipeEnum::MASUK->value)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(BahanMutasi::query()->with('bahan'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('kode')
                    ->label('Kode Mutasi')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('tipe')
                    ->label('Tipe')
                    ->badge(TipeEnum::class)
                    ->sortable(),
                Tables\Columns\TextColumn::make('bahan.nama')
                    ->label('Bahan')
                    ->searchable()
                    ->sortable()
                    ->description(fn ($record) => $record->bahan->kode)
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('jumlah_mutasi')
                    ->label('Jumlah Mutasi')
                    ->numeric()
                    ->sortable()
                    ->suffix(fn (BahanMutasi $record) => ' ' . $record->bahan->satuanTerkecil->nama),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal Mutasi')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipe')
                    ->label('Tipe Mutasi')
                    ->options(TipeEnum::class),
                Tables\Filters\SelectFilter::make('bahan_id')
                    ->label('Bahan')
                    ->relationship('bahan', 'nama')
                    ->searchable()
                    ->preload(),
                DateRangeFilter::make('created_at')
                    ->label('Tanggal Mutasi'),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\EditAction::make()
                    ->mutateFormDataUsing(function (BahanMutasi $record, array $data): array {
                        if ($record->bahan_mutasi_faktur_id) {
                            $faktur = BahanMutasiFaktur::find($record->bahan_mutasi_faktur_id);
                            if ($faktur) {
                                $data['total_diskon'] = $faktur->total_diskon;
                                $data['status_pembayaran'] = $faktur->status_pembayaran instanceof StatusPembayaranEnum
                                    ? $faktur->status_pembayaran->value
                                    : $faktur->status_pembayaran;
                                $data['tanggal_pembayaran'] = $faktur->tanggal_pembayaran;
                                $data['metode_pembayaran'] = $faktur->metode_pembayaran;
                                $data['tanggal_jatuh_tempo'] = $faktur->tanggal_jatuh_tempo;
                                
                                // Load bukti faktur dari media collection
                                $media = $faktur->getFirstMedia('bahan_mutasi_faktur');
                                if ($media) {
                                    $data['bukti_faktur'] = $media->getPath();
                                }
                            }
                        }
                        return $data;
                    })
                    ->using(function (BahanMutasi $record, array $data): BahanMutasi {
                        if (!empty($record->bahan_mutasi_faktur_id)) {
                            $faktur = BahanMutasiFaktur::find($record->bahan_mutasi_faktur_id);
                            if ($faktur) {
                                $parseCurrency = fn ($value) => (int) str_replace([',', ' '], '', (string) ($value ?? 0));
                                $totalDiskonBaru = $parseCurrency($data['total_diskon'] ?? '0');
                                $totalTagihan = max(0, ($faktur->total_harga ?? 0) - $totalDiskonBaru);

                                $faktur->update([
                                    'total_diskon' => $totalDiskonBaru,
                                    'total_harga_setelah_diskon' => $totalTagihan,
                                    'status_pembayaran' => $data['status_pembayaran'],
                                    'tanggal_pembayaran' => $data['tanggal_pembayaran'] ?? $faktur->tanggal_pembayaran,
                                    'metode_pembayaran' => $data['metode_pembayaran'] ?? $faktur->metode_pembayaran,
                                    'tanggal_jatuh_tempo' => $data['tanggal_jatuh_tempo'] ?? $faktur->tanggal_jatuh_tempo,
                                ]);

                                // Handle update bukti faktur jika ada file baru
                                if (!empty($data['bukti_faktur'])) {
                                    try {
                                        // Hapus media lama jika ada
                                        $faktur->clearMediaCollection('bahan_mutasi_faktur');
                                        
                                        // Simpan file baru ke Spatie Media Library collection
                                        $filePath = is_array($data['bukti_faktur']) ? $data['bukti_faktur'][0] : $data['bukti_faktur'];
                                        
                                        if (is_string($filePath) && !empty($filePath)) {
                                            $faktur->addMediaFromDisk($filePath, 'public')
                                                ->toMediaCollection('bahan_mutasi_faktur');
                                        }
                                    } catch (\Exception $e) {
                                        Log::error('Error updating bukti faktur: ' . $e->getMessage());
                                    }
                                }

                                if ($data['status_pembayaran'] == StatusPembayaranEnum::LUNAS->value) {
                                    $totalPembayaran = $faktur->pencatatanKeuangans()->sum('jumlah_bayar');
                                    $sisa = max(0, $totalTagihan - $totalPembayaran);

                                    if ($sisa > 0) {
                                        PencatatanKeuangan::create([
                                            'pencatatan_keuangan_type' => BahanMutasiFaktur::class,
                                            'pencatatan_keuangan_id' => $faktur->id,
                                            'user_id' => Auth::id(),
                                            'jumlah_bayar' => $sisa,
                                            'metode_pembayaran' => $data['metode_pembayaran'] ?? $faktur->metode_pembayaran,
                                            'keterangan' => 'Pelunasan faktur ' . $faktur->kode,
                                            'approved_by' => null,
                                            'approved_at' => null,
                                        ]);
                                    }
                                }
                            }
                        }

                        $record->update($data);
                        return $record;
                    }),
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
            'index' => Pages\ManageBahanMutasis::route('/'),
        ];
    }

    protected static function fillSatuanFields(Forms\Set $set, $bahanId): void
    {
        if (!$bahanId) {
            $set('satuan_terbesar_id', null);
            $set('satuan_terkecil_id', null);
            return;
        }

        $bahan = Bahan::with(['satuanTerbesar', 'satuanTerkecil'])->find($bahanId);

        $set('satuan_terbesar_id', $bahan?->satuanTerbesar?->nama ?? null);
        $set('satuan_terkecil_id', $bahan?->satuanTerkecil?->nama ?? null);
    }
}
