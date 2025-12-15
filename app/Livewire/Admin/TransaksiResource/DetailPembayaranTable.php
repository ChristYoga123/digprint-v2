<?php

namespace App\Livewire\Admin\TransaksiResource;

use Carbon\Carbon;
use Livewire\Component;
use App\Models\Transaksi;
use Filament\Tables\Table;
use App\Models\PencatatanKeuangan;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;

class DetailPembayaranTable extends Component implements HasTable, HasForms
{
    use InteractsWithTable, InteractsWithForms;

    public Transaksi $transaksi;

    public function mount(Transaksi $transaksi)
    {
        $this->transaksi = $transaksi;
    }

    public function render()
    {
        return view('livewire.admin.transaksi-resource.detail-pembayaran-table');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PencatatanKeuangan::query()
                    ->where('pencatatan_keuangan_type', Transaksi::class)
                    ->where('pencatatan_keuangan_id', $this->transaksi->id)
                    ->latest()
            )
            ->columns([
                TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->description(fn(PencatatanKeuangan $record) => Carbon::parse($record->created_at)->format('H:i')),
                TextColumn::make('jumlah_bayar')
                    ->label('Jumlah Bayar')
                    ->money('IDR')
                    ->weight('bold')
                    ->description(fn(PencatatanKeuangan $record) => $record->metode_pembayaran)
                    ->sortable(),
                TextColumn::make('keterangan')
                    ->wrap(),
            ]);
    }
}
