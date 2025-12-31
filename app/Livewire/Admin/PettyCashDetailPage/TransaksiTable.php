<?php

namespace App\Livewire\Admin\PettyCashDetailPage;

use Carbon\Carbon;
use Livewire\Component;
use App\Models\PettyCash;
use App\Models\Transaksi;
use Filament\Tables\Table;
use App\Models\PencatatanKeuangan;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;

class TransaksiTable extends Component implements HasTable, HasForms
{
    use InteractsWithForms, InteractsWithTable;

    public PettyCash $pettyCash;

    public function mount(PettyCash $pettyCash): void
    {
        $this->pettyCash = $pettyCash;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PencatatanKeuangan::query()
                    ->where('pencatatan_keuangan_type', Transaksi::class)
                    ->where('metode_pembayaran', 'Cash')
                    ->whereDate('created_at', $this->pettyCash->tanggal)
                    ->with(['user'])
                    ->latest()
            )
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('kode_transaksi')
                    ->label('Kode Transaksi')
                    ->getStateUsing(function (PencatatanKeuangan $record) {
                        $transaksi = Transaksi::find($record->pencatatan_keuangan_id);
                        return $transaksi?->kode ?? '-';
                    })
                    ->searchable(query: function ($query, string $search) {
                        return $query->whereIn('pencatatan_keuangan_id', function ($subQuery) use ($search) {
                            $subQuery->select('id')
                                ->from('transaksis')
                                ->where('kode', 'like', "%{$search}%");
                        });
                    }),
                TextColumn::make('customer_nama')
                    ->label('Customer')
                    ->getStateUsing(function (PencatatanKeuangan $record) {
                        $transaksi = Transaksi::with('customer')->find($record->pencatatan_keuangan_id);
                        return $transaksi?->customer?->nama ?? '-';
                    }),
                TextColumn::make('jumlah_bayar')
                    ->label('Jumlah Bayar')
                    ->money('IDR')
                    ->weight('bold')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Kasir')
                    ->searchable(),
                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->wrap()
                    ->default('-'),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Tidak ada transaksi cash')
            ->emptyStateDescription('Belum ada transaksi yang dibayar dengan cash pada tanggal ini.');
    }

    public function render()
    {
        return view('livewire.admin.petty-cash-detail-page.transaksi-table');
    }
}

