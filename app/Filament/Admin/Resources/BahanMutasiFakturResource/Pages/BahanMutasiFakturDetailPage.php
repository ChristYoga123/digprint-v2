<?php

namespace App\Filament\Admin\Resources\BahanMutasiFakturResource\Pages;

use Carbon\Carbon;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use App\Models\BahanMutasiFaktur;
use Filament\Resources\Pages\Page;
use Illuminate\Support\HtmlString;
use Filament\Infolists\Components\Livewire;
use App\Enums\BahanMutasiFaktur\StatusPembayaranEnum;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use App\Filament\Admin\Resources\BahanMutasiFakturResource;
use App\Livewire\Admin\BahanMutasiFakturDetailPage\BahanMutasiTable;

class BahanMutasiFakturDetailPage extends Page
{
    use InteractsWithRecord;
    protected static string $resource = BahanMutasiFakturResource::class;

    protected static string $view = 'filament.admin.resources.bahan-mutasi-faktur-resource.pages.bahan-mutasi-faktur-detail-page';

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->record->load(['supplier', 'po', 'pencatatanKeuangans']);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->record)
            ->schema([
                Infolists\Components\Section::make('Detail Supplier & Faktur')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('kode')
                                    ->label('Kode Faktur')
                                    ->weight('bold')
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                                Infolists\Components\TextEntry::make('supplier.nama_perusahaan')
                                    ->label('Nama Perusahaan')
                                    ->weight('bold'),
                                Infolists\Components\TextEntry::make('supplier.kode')
                                    ->label('Kode Supplier')
                                    ->formatStateUsing(fn ($state) => $state ? "SPL-{$state}" : '-')
                                    ->columnSpanFull(),
                                Infolists\Components\TextEntry::make('po.kode')
                                    ->label('Kode PO')
                                    ->formatStateUsing(fn ($state) => $state ?: '-')
                                    ->visible(fn (BahanMutasiFaktur $record) => $record->po_id !== null)
                                    ->columns(2),
                                Infolists\Components\TextEntry::make('supplier.nama_sales')
                                    ->label('Nama Sales'),
                                Infolists\Components\TextEntry::make('supplier.no_hp_sales')
                                    ->label('No. HP Sales'),
                                Infolists\Components\TextEntry::make('supplier.alamat_perusahaan')
                                    ->label('Alamat Perusahaan'),
                                Infolists\Components\TextEntry::make('supplier.alamat_gudang')
                                    ->label('Alamat Gudang'),
                                Infolists\Components\TextEntry::make('total_harga')
                                    ->label('Total Harga')
                                    ->weight('bold')
                                    ->getStateUsing(function (BahanMutasiFaktur $record) {
                                        $totalHarga = $record->total_harga;
                                        $totalDiskon = $record->total_diskon ?? 0;
                                        $hargaSetelahDiskon = $record->total_harga_setelah_diskon ?? $totalHarga;
                                        if (empty($totalDiskon) || $totalDiskon == 0 || $totalDiskon == '0') {
                                            return new HtmlString(
                                                '<span>' . formatRupiah($totalHarga) . '</span>'
                                            );
                                        }
                                        if (!empty($totalDiskon) && $totalDiskon != 0 && $totalDiskon != '0') {
                                            return new HtmlString(
                                                '<div>' .
                                                    '<span style="text-decoration: line-through; color: #ff0000; font-size: 1rem;">'
                                                        . formatRupiah($totalHarga) .
                                                    '</span><br>' .
                                                    '<span style="color: #26d82f; font-weight:bold; font-size: 1.2rem; animation: blink 1s steps(1) infinite;">'
                                                        . formatRupiah($hargaSetelahDiskon) .
                                                    '</span>'
                                                . '</div>'
                                            );
                                        }
                                    }),
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Tanggal Dibuat')
                                    ->formatStateUsing(fn ($state) => Carbon::parse($state)->translatedFormat('d M Y H:i')),
                            ]),
                        Infolists\Components\Section::make('Detail Bahan Mutasi')
                            ->schema([
                                Livewire::make(BahanMutasiTable::class, ['faktur' => $this->record]),
                            ])
                            ->columnSpanFull()
                            ->collapsible(),
                    ])
                    ->columnSpan(1),
                Infolists\Components\Section::make('Bukti Faktur')
                    ->schema([
                        Infolists\Components\SpatieMediaLibraryImageEntry::make('foto_faktur')
                            ->label('Foto Faktur')
                            ->collection('bahan_mutasi_faktur')
                            ->size('100%')
                    ])
                    ->columnSpan(1),
            ])
            ->columns(2);
    }
}

