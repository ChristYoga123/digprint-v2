<?php

namespace App\Filament\Admin\Resources;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Transaksi;
use Filament\Tables\Table;
use Filament\Support\RawJs;
use Filament\Resources\Resource;
use App\Models\PencatatanKeuangan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\Livewire;
use App\Enums\Transaksi\StatusTransaksiEnum;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Enums\Transaksi\StatusPembayaranEnum;
use App\Filament\Admin\Resources\TransaksiResource\Pages;
use App\Livewire\Admin\TransaksiResource\DetailPembayaranTable;
use App\Filament\Admin\Resources\TransaksiResource\RelationManagers;

class TransaksiResource extends Resource
{
    protected static ?string $model = Transaksi::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Transaksi';

    public static function canCreate(): bool
    {
        return false;
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
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('kode')
                    ->searchable(query: fn(Builder $query, string $search) => $query->where('kode', 'like', '%' . $search . '%')->orWhereHas('customer', function ($query) use ($search) {
                        $query->where('nama', 'like', '%' . $search . '%');
                    }))
                    ->weight('bold')
                    ->description(fn(Transaksi $record) => "({$record->customer->nama})"),
                Tables\Columns\TextColumn::make('total_harga_transaksi_setelah_diskon')
                    ->label('Total Transaksi')
                    ->money('IDR')
                    ->sortable()
                    ->description(function(Transaksi $record) {
                        $dibayar = $record->pencatatanKeuangans->sum('jumlah_bayar');
                        $sisa = max(0, $record->total_harga_transaksi_setelah_diskon - $dibayar);
                        if ($sisa > 0) {
                            return new HtmlString('<span style="color: red;" class="font-bold">Tagihan: ' . formatRupiah($sisa) . '</span>');
                        }
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y')
                    ->weight('bold')
                    ->description(fn(Transaksi $record) => Carbon::parse($record->created_at)->format('H:i'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('status_pembayaran')
                    ->label('Status Pembayaran')
                    ->badge(StatusPembayaranEnum::class)
                    ->sortable(),
                Tables\Columns\TextColumn::make('status_transaksi')
                    ->label('Status Pengerjaan')
                    ->badge(StatusTransaksiEnum::class)
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status_transaksi')
                    ->label('Status Pengerjaan')
                    ->options(StatusTransaksiEnum::class),
                Tables\Filters\SelectFilter::make('status_pembayaran')
                    ->label('Status Pembayaran')
                    ->options(StatusPembayaranEnum::class),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat Detail')
                    ->modalHeading(fn(Transaksi $record) => 'Detail Transaksi: ' . $record->kode)
                    ->modalContent(function (Transaksi $record) {
                        $kalkulasi = $record->transaksiKalkulasi;
                        if (!$kalkulasi) {
                            return new HtmlString('<p>Kalkulasi tidak ditemukan</p>');
                        }

                        $customer = $kalkulasi->customer;
                        // Load transaksiKalkulasiProduks dengan semua kolom termasuk keterangan
                        $produks = $kalkulasi->transaksiKalkulasiProduks()->get();

                        $html = '<div style="font-family: sans-serif;">';
                        $html .= '<div style="margin-bottom: 16px; padding: 12px; background: #f3f4f6; border-radius: 8px;">';
                        $html .= '<strong>Customer:</strong> [' . $customer->customerKategori->kode . '] - ' . $customer->nama . '<br>';
                        $html .= '<strong>Kode Kalkulasi:</strong> ' . $kalkulasi->kode;
                        $html .= '</div>';

                        $totalKeseluruhan = 0;
                        $produkCounter = 0;

                        foreach ($produks as $produk) {
                            $produkModel = $produk->produk;
                            if (!$produkModel) continue;

                            $produkCounter++;
                            
                            // Ambil harga satuan berdasarkan kategori customer
                            $produkHarga = \App\Models\ProdukHarga::where('produk_id', $produk->produk_id)
                                ->where('customer_kategori_id', $customer->customer_kategori_id)
                                ->first();
                            
                            $hargaSatuan = $produkHarga ? (float) $produkHarga->harga : 0.0;
                            
                            $jumlah = (float) ($produk->jumlah ?? 1);
                            $panjang = $produk->panjang ? (float) $produk->panjang : 1.0;
                            $lebar = $produk->lebar ? (float) $produk->lebar : 1.0;
                            
                            $totalProduk = $hargaSatuan * $jumlah * $panjang * $lebar;
                            
                            $html .= '<div style="border: 1px solid #e5e7eb; padding: 16px; margin-bottom: 16px; border-radius: 8px;">';
                            $html .= '<h4 style="margin: 0 0 12px 0; color: #374151;">Produk #' . $produkCounter . ': [' . $produkModel->kode . '] - ' . $produkModel->nama . '</h4>';
                            $html .= '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 12px;">';
                            $html .= '<div><strong>Harga Satuan:</strong> ' . formatRupiah($hargaSatuan) . '</div>';
                            $html .= '<div><strong>Jumlah:</strong> ' . $jumlah . '</div>';
                            
                            if ($produk->panjang && $produk->lebar) {
                                $panjangDisplay = rtrim(rtrim(number_format($panjang, 2, '.', ''), '0'), '.');
                                $lebarDisplay = rtrim(rtrim(number_format($lebar, 2, '.', ''), '0'), '.');
                                $html .= '<div><strong>Dimensi:</strong> ' . $panjangDisplay . ' x ' . $lebarDisplay . '</div>';
                            } else {
                                $html .= '<div><strong>Dimensi:</strong> Standar</div>';
                            }
                            
                            $html .= '<div><strong>Subtotal Produk:</strong> ' . formatRupiah($totalProduk) . '</div>';
                            $panjangDisplay = rtrim(rtrim(number_format($panjang, 2, '.', ''), '0'), '.');
                            $lebarDisplay = rtrim(rtrim(number_format($lebar, 2, '.', ''), '0'), '.');
                            $html .= '<div style="font-size: 12px; color: #6b7280; margin-top: 4px;">(' . formatRupiah($hargaSatuan) . ' × ' . $jumlah . ' × ' . $panjangDisplay . ' × ' . $lebarDisplay . ')</div>';
                            $html .= '</div>';
                            
                            // Tampilkan keterangan jika ada
                            $keterangan = isset($produk->keterangan) ? $produk->keterangan : null;
                            if (!empty($keterangan)) {
                                $html .= '<div style="margin-top: 8px; padding: 8px; background: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 4px;">';
                                $html .= '<strong>Keterangan:</strong> ' . nl2br(e($keterangan));
                                $html .= '</div>';
                            }
                            
                            // Hitung dan tampilkan design
                            $totalDesign = 0.0;
                            if ($produk->design_id) {
                                $design = \App\Models\ProdukProses::where('id', $produk->design_id)
                                    ->where('produk_proses_kategori_id', 1)
                                    ->first();
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
                            if ($produk->addons && is_array($produk->addons) && !empty($produk->addons)) {
                                $addons = \App\Models\ProdukProses::whereIn('id', $produk->addons)
                                    ->whereNotNull('harga')
                                    ->where('produk_proses_kategori_id', 3)
                                    ->get();
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
                            
                            $totalProdukFinal = $totalProduk + $totalDesign + $totalAddon;
                            
                            $html .= '<div style="margin-top: 12px; padding-top: 8px; border-top: 2px solid #3b82f6; font-weight: bold; color: #1d4ed8;">';
                            $html .= 'Total Produk #' . $produkCounter . ': ' . formatRupiah($totalProdukFinal);
                            $html .= '</div>';
                            $html .= '</div>';
                            
                            $totalKeseluruhan += $totalProdukFinal;
                        }
                        
                        $html .= '<div style="background: #f8fafc; border: 2px solid #3b82f6; padding: 16px; border-radius: 8px; text-align: center; margin-top: 16px;">';
                        $html .= '<h3 style="margin: 0; color: #1d4ed8; font-size: 20px;">TOTAL KESELURUHAN: ' . formatRupiah($totalKeseluruhan) . '</h3>';
                        $html .= '</div>';
                        $html .= '</div>';
                        
                        return new HtmlString($html);
                    })
                    ->modalWidth('4xl'),
                Tables\Actions\Action::make('detail_pembayaran')
                    ->label('Detail Pembayaran')
                    ->icon('heroicon-o-credit-card')
                    ->color('primary')
                    ->infolist(fn(Transaksi $record) => [
                        Livewire::make(DetailPembayaranTable::class, ['transaksi' => $record]),
                    ]),
                Tables\Actions\Action::make('bayar_top')
                    ->label('Bayar TOP')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->visible(function (Transaksi $record) {
                        // Cek apakah status pembayaran adalah TOP
                        // Handle baik enum instance maupun string value
                        $status = $record->status_pembayaran;
                        if ($status instanceof StatusPembayaranEnum) {
                            return $status === StatusPembayaranEnum::TERM_OF_PAYMENT;
                        }
                        // Fallback: bandingkan dengan value jika masih string
                        return $status === StatusPembayaranEnum::TERM_OF_PAYMENT->value;
                    })
                    ->form([
                        Forms\Components\Select::make('metode_pembayaran')
                            ->label('Metode Pembayaran')
                            ->options(getBankData())
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('jumlah_bayar')
                            ->label('Jumlah Bayar')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->minValue(1)
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->helperText(fn (Transaksi $record) => 'Sisa tagihan: ' . formatRupiah($record->total_harga_transaksi_setelah_diskon - $record->pencatatanKeuangans->sum('jumlah_bayar'))),
                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->placeholder('Keterangan pembayaran (opsional)')
                            ->rows(3),
                    ])
                    ->action(function (Transaksi $record, array $data) {
                        // Parse jumlah bayar (remove comma separator)
                        $jumlahBayarParsed = (int) str_replace(',', '', (string) $data['jumlah_bayar']);
                        
                        if ($jumlahBayarParsed <= 0) {
                            Notification::make()
                                ->title('Jumlah bayar harus lebih dari 0')
                                ->warning()
                                ->send();
                            return;
                        }

                        if ($jumlahBayarParsed > $record->total_harga_transaksi_setelah_diskon - $record->pencatatanKeuangans->sum('jumlah_bayar')) {
                            Notification::make()
                                ->title('Jumlah bayar tidak boleh lebih dari sisa tagihan')
                                ->warning()
                                ->send();
                            return;
                        }

                        try {
                            DB::beginTransaction();

                            // Create PencatatanKeuangan
                            PencatatanKeuangan::create([
                                'pencatatan_keuangan_type' => Transaksi::class,
                                'pencatatan_keuangan_id' => $record->id,
                                'user_id' => Auth::id(),
                                'jumlah_bayar' => $jumlahBayarParsed,
                                'metode_pembayaran' => $data['metode_pembayaran'],
                                'keterangan' => $data['keterangan'] ?? 'Pembayaran tambahan transaksi ' . $record->kode,
                                'approved_by' => null,
                                'approved_at' => null,
                            ]);

                            // Update jumlah_bayar di Transaksi dari aggregate pencatatan_keuangans
                            $totalPembayaran = PencatatanKeuangan::where('pencatatan_keuangan_type', Transaksi::class)
                                ->where('pencatatan_keuangan_id', $record->id)
                                ->sum('jumlah_bayar');

                            $record->update([
                                'jumlah_bayar' => $totalPembayaran,
                            ]);

                            // Cek apakah sudah lunas
                            if ($totalPembayaran >= $record->total_harga_transaksi_setelah_diskon) {
                                $record->update([
                                    'status_pembayaran' => StatusPembayaranEnum::LUNAS->value,
                                    'tanggal_pembayaran' => now(),
                                ]);

                                Notification::make()
                                    ->title('Transaksi sudah lunas')
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Pembayaran berhasil ditambahkan')
                                    ->success()
                                    ->send();
                            }

                            DB::commit();

                        } catch (\Exception $e) {
                            DB::rollBack();
                            
                            Notification::make()
                                ->title('Gagal menambah pembayaran')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
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
            'index' => Pages\ManageTransaksis::route('/'),
        ];
    }
}
