<?php

namespace App\Livewire\Admin\BahanMutasiFakturResource;

use Carbon\Carbon;
use Livewire\Component;
use App\Models\PencatatanKeuangan;
use App\Models\BahanMutasiFaktur;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;

class DetailPembayaranTable extends Component implements HasTable, HasForms
{
    use InteractsWithTable;
    use InteractsWithForms;

    public BahanMutasiFaktur $faktur;

    public function mount(BahanMutasiFaktur $faktur): void
    {
        $this->faktur = $faktur;
    }

    public function render()
    {
        return view('livewire.admin.bahan-mutasi-faktur-resource.detail-pembayaran-table');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PencatatanKeuangan::query()
                    ->where('pencatatan_keuangan_type', BahanMutasiFaktur::class)
                    ->where('pencatatan_keuangan_id', $this->faktur->id)
                    ->latest()
            )
            ->columns([
                TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y')
                    ->description(fn (PencatatanKeuangan $record) => Carbon::parse($record->created_at)->format('H:i'))
                    ->sortable(),
                TextColumn::make('jumlah_bayar')
                    ->label('Jumlah Bayar')
                    ->money('IDR')
                    ->weight('bold')
                    ->description(fn (PencatatanKeuangan $record) => $record->metode_pembayaran)
                    ->sortable(),
                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->wrap(),
            ]);
    }
}

