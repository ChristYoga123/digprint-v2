<?php

namespace App\Filament\Admin\Resources\StokOpnameResource\Pages;

use Filament\Forms;
use Filament\Tables;
use Filament\Actions;
use App\Models\StokOpname;
use App\Models\StokOpnameItem;
use App\Models\StokOpnameHistory;
use App\Models\BahanStokBatch;
use App\Models\BahanMutasi;
use Filament\Resources\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Enums\StokOpname\StatusEnum;
use App\Enums\StokOpname\ItemStatusEnum;
use App\Filament\Admin\Resources\StokOpnameResource;
use Illuminate\Database\Eloquent\Builder;

class ManageStokOpnameItems extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = StokOpnameResource::class;

    protected static string $view = 'filament.admin.resources.stok-opname-resource.pages.manage-stok-opname-items';

    public StokOpname $record;

    public function mount(StokOpname $record): void
    {
        $this->record = $record;
    }

    public function getTitle(): string
    {
        return 'Kelola Items: ' . $this->record->kode;
    }

    public function getBreadcrumbs(): array
    {
        return [
            StokOpnameResource::getUrl() => 'Stok Opname',
            '#' => $this->record->kode,
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                StokOpnameItem::query()
                    ->where('stok_opname_id', $this->record->id)
                    ->with(['bahan', 'bahan.satuanTerkecil'])
            )
            ->columns([
                Tables\Columns\TextColumn::make('bahan.kode')
                    ->label('Kode')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('bahan.nama')
                    ->label('Nama Bahan')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('stock_system')
                    ->label('Stok Sistem')
                    ->numeric(decimalPlaces: 2)
                    ->suffix(fn (StokOpnameItem $record) => ' ' . ($record->bahan->satuanTerkecil->nama ?? '')),
                Tables\Columns\TextInputColumn::make('stock_physical')
                    ->label('Stok Fisik')
                    ->type('number')
                    ->rules(['nullable', 'numeric', 'min:0'])
                    ->updateStateUsing(function (StokOpnameItem $record, $state) {
                        $record->stock_physical = $state;
                        
                        if ($state !== null && $state !== '') {
                            $record->difference = floatval($state) - floatval($record->stock_system);
                        } else {
                            $record->difference = null;
                        }
                        
                        $record->save();
                        
                        return $state;
                    })
                    ->disabled(fn () => in_array($this->record->status, [StatusEnum::SUBMITTED, StatusEnum::APPROVED])),
                Tables\Columns\TextColumn::make('difference')
                    ->label('Selisih')
                    ->numeric(decimalPlaces: 2)
                    ->color(fn (StokOpnameItem $record) => match (true) {
                        $record->difference === null => 'gray',
                        $record->difference > 0 => 'success',
                        $record->difference < 0 => 'danger',
                        default => 'gray',
                    })
                    ->icon(fn (StokOpnameItem $record) => match (true) {
                        $record->difference === null => null,
                        $record->difference > 0 => 'heroicon-o-arrow-trending-up',
                        $record->difference < 0 => 'heroicon-o-arrow-trending-down',
                        default => 'heroicon-o-minus',
                    })
                    ->suffix(fn (StokOpnameItem $record) => ' ' . ($record->bahan->satuanTerkecil->nama ?? '')),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                Tables\Columns\TextInputColumn::make('catatan')
                    ->label('Catatan')
                    ->disabled(fn () => in_array($this->record->status, [StatusEnum::SUBMITTED, StatusEnum::APPROVED])),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status Item')
                    ->options(ItemStatusEnum::class),
                Tables\Filters\Filter::make('has_difference')
                    ->label('Ada Selisih')
                    ->query(fn (Builder $query) => $query->whereNotNull('difference')->where('difference', '!=', 0)),
            ])
            ->actions([
                Tables\Actions\Action::make('approve_item')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Item')
                    ->modalDescription(fn (StokOpnameItem $record) => 
                        "Yakin ingin approve item {$record->bahan->nama}? " .
                        ($record->hasDifference() ? "Stok akan diupdate sesuai selisih ({$record->difference})." : "Tidak ada perubahan stok.")
                    )
                    ->action(fn (StokOpnameItem $record) => $this->approveItem($record))
                    ->visible(fn (StokOpnameItem $record) => 
                        $record->status !== ItemStatusEnum::APPROVED && 
                        $record->stock_physical !== null &&
                        $this->record->status !== StatusEnum::APPROVED &&
                        Auth::user()->can('approve_stok::opname')
                    ),
                Tables\Actions\Action::make('revise_item')
                    ->label('Revisi')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->form([
                        Forms\Components\TextInput::make('stock_physical')
                            ->label('Stok Fisik (Revisi)')
                            ->numeric()
                            ->required()
                            ->default(fn (StokOpnameItem $record) => $record->stock_physical),
                        Forms\Components\Textarea::make('catatan')
                            ->label('Catatan Revisi')
                            ->default(fn (StokOpnameItem $record) => $record->catatan),
                    ])
                    ->action(function (StokOpnameItem $record, array $data) {
                        $record->stock_physical = $data['stock_physical'];
                        $record->difference = floatval($data['stock_physical']) - floatval($record->stock_system);
                        $record->catatan = $data['catatan'];
                        $record->status = ItemStatusEnum::REVISED;
                        $record->save();
                        
                        Notification::make()
                            ->success()
                            ->title('Item berhasil direvisi')
                            ->send();
                    })
                    ->visible(fn (StokOpnameItem $record) => 
                        $record->stock_physical !== null &&
                        $this->record->status !== StatusEnum::APPROVED &&
                        Auth::user()->can('update_stok::opname')
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('bulk_approve')
                    ->label('Bulk Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Bulk Approve Items')
                    ->modalDescription('Yakin ingin approve semua item yang dipilih? Stok akan diupdate sesuai selisih masing-masing item.')
                    ->action(function ($records) {
                        $successCount = 0;
                        $skipCount = 0;
                        
                        foreach ($records as $record) {
                            if ($record->status === ItemStatusEnum::APPROVED) {
                                $skipCount++;
                                continue;
                            }
                            
                            if ($record->stock_physical === null) {
                                $skipCount++;
                                continue;
                            }
                            
                            $this->approveItem($record);
                            $successCount++;
                        }
                        
                        Notification::make()
                            ->success()
                            ->title("Bulk approve selesai")
                            ->body("$successCount item berhasil diapprove, $skipCount item dilewati.")
                            ->send();
                        
                        $this->checkAndUpdateStokOpnameStatus();
                    })
                    ->visible(fn () => 
                        $this->record->status !== StatusEnum::APPROVED &&
                        Auth::user()->can('approve_stok::opname')
                    ),
            ])
            ->headerActions([
                Tables\Actions\Action::make('back')
                    ->label('Kembali')
                    ->icon('heroicon-o-arrow-left')
                    ->color('gray')
                    ->url(StokOpnameResource::getUrl()),
                Tables\Actions\Action::make('submit')
                    ->label('Submit untuk Approval')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->action(function () {
                        $this->record->status = StatusEnum::SUBMITTED;
                        $this->record->submitted_by = Auth::id();
                        $this->record->submitted_at = now();
                        $this->record->save();
                        
                        Notification::make()
                            ->success()
                            ->title('Stok opname berhasil disubmit')
                            ->send();
                    })
                    ->visible(fn () => 
                        $this->record->status === StatusEnum::DRAFT &&
                        $this->record->items()->whereNotNull('stock_physical')->count() > 0 &&
                        Auth::user()->can('update_stok::opname')
                    ),
                Tables\Actions\Action::make('approve_all')
                    ->label('Approve Semua')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Approve Semua Item')
                    ->modalDescription('Yakin ingin approve semua item yang belum approved? Stok akan diupdate sesuai selisih masing-masing item.')
                    ->action(function () {
                        $items = $this->record->items()
                            ->where('status', '!=', 'approved')
                            ->whereNotNull('stock_physical')
                            ->get();
                        
                        foreach ($items as $item) {
                            $this->approveItem($item);
                        }
                        
                        $this->checkAndUpdateStokOpnameStatus();
                        
                        Notification::make()
                            ->success()
                            ->title('Semua item berhasil diapprove')
                            ->send();
                    })
                    ->visible(fn () => 
                        $this->record->status !== StatusEnum::APPROVED &&
                        $this->record->items()->where('status', '!=', 'approved')->whereNotNull('stock_physical')->count() > 0 &&
                        Auth::user()->can('approve_stok::opname')
                    ),
                Tables\Actions\Action::make('print_form')
                    ->label('Print Form')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn () => route('stok-opname.print-form', ['stok_opname_id' => $this->record->id]))
                    ->openUrlInNewTab()
                    ->visible(fn () => Auth::user()->can('print_stok::opname')),
            ]);
    }

    protected function approveItem(StokOpnameItem $item): void
    {
        DB::transaction(function () use ($item) {
            $bahan = $item->bahan;
            $difference = $item->difference ?? 0;
            
            if ($difference != 0) {
                // Create history record
                StokOpnameHistory::create([
                    'stok_opname_item_id' => $item->id,
                    'bahan_id' => $bahan->id,
                    'stock_before' => $item->stock_system,
                    'stock_after' => $item->stock_physical,
                    'adjustment' => abs($difference),
                    'adjustment_type' => $difference > 0 ? 'increase' : 'decrease',
                    'adjusted_by' => Auth::id(),
                    'adjusted_at' => now(),
                ]);
                
                // Adjust stock based on difference
                if ($difference > 0) {
                    // Stock increase - create adjustment batch
                    $this->createAdjustmentBatch($bahan, $difference, 'increase', $item);
                } else {
                    // Stock decrease - reduce from existing batches (FIFO)
                    $this->reduceStockFIFO($bahan, abs($difference));
                }
            }
            
            // Update item status
            $item->status = ItemStatusEnum::APPROVED;
            $item->approved_by = Auth::id();
            $item->approved_at = now();
            $item->save();
        });
    }

    protected function createAdjustmentBatch($bahan, float $amount, string $type, StokOpnameItem $item): void
    {
        // First create a mutation record for this adjustment
        $mutasi = BahanMutasi::create([
            'kode' => generateKode('ADJ'),
            'tipe' => 'MASUK',
            'bahan_id' => $bahan->id,
            'jumlah_mutasi' => $amount,
            'jumlah_satuan_terkecil' => $amount,
        ]);
        
        // Then create a batch for the adjustment
        BahanStokBatch::create([
            'bahan_id' => $bahan->id,
            'bahan_mutasi_id' => $mutasi->id,
            'jumlah_masuk' => $amount,
            'jumlah_tersedia' => $amount,
            'harga_satuan_terkecil' => 0, // Adjustment has no cost
            'harga_satuan_terbesar' => 0,
            'tanggal_masuk' => now(),
        ]);
    }

    protected function reduceStockFIFO($bahan, float $amount): void
    {
        $remaining = $amount;
        $batches = BahanStokBatch::getAvailableBatches($bahan->id, $amount);
        
        foreach ($batches as $batch) {
            if ($remaining <= 0) break;
            
            $reduceAmount = min($batch->jumlah_tersedia, $remaining);
            $batch->reduceStock($reduceAmount);
            $remaining -= $reduceAmount;
        }
    }

    protected function checkAndUpdateStokOpnameStatus(): void
    {
        if ($this->record->allItemsApproved()) {
            $this->record->status = StatusEnum::APPROVED;
            $this->record->approved_by = Auth::id();
            $this->record->approved_at = now();
            $this->record->save();
            
            Notification::make()
                ->success()
                ->title('Stok Opname telah selesai')
                ->body('Semua item telah diapprove dan stok telah diupdate.')
                ->send();
        }
    }
}
