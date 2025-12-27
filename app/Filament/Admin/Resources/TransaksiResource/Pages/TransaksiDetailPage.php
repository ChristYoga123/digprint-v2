<?php

namespace App\Filament\Admin\Resources\TransaksiResource\Pages;

use Exception;
use Filament\Forms\Get;
use Filament\Tables\Table;
use Filament\Support\RawJs;
use App\Models\ProdukProses;
use App\Models\TransaksiProduk;
use App\Models\TransaksiProses;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use App\Models\ProdukProsesKategori;
use Filament\Forms\Components\Select;
use App\Models\TransaksiProdukSubjoin;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use App\Enums\Utils\TipeNotificationEnum;
use Filament\Forms\Components\FileUpload;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Infolists\Components\Livewire;
use App\Enums\TransaksiProduk\TipeSubjoinEnum;
use Filament\Tables\Concerns\InteractsWithTable;
use App\Filament\Admin\Resources\TransaksiResource;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use App\Livewire\Admin\TransaksiDetailPage\TransaksiSubjoinTable;

class TransaksiDetailPage extends Page implements HasTable
{
    use InteractsWithTable, InteractsWithRecord;
    protected static string $resource = TransaksiResource::class;

    protected static string $view = 'filament.admin.resources.transaksi-resource.pages.transaksi-detail-page';

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string|Htmlable
    {
        return 'Detail Transaksi: ' . $this->record->kode;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(TransaksiProduk::query()->where('transaksi_id', $this->record->id))
            ->columns([
                TextColumn::make('produk.nama')
                    ->label('Nama')
                    ->sortable()
                    ->description(fn(TransaksiProduk $record) => 'Jumlah: ' . $record->jumlah . ' pesanan'),
                TextColumn::make('total_harga_produk_setelah_diskon')
                    ->label('Total Harga')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold'),
            ])
            ->actions([
                Action::make('print_spk')
                    ->label('Print SPK')
                    ->icon('heroicon-o-printer')
                    ->color('warning')
                    ->url(fn(TransaksiProduk $record) => route('print.spk', ['transaksi_produk_id' => $record->id]))
                    ->openUrlInNewTab(),
                Action::make('lihat_subjoin')
                    ->label('Lihat Subjoin')
                    ->icon('heroicon-o-eye')
                    ->color('success')
                    ->infolist(fn(TransaksiProduk $record) => [
                        Livewire::make(TransaksiSubjoinTable::class, ['produk' => $record])
                    ])
                    ->modalHeading('Lihat Rencana Subjoin'),
                Action::make('set_subjoin')
                    ->label('Set Rencana Subjoin')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->form(function(TransaksiProduk $record) {
                        return [
                            Grid::make(2)
                                ->schema([
                                    Select::make('tipe_subjoin_id')
                                        ->label('Tipe Subjoin')
                                        ->options(TipeSubjoinEnum::class)
                                        ->live()
                                        ->required()
                                        ->columnSpan(fn(Get $get) => $get('tipe_subjoin_id') === TipeSubjoinEnum::FULL->value ? 2 : 1)
                                        ->helperText(fn(Get $get) => $get('tipe_subjoin_id') === TipeSubjoinEnum::FULL->value ? 'Untuk subjoin full, maka semua proses akan disubjoin' : 'Pilih proses yang akan disubjoin'),
                                    Select::make('produk_proses_id')
                                        ->label('Proses')
                                        ->options(fn(Get $get) => $get('tipe_subjoin_id') ? ProdukProses::query()
                                            ->whereHas('prosesKategori', fn($query) => $query->where('nama', $get('tipe_subjoin_id')))
                                            ->where('produk_id', $record->produk_id)
                                            ->pluck('nama', 'id') : null
                                        )
                                        ->live()
                                        ->required(fn(Get $get) => $get('tipe_subjoin_id') !== TipeSubjoinEnum::FULL->value)
                                        ->hidden(fn(Get $get) => $get('tipe_subjoin_id') === TipeSubjoinEnum::FULL->value),
                                ]),
                        ];
                    })
                    ->action(function(TransaksiProduk $record, array $data) {
                        DB::beginTransaction();
                        try{
                            // Jika tipe subjoin adalah FULL
                            if($data['tipe_subjoin_id'] === TipeSubjoinEnum::FULL->value) {
                                // Ambil semua transaksi proses untuk produk ini
                                $transaksiProses = TransaksiProses::where('transaksi_produk_id', $record->id)
                                    ->with('produkProses')
                                    ->orderBy('urutan')
                                    ->get();
                                
                                if($transaksiProses->isEmpty()) {
                                    DB::rollback();
                                    Notification::make()
                                        ->title('Gagal')
                                        ->body('Tidak ada proses yang ditemukan untuk produk ini')
                                        ->danger()
                                        ->send();
                                    return;
                                }

                                $createdCount = 0;
                                $skippedCount = 0;

                                // Buat subjoin untuk setiap proses
                                foreach($transaksiProses as $proses) {
                                    // Cek apakah subjoin sudah ada
                                    $exists = TransaksiProdukSubjoin::where('transaksi_produk_id', $record->id)
                                        ->where('produk_proses_id', $proses->produk_proses_id)
                                        ->exists();
                                    
                                    if(!$exists) {
                                        TransaksiProdukSubjoin::create([
                                            'transaksi_produk_id' => $record->id,
                                            'produk_proses_id' => $proses->produk_proses_id,
                                            'apakah_subjoin_diapprove' => false, // Set ke 0 dulu
                                        ]);
                                        
                                        $createdCount++;
                                    } else {
                                        $skippedCount++;
                                    }
                                }

                                DB::commit();
                                
                                $message = "Berhasil membuat {$createdCount} subjoin untuk seluruh proses.";
                                if($skippedCount > 0) {
                                    $message .= " ({$skippedCount} subjoin sudah ada sebelumnya)";
                                }
                                
                                filamentNotification(TipeNotificationEnum::SUCCESS, $message);
                            } 
                            // Jika tipe subjoin adalah spesifik (Pra Produksi, Produksi, atau Finishing)
                            else {
                                if(!isset($data['produk_proses_id'])) {
                                    DB::rollback();
                                    Notification::make()
                                        ->title('Gagal')
                                        ->body('Proses harus dipilih')
                                        ->danger()
                                        ->send();
                                    return;
                                }

                                // Cek apakah subjoin sudah ada
                                if (TransaksiProdukSubjoin::where('transaksi_produk_id', $record->id)
                                    ->where('produk_proses_id', $data['produk_proses_id'])
                                    ->exists()) {
                                    DB::rollback();
                                    Notification::make()
                                        ->title('Gagal')
                                        ->body('Subjoin sudah ada')
                                        ->danger()
                                        ->send();
                                    return;
                                }

                                TransaksiProdukSubjoin::create([
                                    'transaksi_produk_id' => $record->id,
                                    'produk_proses_id' => $data['produk_proses_id'],
                                    'apakah_subjoin_diapprove' => false, // Set ke 0 dulu
                                ]);

                                DB::commit();
                                filamentNotification(TipeNotificationEnum::SUCCESS, 'Subjoin berhasil dibuat');
                            }
                        } catch(Exception $e) {
                            DB::rollback();
                            filamentNotification(TipeNotificationEnum::ERROR, 'Subjoin gagal dibuat karena kesalahan server. ' . $e->getMessage());
                        }
                    }),
            ]);
    }
}
