<?php

namespace App\Filament\Admin\Resources\PettyCashResource\Pages;

use Carbon\Carbon;
use App\Models\PettyCash;
use App\Models\Transaksi;
use Filament\Infolists\Infolist;
use App\Models\PencatatanKeuangan;
use Filament\Resources\Pages\Page;
use App\Enums\PettyCash\StatusEnum;
use App\Enums\PettyCashFlow\TipeEnum;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Section;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Infolists\Components\Livewire;
use Filament\Infolists\Components\TextEntry;
use App\Filament\Admin\Resources\PettyCashResource;
use App\Livewire\Admin\PettyCashDetailPage\ExpenseTable;
use App\Livewire\Admin\PettyCashDetailPage\RequestTable;
use App\Livewire\Admin\PettyCashDetailPage\TransaksiTable;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use App\Livewire\Admin\PettyCashDetailPage\PermintaanTable;
use App\Livewire\Admin\PettyCashDetailPage\PengeluaranTable;

class PettyCashDetailPage extends Page
{
    use InteractsWithRecord;
    protected static string $resource = PettyCashResource::class;

    protected static string $view = 'filament.admin.resources.petty-cash-resource.pages.petty-cash-detail-page';

    public function getTitle(): string|Htmlable
    {
        return 'Detail Petty Cash';
    }

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->record)
            ->schema([
                Section::make('Detail Petty Cash Session')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('tanggal')
                                    ->label('Tanggal')
                                    ->formatStateUsing(fn ($state) => Carbon::parse($state)->translatedFormat('d M Y'))
                                    ->weight('bold')
                                    ->size(TextEntry\TextEntrySize::Large),
                                TextEntry::make('status')
                                    ->label('Status')
                                    ->badge(StatusEnum::class),
                                TextEntry::make('uang_buka')
                                    ->label('Uang Buka Toko')
                                    ->money('IDR')
                                    ->weight('bold'),
                                TextEntry::make('uang_tutup')
                                    ->label('Uang Tutup Toko')
                                    ->money('IDR')
                                    ->weight('bold')
                                    ->default('-')
                                    ->visible(fn (PettyCash $record) => $record->uang_tutup !== null),
                                TextEntry::make('userBuka.name')
                                    ->label('User Buka Toko'),
                                TextEntry::make('userTutup.name')
                                    ->label('User Tutup Toko')
                                    ->default('-')
                                    ->visible(fn (PettyCash $record) => $record->userTutup !== null),
                                TextEntry::make('approvedByBuka.name')
                                    ->label('Approved Buka Oleh')
                                    ->default('-')
                                    ->visible(fn (PettyCash $record) => $record->approved_by_buka !== null),
                                TextEntry::make('approved_at_buka')
                                    ->label('Tanggal Approved Buka')
                                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->translatedFormat('d M Y H:i') : '-')
                                    ->visible(fn (PettyCash $record) => $record->approved_at_buka !== null),
                                TextEntry::make('approvedByTutup.name')
                                    ->label('Approved Tutup Oleh')
                                    ->default('-')
                                    ->visible(fn (PettyCash $record) => $record->approved_by_tutup !== null),
                                TextEntry::make('approved_at_tutup')
                                    ->label('Tanggal Approved Tutup')
                                    ->formatStateUsing(fn ($state) => $state ? Carbon::parse($state)->translatedFormat('d M Y H:i') : '-')
                                    ->visible(fn (PettyCash $record) => $record->approved_at_tutup !== null),
                                TextEntry::make('keterangan_buka')
                                    ->label('Keterangan Buka')
                                    ->columnSpanFull()
                                    ->default('-'),
                                TextEntry::make('keterangan_tutup')
                                    ->label('Keterangan Tutup')
                                    ->columnSpanFull()
                                    ->default('-')
                                    ->visible(fn (PettyCash $record) => $record->keterangan_tutup !== null),
                            ]),
                        Section::make('Summary')
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        TextEntry::make('uang_buka')
                                            ->label('Uang Buka')
                                            ->money('IDR')
                                            ->color('info')
                                            ->weight('bold'),
                                        TextEntry::make('total_permintaan')
                                            ->label('Total Permintaan (Approved)')
                                            ->money('IDR')
                                            ->color('warning')
                                            ->weight('bold')
                                            ->getStateUsing(fn (PettyCash $record) => $record->pettyCashFlows->where('tipe', TipeEnum::PERMINTAAN)->where('approved_by', '!=', null)->sum('jumlah')),
                                        TextEntry::make('total_transaksi_cash')
                                            ->label('Total Transaksi Cash')
                                            ->money('IDR')
                                            ->color('success')
                                            ->weight('bold')
                                            ->getStateUsing(function(){
                                                $transaksiCash = PencatatanKeuangan::where('pencatatan_keuangan_type', Transaksi::class)
                                                    ->where('metode_pembayaran', 'Cash')
                                                    ->whereDate('created_at', $this->record->tanggal)
                                                    ->sum('jumlah_bayar');

                                                return $transaksiCash ?? 0;
                                            }),
                                        TextEntry::make('total_pengeluaran')
                                            ->label('Total Pengeluaran (Approved)')
                                            ->money('IDR')
                                            ->color('danger')
                                            ->weight('bold')
                                            ->getStateUsing(fn (PettyCash $record) => $record->pettyCashFlows->where('tipe', TipeEnum::PENGELUARAN)->where('approved_by', '!=', null)->sum('jumlah')),
                                        TextEntry::make('uang_tutup')
                                            ->label('Uang Tutup')
                                            ->money('IDR')
                                            ->color('warning')
                                            ->weight('bold')
                                            ->getStateUsing(fn (PettyCash $record) => $record->uang_tutup ?? 0),
                                    ]),
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('uang_seharusnya')
                                            ->label('Uang Seharusnya')
                                            ->money('IDR')
                                            ->color('info')
                                            ->weight('bold')
                                            ->getStateUsing(function(){
                                                // Uang Buka
                                                $uangBuka = $this->record->uang_buka ?? 0;
                                                
                                                // Uang Minta yang diapprove
                                                $totalPermintaanApproved = $this->record->pettyCashFlows
                                                    ->where('tipe', TipeEnum::PERMINTAAN)
                                                    ->where('approved_by', '!=', null)
                                                    ->sum('jumlah') ?? 0;
                                                
                                                // Uang Cash (transaksi cash)
                                                $totalTransaksiCash = PencatatanKeuangan::where('pencatatan_keuangan_type', Transaksi::class)
                                                    ->where('metode_pembayaran', 'Cash')
                                                    ->whereDate('created_at', $this->record->tanggal)
                                                    ->sum('jumlah_bayar') ?? 0;
                                                
                                                // Uang Pengeluaran yang diapprove
                                                $totalPengeluaranApproved = $this->record->pettyCashFlows
                                                    ->where('tipe', TipeEnum::PENGELUARAN)
                                                    ->where('approved_by', '!=', null)
                                                    ->sum('jumlah') ?? 0;
                                                
                                                // Uang Seharusnya = Uang Buka + Uang Minta yang diapprove + Uang Cash - Uang Pengeluaran yang diapprove
                                                return $uangBuka + $totalPermintaanApproved + $totalTransaksiCash - $totalPengeluaranApproved;
                                            })
                                            ->visible(fn (PettyCash $record) => $record->uang_tutup !== null),
                                        TextEntry::make('selisih')
                                            ->label('Selisih')
                                            ->getStateUsing(function(){
                                                $uangTutup = $this->record->uang_tutup ?? 0;
                                                
                                                // Uang Buka
                                                $uangBuka = $this->record->uang_buka ?? 0;
                                                
                                                // Uang Minta yang diapprove
                                                $totalPermintaanApproved = $this->record->pettyCashFlows
                                                    ->where('tipe', TipeEnum::PERMINTAAN)
                                                    ->where('approved_by', '!=', null)
                                                    ->sum('jumlah') ?? 0;
                                                
                                                // Uang Cash (transaksi cash)
                                                $totalTransaksiCash = PencatatanKeuangan::where('pencatatan_keuangan_type', Transaksi::class)
                                                    ->where('metode_pembayaran', 'Cash')
                                                    ->whereDate('created_at', $this->record->tanggal)
                                                    ->sum('jumlah_bayar') ?? 0;
                                                
                                                // Uang Pengeluaran yang diapprove
                                                $totalPengeluaranApproved = $this->record->pettyCashFlows
                                                    ->where('tipe', TipeEnum::PENGELUARAN)
                                                    ->where('approved_by', '!=', null)
                                                    ->sum('jumlah') ?? 0;
                                                
                                                // Uang Seharusnya
                                                $uangSeharusnya = $uangBuka + $totalPermintaanApproved + $totalTransaksiCash - $totalPengeluaranApproved;
                                                
                                                // Selisih = Uang Tutup - Uang Seharusnya
                                                return $uangTutup - $uangSeharusnya;
                                            })
                                            ->formatStateUsing(function($state){
                                                $uangTutup = $this->record->uang_tutup ?? 0;
                                                
                                                // Uang Buka
                                                $uangBuka = $this->record->uang_buka ?? 0;
                                                
                                                // Uang Minta yang diapprove
                                                $totalPermintaanApproved = $this->record->pettyCashFlows
                                                    ->where('tipe', TipeEnum::PERMINTAAN)
                                                    ->where('approved_by', '!=', null)
                                                    ->sum('jumlah') ?? 0;
                                                
                                                // Uang Cash (transaksi cash)
                                                $totalTransaksiCash = PencatatanKeuangan::where('pencatatan_keuangan_type', Transaksi::class)
                                                    ->where('metode_pembayaran', 'Cash')
                                                    ->whereDate('created_at', $this->record->tanggal)
                                                    ->sum('jumlah_bayar') ?? 0;
                                                
                                                // Uang Pengeluaran yang diapprove
                                                $totalPengeluaranApproved = $this->record->pettyCashFlows
                                                    ->where('tipe', TipeEnum::PENGELUARAN)
                                                    ->where('approved_by', '!=', null)
                                                    ->sum('jumlah') ?? 0;
                                                
                                                // Uang Seharusnya
                                                $uangSeharusnya = $uangBuka + $totalPermintaanApproved + $totalTransaksiCash - $totalPengeluaranApproved;
                                                
                                                // Selisih = Uang Tutup - Uang Seharusnya
                                                $selisih = $uangTutup - $uangSeharusnya;
                                                
                                                return \Illuminate\Support\Number::currency($selisih, 'IDR') . ' (' . ($selisih >= 0 ? 'Kelebihan' : 'Kekurangan') . ')';
                                            })
                                            ->color(function(){
                                                $uangTutup = $this->record->uang_tutup ?? 0;
                                                
                                                // Uang Buka
                                                $uangBuka = $this->record->uang_buka ?? 0;
                                                
                                                // Uang Minta yang diapprove
                                                $totalPermintaanApproved = $this->record->pettyCashFlows
                                                    ->where('tipe', TipeEnum::PERMINTAAN)
                                                    ->where('approved_by', '!=', null)
                                                    ->sum('jumlah') ?? 0;
                                                
                                                // Uang Cash (transaksi cash)
                                                $totalTransaksiCash = PencatatanKeuangan::where('pencatatan_keuangan_type', Transaksi::class)
                                                    ->where('metode_pembayaran', 'Cash')
                                                    ->whereDate('created_at', $this->record->tanggal)
                                                    ->sum('jumlah_bayar') ?? 0;
                                                
                                                // Uang Pengeluaran yang diapprove
                                                $totalPengeluaranApproved = $this->record->pettyCashFlows
                                                    ->where('tipe', TipeEnum::PENGELUARAN)
                                                    ->where('approved_by', '!=', null)
                                                    ->sum('jumlah') ?? 0;
                                                
                                                // Uang Seharusnya
                                                $uangSeharusnya = $uangBuka + $totalPermintaanApproved + $totalTransaksiCash - $totalPengeluaranApproved;
                                                
                                                // Selisih = Uang Tutup - Uang Seharusnya
                                                $selisih = $uangTutup - $uangSeharusnya;
                                                
                                                return $selisih >= 0 ? 'success' : 'danger';
                                            })
                                            ->weight('bold')
                                            ->visible(fn (PettyCash $record) => $record->uang_tutup !== null),
                                    ]),
                                TextEntry::make('keterangan_rekonsiliasi')
                                    ->label('')
                                    ->getStateUsing(fn () => 'Uang Seharusnya = Uang Buka + Uang Minta yang diapprove + Uang Cash - Uang Pengeluaran yang diapprove')
                                    ->visible(fn (PettyCash $record) => $record->uang_tutup !== null)
                                    ->columnSpanFull()
                                    ->color('gray'),
                            ])
                            ->columnSpanFull(),
                        Section::make('Pengeluaran Operasional')
                            ->description('Daftar pengeluaran operasional harian dari petty cash')
                            ->schema([
                                Livewire::make(PengeluaranTable::class, ['pettyCash' => $this->record]),
                            ])
                            ->columnSpanFull()
                            ->collapsible(),
                        Section::make('Permintaan Dana')
                            ->description('Daftar permintaan dana petty cash')
                            ->schema([
                                Livewire::make(PermintaanTable::class, ['pettyCash' => $this->record]),
                            ])
                            ->columnSpanFull()
                            ->collapsible(),
                        Section::make('Transaksi Cash')
                            ->description('Daftar transaksi yang dibayar dengan cash pada tanggal ini')
                            ->schema([
                                Livewire::make(TransaksiTable::class, ['session' => $this->record]),
                            ])
                            ->columnSpanFull()
                            ->collapsible(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
