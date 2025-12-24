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
use App\Enums\Transaksi\TipeSubjoinEnum;
use Filament\Notifications\Notification;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\Livewire;
use App\Enums\Transaksi\StatusTransaksiEnum;
use App\Enums\Transaksi\StatusPembayaranEnum;
use Illuminate\Database\Eloquent\SoftDeletingScope;
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
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->label('Lihat Ringkasan Biaya')
                        ->icon('heroicon-o-document-text')
                        ->color('success')
                        ->modalHeading(fn(Transaksi $record) => 'Ringkasan Biaya - ' . $record->kode)
                        ->modalContent(function (Transaksi $record) {
                            $transaksiProduks = $record->transaksiProduks()->with(['produk', 'design'])->get();
                            $customer = $record->customer;
                            
                            if ($transaksiProduks->isEmpty()) {
                                return new HtmlString('<p>Tidak ada produk dalam transaksi ini.</p>');
                            }
                            
                            $html = '<div style="font-family: sans-serif;">';
                            $totalKeseluruhan = 0;
                            $produkCounter = 0;
                            
                            foreach ($transaksiProduks as $item) {
                                $produkCounter++;
                                $produkModel = $item->produk;
                                
                                if (!$produkModel) continue;
                                
                                // Hitung harga satuan (dari total_harga_produk_sebelum_diskon)
                                $jumlah = (float) $item->jumlah;
                                $panjang = $item->panjang ?? 1.0;
                                $lebar = $item->lebar ?? 1.0;
                                
                                // Hitung subtotal produk tanpa design dan addon
                                $totalProdukSebelumDiskon = (float) $item->total_harga_produk_sebelum_diskon;
                                
                                $html .= '<div style="border: 1px solid #e5e7eb; padding: 16px; margin-bottom: 16px; border-radius: 8px;">';
                                
                                // Tampilkan judul pesanan jika ada
                                if (!empty($item->judul_pesanan)) {
                                    $html .= '<div style="margin-bottom: 8px; padding: 8px; background: #f0fdf4; border-left: 4px solid #10b981; border-radius: 4px;">';
                                    $html .= '<strong style="color: #059669;">Judul Pesanan:</strong> ' . e($item->judul_pesanan);
                                    $html .= '</div>';
                                }
                                
                                $html .= '<h4 style="margin: 0 0 12px 0; color: #374151;">Produk #' . $produkCounter . ': [' . $produkModel->kode . '] - ' . $produkModel->nama . '</h4>';
                                $html .= '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 12px;">';
                                $html .= '<div><strong>Jumlah:</strong> ' . $jumlah . '</div>';
                                
                                if ($item->panjang && $item->lebar) {
                                    $panjangDisplay = rtrim(rtrim(number_format($panjang, 2, '.', ''), '0'), '.');
                                    $lebarDisplay = rtrim(rtrim(number_format($lebar, 2, '.', ''), '0'), '.');
                                    $html .= '<div><strong>Dimensi:</strong> ' . $panjangDisplay . ' x ' . $lebarDisplay . '</div>';
                                } else {
                                    $html .= '<div><strong>Dimensi:</strong> Standar</div>';
                                }
                                
                                $html .= '<div><strong>Harga Sebelum Diskon:</strong> ' . formatRupiah($totalProdukSebelumDiskon) . '</div>';
                                
                                if ($item->total_diskon_produk > 0) {
                                    $html .= '<div style="color: #dc2626;"><strong>Diskon Produk:</strong> - ' . formatRupiah($item->total_diskon_produk) . '</div>';
                                }
                                
                                $html .= '</div>';
                                
                                // Hitung dan tampilkan design
                                if ($item->design_id) {
                                    $design = \App\Models\ProdukProses::find($item->design_id);
                                    if ($design) {
                                        $html .= '<div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #f3f4f6;">';
                                        $html .= '<strong>Design:</strong><br>';
                                        $html .= '• ' . $design->nama . ': ' . formatRupiah($design->harga ?? 0);
                                        $html .= '</div>';
                                    }
                                }
                                
                                // Hitung dan tampilkan addon
                                if (!empty($item->addons) && is_array($item->addons)) {
                                    $addons = \App\Models\ProdukProses::whereIn('id', $item->addons)->whereNotNull('harga')->get();
                                    if ($addons->count() > 0) {
                                        $html .= '<div style="margin-top: 8px; padding-top: 8px; border-top: 1px solid #f3f4f6;">';
                                        $html .= '<strong>Addons:</strong><br>';
                                        $totalAddon = 0;
                                        foreach ($addons as $addon) {
                                            $html .= '• ' . $addon->nama . ': ' . formatRupiah($addon->harga) . '<br>';
                                            $totalAddon += (float) $addon->harga;
                                        }
                                        $html .= '<strong>Total Addon: ' . formatRupiah($totalAddon) . '</strong>';
                                        $html .= '</div>';
                                    }
                                }
                                
                                // Tampilkan keterangan jika ada
                                if (!empty($item->keterangan)) {
                                    $html .= '<div style="margin-top: 8px; padding: 8px; background: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 4px;">';
                                    $html .= '<strong>Keterangan:</strong> ' . nl2br(e($item->keterangan));
                                    $html .= '</div>';
                                }
                                
                                $totalProdukFinal = (float) $item->total_harga_produk_setelah_diskon;
                                
                                $html .= '<div style="margin-top: 12px; padding-top: 8px; border-top: 2px solid #10b981; font-weight: bold; color: #059669;">';
                                $html .= 'Total Produk #' . $produkCounter . ': ' . formatRupiah($totalProdukFinal);
                                $html .= '</div>';
                                $html .= '</div>';
                                
                                $totalKeseluruhan += $totalProdukFinal;
                            }
                            
                            // Total sebelum diskon invoice
                            $html .= '<div style="background: #f0fdf4; border: 2px solid #10b981; padding: 16px; border-radius: 8px; margin-bottom: 16px;">';
                            $html .= '<div style="display: flex; justify-content: space-between; margin-bottom: 8px;">';
                            $html .= '<span><strong>Subtotal (setelah diskon per item):</strong></span>';
                            $html .= '<span><strong>' . formatRupiah($totalKeseluruhan) . '</strong></span>';
                            $html .= '</div>';
                            
                            if ($record->total_diskon_transaksi > 0) {
                                $diskonInvoice = $record->total_diskon_transaksi - $transaksiProduks->sum('total_diskon_produk');
                                if ($diskonInvoice > 0) {
                                    $html .= '<div style="display: flex; justify-content: space-between; color: #dc2626; margin-bottom: 8px;">';
                                    $html .= '<span><strong>Diskon Invoice:</strong></span>';
                                    $html .= '<span><strong>- ' . formatRupiah($diskonInvoice) . '</strong></span>';
                                    $html .= '</div>';
                                }
                            }
                            
                            $html .= '</div>';
                            
                            $html .= '<div style="background: #f0fdf4; border: 2px solid #10b981; padding: 16px; border-radius: 8px; text-align: center;">';
                            $html .= '<h3 style="margin: 0; color: #059669; font-size: 20px;">TOTAL KESELURUHAN: ' . formatRupiah($record->total_harga_transaksi_setelah_diskon) . '</h3>';
                            $html .= '</div>';
                            $html .= '</div>';
                            
                            return new HtmlString($html);
                        })
                        ->modalSubmitAction(false)
                        ->modalCancelActionLabel('Tutup'),
                    Tables\Actions\Action::make('detail_transaksi')
                        ->label('Plan Subjoin')
                        ->icon('heroicon-o-document-text')
                        ->color('info')
                        ->url(fn(Transaksi $record) => Pages\TransaksiPlanSubjoinPage::getUrl(['record' => $record->id])),
                    Tables\Actions\Action::make('detail_pembayaran')
                        ->label('Detail Pembayaran')
                        ->icon('heroicon-o-credit-card')
                        ->color('gray')
                        ->infolist(fn(Transaksi $record) => [
                            Livewire::make(DetailPembayaranTable::class, ['transaksi' => $record]),
                        ]),
                    Tables\Actions\Action::make('bayar_top')
                        ->label('Bayar TOP')
                        ->icon('heroicon-o-currency-dollar')
                        ->color('warning')
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
                ]),
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
            'detail' => Pages\TransaksiPlanSubjoinPage::route('/{record}/plan-subjoin'),
        ];
    }
}
