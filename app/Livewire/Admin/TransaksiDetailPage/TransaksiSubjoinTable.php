<?php

namespace App\Livewire\Admin\TransaksiDetailPage;

use App\Models\TransaksiProduk;
use App\Models\TransaksiProses;
use App\Models\TransaksiProdukSubjoin;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use App\Enums\TransaksiProses\StatusProsesEnum;
use App\Enums\Transaksi\StatusTransaksiEnum;
use App\Models\ProdukProsesKategori;

class TransaksiSubjoinTable extends Component implements HasTable, HasForms
{
    use InteractsWithForms, InteractsWithTable;

    public TransaksiProduk $produk;

    public function mount(TransaksiProduk $produk) {
        $this->produk = $produk;
    }

    public function render()
    {
        return view('livewire.admin.transaksi-detail-page.transaksi-subjoin-table');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(TransaksiProdukSubjoin::query()
                ->where('transaksi_produk_id', $this->produk->id))
            ->columns([
                TextColumn::make('produkProses.nama')
                    ->label('Proses yang akan di-subjoin')
                    ->description(fn(TransaksiProdukSubjoin $record) => new HtmlString('<span class="font-bold">(' . $record->produkProses->produkProsesKategori->nama . ')</span>')),
                IconColumn::make('apakah_subjoin_diapprove')
                    ->label('Status Approval')
                    ->boolean(),
                IconColumn::make('apakah_subjoin_selesai')
                    ->label('Status Selesai')
                    ->boolean(),
            ])
            ->actions([
                Action::make('selesaikan_subjoin')
                    ->label('Selesaikan')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Selesaikan Subjoin')
                    ->modalDescription(fn(TransaksiProdukSubjoin $record) => 'Selesaikan subjoin untuk proses: ' . $record->produkProses->nama . '?')
                    ->visible(fn(TransaksiProdukSubjoin $record) => $record->apakah_subjoin_diapprove && !$record->apakah_subjoin_selesai)
                    ->action(function(TransaksiProdukSubjoin $record) {
                        try {
                            DB::beginTransaction();

                            // Update status subjoin menjadi selesai
                            $record->update([
                                'apakah_subjoin_selesai' => true,
                            ]);

                            // Cari transaksi proses yang sesuai
                            $transaksiProses = TransaksiProses::where('transaksi_produk_id', $record->transaksi_produk_id)
                                ->where('produk_proses_id', $record->produk_proses_id)
                                ->first();

                            if ($transaksiProses) {
                                // Update status proses menjadi SELESAI
                                $transaksiProses->update([
                                    'status_proses' => StatusProsesEnum::SELESAI->value,
                                ]);

                                // Refresh data
                                $transaksiProduk = $this->produk->fresh();
                                $transaksiProduk->load('transaksiProses');

                                // Cek apakah semua proses sudah selesai
                                $allProcessesComplete = $transaksiProduk->transaksiProses
                                    ->every(fn($tp) => $tp->status_proses === StatusProsesEnum::SELESAI);

                                if ($allProcessesComplete) {
                                    // Update status transaksi menjadi SELESAI
                                    $transaksiProduk->transaksi->update([
                                        'status_transaksi' => StatusTransaksiEnum::SELESAI->value,
                                    ]);
                                }

                                // Jika proses design (kategori 1), update status transaksi ke PRODUKSI
                                if ($transaksiProses->produkProses->produk_proses_kategori_id == ProdukProsesKategori::praProduksiId()) {
                                    if ($transaksiProduk->transaksi->status_transaksi === StatusTransaksiEnum::BELUM) {
                                        $transaksiProduk->transaksi->update([
                                            'status_transaksi' => StatusTransaksiEnum::PRODUKSI->value,
                                        ]);
                                    }
                                }
                            }

                            DB::commit();

                            Notification::make()
                                ->title('Berhasil')
                                ->body('Subjoin berhasil diselesaikan.')
                                ->success()
                                ->send();

                        } catch (\Exception $e) {
                            DB::rollBack();
                            
                            Notification::make()
                                ->title('Gagal menyelesaikan subjoin')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
                DeleteAction::make()
                    ->hidden(fn(TransaksiProdukSubjoin $record) => $record->apakah_subjoin_diapprove || $record->apakah_subjoin_selesai)
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Hapus Terpilih')
                        ->requiresConfirmation()
                        ->modalHeading('Hapus Subjoin Terpilih')
                        ->modalDescription('Apakah Anda yakin ingin menghapus subjoin yang dipilih?')
                        ->action(function ($records) {
                            try {
                                DB::beginTransaction();
                                
                                $deletedCount = 0;
                                $skippedCount = 0;
                                
                                foreach ($records as $record) {
                                    // Hanya hapus jika belum di-approve dan belum selesai
                                    if (!$record->apakah_subjoin_diapprove && !$record->apakah_subjoin_selesai) {
                                        $record->delete();
                                        $deletedCount++;
                                    } else {
                                        $skippedCount++;
                                    }
                                }
                                
                                DB::commit();
                                
                                $message = "Berhasil menghapus {$deletedCount} subjoin.";
                                if ($skippedCount > 0) {
                                    $message .= " ({$skippedCount} subjoin tidak dapat dihapus karena sudah di-approve atau selesai)";
                                }
                                
                                Notification::make()
                                    ->title('Berhasil')
                                    ->body($message)
                                    ->success()
                                    ->send();
                                    
                            } catch (\Exception $e) {
                                DB::rollBack();
                                
                                Notification::make()
                                    ->title('Gagal menghapus subjoin')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    Action::make('selesaikan_bulk')
                        ->label('Selesaikan Terpilih')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Selesaikan Subjoin Terpilih')
                        ->modalDescription('Apakah Anda yakin ingin menyelesaikan subjoin yang dipilih?')
                        ->action(function ($records) {
                            try {
                                DB::beginTransaction();
                                
                                $completedCount = 0;
                                $skippedCount = 0;
                                
                                foreach ($records as $record) {
                                    // Hanya selesaikan jika sudah di-approve dan belum selesai
                                    if ($record->apakah_subjoin_diapprove && !$record->apakah_subjoin_selesai) {
                                        // Update status subjoin menjadi selesai
                                        $record->update([
                                            'apakah_subjoin_selesai' => true,
                                        ]);
                                        
                                        // Cari transaksi proses yang sesuai
                                        $transaksiProses = TransaksiProses::where('transaksi_produk_id', $record->transaksi_produk_id)
                                            ->where('produk_proses_id', $record->produk_proses_id)
                                            ->first();
                                        
                                        if ($transaksiProses) {
                                            // Update status proses menjadi SELESAI
                                            $transaksiProses->update([
                                                'status_proses' => StatusProsesEnum::SELESAI->value,
                                            ]);
                                        }
                                        
                                        $completedCount++;
                                    } else {
                                        $skippedCount++;
                                    }
                                }
                                
                                // Refresh data dan cek status transaksi
                                $transaksiProduk = $this->produk->fresh();
                                $transaksiProduk->load('transaksiProses');
                                
                                // Cek apakah semua proses sudah selesai
                                $allProcessesComplete = $transaksiProduk->transaksiProses
                                    ->every(fn($tp) => $tp->status_proses === StatusProsesEnum::SELESAI);
                                
                                if ($allProcessesComplete) {
                                    // Update status transaksi menjadi SELESAI
                                    $transaksiProduk->transaksi->update([
                                        'status_transaksi' => StatusTransaksiEnum::SELESAI->value,
                                    ]);
                                }
                                
                                DB::commit();
                                
                                $message = "Berhasil menyelesaikan {$completedCount} subjoin.";
                                if ($skippedCount > 0) {
                                    $message .= " ({$skippedCount} subjoin tidak dapat diselesaikan karena belum di-approve atau sudah selesai)";
                                }
                                
                                Notification::make()
                                    ->title('Berhasil')
                                    ->body($message)
                                    ->success()
                                    ->send();
                                    
                            } catch (\Exception $e) {
                                DB::rollBack();
                                
                                Notification::make()
                                    ->title('Gagal menyelesaikan subjoin')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                ]),
            ]);
    }
}
