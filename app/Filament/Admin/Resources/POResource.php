<?php

namespace App\Filament\Admin\Resources;

use App\Models\PO;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use App\Models\Bahan;
use App\Models\Satuan;
use App\Models\Supplier;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Support\RawJs;
use Filament\Resources\Resource;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use App\Filament\Admin\Resources\POResource\Pages;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Illuminate\Database\Eloquent\Model;

class POResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = PO::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    protected static ?string $navigationLabel = 'PO Bahan';

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()->can('view_p::o') && Auth::user()->can('view_any_p::o');
    }

    public static function canViewAny(): bool
    {
        return Auth::user()->can('view_any_p::o');
    }

    public static function canView(Model $record): bool
    {
        return Auth::user()->can('view_p::o');
    }

    public static function canCreate(): bool
    {
        return Auth::user()->can('create_p::o');
    }

    public static function canEdit(Model $record): bool
    {
        return Auth::user()->can('update_p::o');
    }

    public static function canDelete(Model $record): bool
    {
        return Auth::user()->can('delete_p::o');
    }

    public static function canDeleteAny(): bool
    {
        return Auth::user()->can('delete_any_p::o');
    }

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
            'delete_any',
            'approve_po'
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('kode')
                    ->label('Kode PO')
                    ->helperText(customableState())
                    ->required()
                    ->default(fn ($record) => $record?->kode ?? generateKode('PO')),
                Forms\Components\Select::make('supplier_id')
                    ->label('Supplier')
                    ->required()
                    ->relationship('supplier', 'nama_perusahaan', fn (Builder $query) => $query->where('is_po', true))
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('status_kirim')
                    ->label('Status Kirim')
                    ->required()
                    ->options([
                        'Ambil' => 'Ambil Di Tempat',
                        'Kirim' => 'Kirim Ke Lokasi',
                    ]),
                Forms\Components\DatePicker::make('tanggal_kirim')
                    ->label('Tanggal Kirim')
                    ->required()
                    ->default(now()),
                Forms\Components\Repeater::make('bahanPO')
                    ->label('Bahan PO')
                    ->required()
                    ->relationship('bahanPO')
                    ->live()
                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                        $parse = fn ($value) => (float) str_replace([',', ' '], '', (string) ($value ?? 0));
                        $items = $get('bahanPO') ?? [];
                        $total = 0;
                        if (is_array($items)) {
                            foreach ($items as $item) {
                                $total += $parse($item['total_harga_po'] ?? 0);
                            }
                        }
                        $set('total_harga_po_keseluruhan', $total);
                    })
                    ->schema([
                        Forms\Components\Select::make('bahan_id')
                            ->label('Bahan')
                            ->required()
                            ->relationship('bahan', 'nama', function (Builder $query, Forms\Get $get) {
                                // Get all selected bahan_id from other items in the repeater
                                $items = $get('../../bahanPO') ?? [];
                                $excludedIds = [];
                                $currentBahanId = $get('bahan_id');
                                
                                if (is_array($items)) {
                                    foreach ($items as $item) {
                                        if (isset($item['bahan_id']) && $item['bahan_id'] && $item['bahan_id'] != $currentBahanId) {
                                            $excludedIds[] = $item['bahan_id'];
                                        }
                                    }
                                }
                                
                                // Exclude already selected bahan (except current selection)
                                if (!empty($excludedIds)) {
                                    $query->whereNotIn('id', $excludedIds);
                                }
                                
                                return $query;
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateHydrated(function (Forms\Set $set, $state) {
                                if ($state) {
                                    $bahan = Bahan::find($state);
                                    if ($bahan) {
                                        $satuanTerbesar = Satuan::find($bahan->satuan_terbesar_id);
                                        $satuanTerkecil = Satuan::find($bahan->satuan_terkecil_id);
                                        if ($satuanTerbesar) {
                                            $set('satuan_terbesar_id', $satuanTerbesar->nama);
                                        }
                                        if ($satuanTerkecil) {
                                            $set('satuan_terkecil_id', $satuanTerkecil->nama);
                                        }
                                    }
                                }
                            })
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                if ($state) {
                                    $bahan = Bahan::find($state);
                                    if ($bahan) {
                                        $satuanTerbesar = Satuan::find($bahan->satuan_terbesar_id);
                                        $satuanTerkecil = Satuan::find($bahan->satuan_terkecil_id);
                                        if ($satuanTerbesar) {
                                            $set('satuan_terbesar_id', $satuanTerbesar->nama);
                                        }
                                        if ($satuanTerkecil) {
                                            $set('satuan_terkecil_id', $satuanTerkecil->nama);
                                        }
                                    }
                                }
                            })
                            ->columnSpanFull(),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('jumlah_terbesar')
                                     ->label('Jumlah Beli')
                                     ->required()
                                     ->numeric()
                                     ->mask(RawJs::make('$money($input)'))
                                     ->stripCharacters(',')
                                     ->live(onBlur: true)
                                     ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                        // Recalculate total_harga_po dan harga_satuan_terkecil jika harga_satuan_terbesar sudah diisi
                                        $parse = fn ($value) => (float) str_replace([',', ' '], '', (string) ($value ?? 0));
                                        $hargaSatuanTerbesar = $parse($get('harga_satuan_terbesar'));
                                        if ($hargaSatuanTerbesar > 0) {
                                            $jumlahTerbesar = $parse($get('jumlah_terbesar'));
                                            $jumlahTerkecil = $parse($get('jumlah_terkecil'));
                                            
                                            // Calculate total_harga_po
                                            $totalHargaPo = $hargaSatuanTerbesar * $jumlahTerbesar;
                                            $set('total_harga_po', (string) $totalHargaPo);
                                            
                                            // Calculate harga_satuan_terkecil
                                            $hargaSatuanTerkecil = $jumlahTerkecil > 0 ? round($hargaSatuanTerbesar / $jumlahTerkecil) : 0;
                                            $set('harga_satuan_terkecil', (string) $hargaSatuanTerkecil);
                                            
                                            // Update grand total
                                            $items = $get('../../bahanPO') ?? [];
                                            $grandTotal = 0;
                                            foreach ($items as $item) {
                                                $grandTotal += $parse($item['total_harga_po'] ?? 0);
                                            }
                                            $set('../../total_harga_po_keseluruhan', $grandTotal);
                                        }
                                        
                                        // Jika input via Dimensi, recalculate jumlah_terkecil
                                        if ($get('input_via') === 'Dimensi') {
                                            $panjang = $parse($get('panjang'));
                                            $lebar = $parse($get('lebar'));
                                            
                                            if ($panjang > 0 && $lebar > 0) {
                                                // Hanya p * l untuk jumlah_terkecil (luas per satuan terbesar)
                                                $luas = $panjang * $lebar;
                                                $set('jumlah_terkecil', (string) $luas);
                                                
                                                // Update harga satuan terkecil (Harga Satuan Besar / Luas)
                                                if ($hargaSatuanTerbesar > 0 && $luas > 0) {
                                                    $hargaSatuanTerkecil = round($hargaSatuanTerbesar / $luas);
                                                    $set('harga_satuan_terkecil', (string) $hargaSatuanTerkecil);
                                                }
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
                                    ->grouped()
                                    ->options([
                                        'Nominal' => 'Nominal',
                                        'Dimensi' => 'Dimensi',
                                    ])
                                    ->default('Nominal')
                                    ->required()
                                    ->live()
                                    ->inline()
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
                                            ->live(onBlur: true)
                                            ->visible(fn (Forms\Get $get) => $get('input_via') === 'Dimensi')
                                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                                $parse = fn ($value) => (float) str_replace([',', ' '], '', (string) ($value ?? 0));
                                                $panjang = $parse($get('panjang'));
                                                $lebar = $parse($get('lebar'));
                                                
                                                if ($panjang > 0 && $lebar > 0) {
                                                    // Hanya p * l untuk jumlah_terkecil (luas per satuan terbesar)
                                                    $luas = $panjang * $lebar;
                                                    $set('jumlah_terkecil', (string) $luas);
                                                    
                                                    // Update harga satuan terkecil (Harga Satuan Besar / Luas)
                                                    $hargaSatuanTerbesar = $parse($get('harga_satuan_terbesar'));
                                                    if ($hargaSatuanTerbesar > 0 && $luas > 0) {
                                                        $hargaSatuanTerkecil = round($hargaSatuanTerbesar / $luas);
                                                        $set('harga_satuan_terkecil', (string) $hargaSatuanTerkecil);
                                                    }
                                                }
                                            }),
                                        Forms\Components\TextInput::make('lebar')
                                            ->label('Lebar (l)')
                                            ->required()
                                            ->numeric()
                                            ->step(0.01)
                                            ->live(onBlur: true)
                                            ->visible(fn (Forms\Get $get) => $get('input_via') === 'Dimensi')
                                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                                $parse = fn ($value) => (float) str_replace([',', ' '], '', (string) ($value ?? 0));
                                                $panjang = $parse($get('panjang'));
                                                $lebar = $parse($get('lebar'));
                                                
                                                if ($panjang > 0 && $lebar > 0) {
                                                    // Hanya p * l untuk jumlah_terkecil (luas per satuan terbesar)
                                                    $luas = $panjang * $lebar;
                                                    $set('jumlah_terkecil', (string) $luas);
                                                    
                                                    // Update harga satuan terkecil (Harga Satuan Besar / Luas)
                                                    $hargaSatuanTerbesar = $parse($get('harga_satuan_terbesar'));
                                                    if ($hargaSatuanTerbesar > 0 && $luas > 0) {
                                                        $hargaSatuanTerkecil = round($hargaSatuanTerbesar / $luas);
                                                        $set('harga_satuan_terkecil', (string) $hargaSatuanTerkecil);
                                                    }
                                                }
                                            }),
                                    ])
                                    ->visible(fn (Forms\Get $get) => $get('input_via') === 'Dimensi')
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('jumlah_terkecil')
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
                                        // Recalculate harga_satuan_terkecil jika harga_satuan_terbesar sudah diisi
                                        $parse = fn ($value) => (float) str_replace([',', ' '], '', (string) ($value ?? 0));
                                        $hargaSatuanTerbesar = $parse($get('harga_satuan_terbesar'));
                                        if ($hargaSatuanTerbesar > 0) {
                                            $jumlahTerkecil = $parse($get('jumlah_terkecil'));
                                            $hargaSatuanTerkecil = $jumlahTerkecil > 0 ? round($hargaSatuanTerbesar / $jumlahTerkecil) : 0;
                                            $set('harga_satuan_terkecil', (string) $hargaSatuanTerkecil);
                                        }
                                     }),
                                Forms\Components\TextInput::make('satuan_terkecil_id')
                                    ->label('Satuan Terkecil')
                                    ->required()
                                    ->readOnly()
                                    ->dehydrated(false),
                            ]),
                        Forms\Components\TextInput::make('harga_satuan_terbesar')
                            ->label('Harga per Satuan Terbesar')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->suffix(',-')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->live(onBlur: true)
                            ->helperText('Input manual harga satuan terbesar')
                            ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                $parse = fn ($value) => (float) str_replace([',', ' '], '', (string) ($value ?? 0));
                                $hargaSatuanTerbesar = $parse($get('harga_satuan_terbesar'));
                                $jumlahTerbesar = $parse($get('jumlah_terbesar'));
                                $jumlahTerkecil = $parse($get('jumlah_terkecil'));
                                
                                // Calculate total_harga_po
                                $totalHargaPo = $hargaSatuanTerbesar * $jumlahTerbesar;
                                $set('total_harga_po', (string) $totalHargaPo);
                                
                                // Calculate harga_satuan_terkecil
                                $hargaSatuanTerkecil = $jumlahTerkecil > 0 ? round($hargaSatuanTerbesar / $jumlahTerkecil) : 0;
                                $set('harga_satuan_terkecil', (string) $hargaSatuanTerkecil);
                                
                                // Update grand total
                                $items = $get('../../bahanPO') ?? [];
                                $grandTotal = 0;
                                foreach ($items as $item) {
                                    $grandTotal += $parse($item['total_harga_po'] ?? 0);
                                }
                                $set('../../total_harga_po_keseluruhan', $grandTotal);
                                
                                // Jika input via Dimensi, pastikan jumlah_terkecil sudah terhitung
                                if ($get('input_via') === 'Dimensi') {
                                    $panjang = $parse($get('panjang'));
                                    $lebar = $parse($get('lebar'));
                                    
                                    if ($panjang > 0 && $lebar > 0) {
                                        // Hanya p * l untuk jumlah_terkecil (luas per satuan terbesar)
                                        $luas = $panjang * $lebar;
                                        $set('jumlah_terkecil', (string) $luas);
                                        
                                        // Recalculate harga satuan terkecil (Harga Satuan Besar / Luas)
                                        if ($hargaSatuanTerbesar > 0 && $luas > 0) {
                                            $hargaSatuanTerkecil = round($hargaSatuanTerbesar / $luas);
                                            $set('harga_satuan_terkecil', (string) $hargaSatuanTerkecil);
                                        }
                                    }
                                }
                            })
                            ->columnSpanFull(),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('total_harga_po')
                                    ->label('Total Harga PO')
                                    ->prefix('Rp')
                                    ->suffix(',-')
                                    ->required()
                                    ->numeric()
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->readOnly()
                                    ->dehydrated(),
                                Forms\Components\TextInput::make('harga_satuan_terkecil')
                                    ->label('Harga Satuan Terkecil')
                                    ->required()
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->suffix(',-')
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->readOnly()
                                    ->dehydrated()
                                    ->helperText('Otomatis terhitung dari harga satuan terbesar'),
                            ]),
                    ])
                    ->columnSpanFull(),
                Forms\Components\Hidden::make('total_harga_po_keseluruhan')
                    ->default(0)
                    ->afterStateHydrated(function (Forms\Set $set, Forms\Get $get, $state) {
                        if ($state > 0) {
                            return;
                        }

                        $parse = fn ($value) => (float) str_replace([',', ' '], '', (string) ($value ?? 0));
                        $items = $get('bahanPO') ?? [];
                        $total = 0;
                        if (is_array($items)) {
                            foreach ($items as $item) {
                                $total += $parse($item['total_harga_po'] ?? 0);
                            }
                        }
                        $set('total_harga_po_keseluruhan', $total);
                    })
                    ->dehydrated(),
                Forms\Components\Placeholder::make('total_harga_po_keseluruhan')
                    ->label('Total Keseluruhan PO')
                    ->content(function (Forms\Get $get, ?PO $record) {
                        $total = (float) $get('total_harga_po_keseluruhan');

                        if ($total === 0) {
                            $items = $get('bahanPO') ?? [];
                            if (is_array($items)) {
                                foreach ($items as $item) {
                                    $total += (float) str_replace([',', ' '], '', (string) ($item['total_harga_po'] ?? 0));
                                }
                            }
                        }

                        if ($total === 0 && $record?->relationLoaded('bahanPO')) {
                            $total = $record->bahanPO->sum('total_harga_po');
                        }

                        return formatRupiah($total);
                    })
                    ->live()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('kode')
                    ->description(fn(PO $record) => $record->bahanMutasiFaktur ? "({$record->bahanMutasiFaktur->kode})" : '(Belum ada faktur)'),
                Tables\Columns\TextColumn::make('supplier.nama_perusahaan')
                    ->label('Supplier')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tanggal_kirim')
                    ->label('Tanggal Kirim/Ambil')
                    ->getStateUsing(fn(PO $record) => Carbon::parse($record->tanggal_kirim)->translatedFormat('d M Y'))
                    ->sortable(),
                Tables\Columns\IconColumn::make('status_kirim')
                    ->label('Status Kirim')
                    ->icon(fn(PO $record) => $record->status_kirim == 'Ambil' ? 'heroicon-o-shopping-cart' : 'heroicon-o-truck')
                    ->color(fn(PO $record) => $record->status_kirim == 'Ambil' ? 'info' : 'success'),
                Tables\Columns\TextColumn::make('total_harga_po_keseluruhan')
                    ->label('Total Harga PO')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('is_approved')
                    ->label('Persetujuan')
                    ->badge()
                    ->color(fn(PO $record) => $record->is_approved === null ? 'warning' : ($record->is_approved ? 'success' : 'danger'))
                    ->getStateUsing(fn(PO $record) => $record->is_approved === null ? 'Belum Disetujui' : ($record->is_approved ? 'Disetujui' : 'Ditolak'))
                    ->description(fn(PO $record) => $record->is_approved === null ? '-' : Carbon::parse($record->tanggal_approved)->translatedFormat('d M Y')),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->color('info')
                    ->label('Approval PO')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Select::make('is_approved')
                            ->label('Persetujuan')
                            ->options([
                                true => 'Disetujui',
                                false => 'Ditolak',
                            ])
                            ->required(),
                    ])
                    ->action(function (PO $record, array $data) {
                        $user = Auth::user();
                        $record->is_approved = $data['is_approved'];
                        $record->tanggal_approved = now();
                        $record->approved_by = $user->id;
                        $record->save();
                        Notification::make()
                            ->title('Sukses')
                            ->body('PO berhasil ' . ($data['is_approved'] ? 'disetujui' : 'ditolak') . ' oleh ' . $user->name)
                            ->success()
                            ->send();
                    })
                    ->visible(fn(PO $record) => $record->is_approved === null && Auth::user()->can('approve_po_p::o')),
                Tables\Actions\EditAction::make()
                    ->after(function (PO $record) {
                        $record->total_harga_po_keseluruhan = $record->bahanPO->sum('total_harga_po');
                        $record->save();
                    })
                    ->visible(fn(PO $record) => !$record->bahanMutasiFaktur && Auth::user()->can('update_p::o')),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn(PO $record) => !$record->bahanMutasiFaktur && Auth::user()->can('delete_p::o')),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()->can('delete_any_p::o')),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePOS::route('/'),
        ];
    }

}
