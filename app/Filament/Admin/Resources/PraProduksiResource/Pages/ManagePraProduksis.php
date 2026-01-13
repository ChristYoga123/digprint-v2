<?php

namespace App\Filament\Admin\Resources\PraProduksiResource\Pages;

use App\Filament\Admin\Resources\PraProduksiResource;
use App\Models\TransaksiProses;
use App\Models\KaryawanPekerjaan;
use App\Enums\TransaksiProses\StatusProsesEnum;
use App\Models\ProdukProsesKategori;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Carbon\Carbon;

class ManagePraProduksis extends ManageRecords
{
    protected static string $resource = PraProduksiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('lihat_riwayat')
                ->label('Lihat Riwayat Produksi')
                ->icon('heroicon-o-clock')
                ->color('info')
                ->visible(fn() => Auth::user()->can('lihat_seluruhnya_riwayat_pra::produksi') || Auth::user()->can('lihat_sebagian_riwayat_pra::produksi'))
                ->modalHeading('Riwayat Pra Produksi (Design)')
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Tutup')
                ->modalContent(function () {
                    $query = TransaksiProses::query()
                        ->where('status_proses', StatusProsesEnum::SELESAI->value)
                        ->whereHas('produkProses', function($q) {
                            $q->where('produk_proses_kategori_id', ProdukProsesKategori::praProduksiId()); // Design
                        })
                        ->with([
                            'transaksiProduk.transaksi.customer',
                            'transaksiProduk.produk',
                            'produkProses',
                            'karyawanPekerjaans.karyawan',
                        ])
                        ->orderBy('completed_at', 'desc')
                        ->limit(50);
                    
                    // Jika user hanya punya permission lihat_sebagian_riwayat
                    if (!Auth::user()->can('lihat_seluruhnya_riwayat_pra::produksi') && Auth::user()->can('lihat_sebagian_riwayat_pra::produksi')) {
                        $query->whereHas('karyawanPekerjaans', function($q) {
                            $q->where('karyawan_id', Auth::id());
                        });
                    }
                    
                    $records = $query->get();
                    
                    if ($records->isEmpty()) {
                        return new HtmlString('<div class="text-center py-8 text-gray-500">Tidak ada riwayat pra produksi</div>');
                    }
                    
                    $html = '<div class="overflow-x-auto"><table class="w-full text-sm">';
                    $html .= '<thead class="bg-gray-100 dark:bg-gray-800">';
                    $html .= '<tr>';
                    $html .= '<th class="px-4 py-2 text-left">Kode Transaksi</th>';
                    $html .= '<th class="px-4 py-2 text-left">Produk</th>';
                    $html .= '<th class="px-4 py-2 text-left">Proses</th>';
                    $html .= '<th class="px-4 py-2 text-left">Dikerjakan Oleh</th>';
                    $html .= '<th class="px-4 py-2 text-left">Selesai Pada</th>';
                    $html .= '</tr>';
                    $html .= '</thead>';
                    $html .= '<tbody class="divide-y divide-gray-200 dark:divide-gray-700">';
                    
                    foreach ($records as $record) {
                        $karyawanNames = $record->karyawanPekerjaans->pluck('karyawan.name')->filter()->join(', ') ?: '-';
                        $completedAt = $record->completed_at ? Carbon::parse($record->completed_at)->format('d M Y H:i') : '-';
                        
                        $html .= '<tr>';
                        $html .= '<td class="px-4 py-2">' . e($record->transaksiProduk?->transaksi?->kode ?? '-') . '</td>';
                        $html .= '<td class="px-4 py-2">' . e($record->transaksiProduk?->produk?->nama ?? '-') . '</td>';
                        $html .= '<td class="px-4 py-2">' . e($record->produkProses?->nama ?? '-') . '</td>';
                        $html .= '<td class="px-4 py-2">' . e($karyawanNames) . '</td>';
                        $html .= '<td class="px-4 py-2">' . $completedAt . '</td>';
                        $html .= '</tr>';
                    }
                    
                    $html .= '</tbody></table></div>';
                    
                    return new HtmlString($html);
                }),
            Actions\CreateAction::make(),
        ];
    }
}
