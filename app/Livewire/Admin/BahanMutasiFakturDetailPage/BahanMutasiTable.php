<?php

namespace App\Livewire\Admin\BahanMutasiFakturDetailPage;

use Livewire\Component;
use Filament\Tables\Table;
use App\Models\BahanMutasi;
use App\Models\BahanMutasiFaktur;
use Illuminate\Support\HtmlString;
use App\Enums\BahanMutasi\TipeEnum;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Tables\Concerns\InteractsWithTable;

class BahanMutasiTable extends Component implements HasTable, HasForms
{
    use InteractsWithTable, InteractsWithForms;

    public BahanMutasiFaktur $faktur;

    public function mount(BahanMutasiFaktur $faktur): void
    {
        $this->faktur = $faktur;
    }

    public function render()
    {
        return view('livewire.admin.bahan-mutasi-faktur-detail-page.bahan-mutasi-table');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(BahanMutasi::query()->where('bahan_mutasi_faktur_id', $this->faktur->id)->with(['bahan.satuanTerbesar', 'bahan.satuanTerkecil']))
            ->columns([
                TextColumn::make('bahan.nama')
                    ->label('Bahan')
                    ->weight('bold')
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->whereHas('bahan', function (Builder $query) use ($search){
                            $query->where('nama', 'like', '%' . $search . '%')
                                ->orWhere('kode', 'like', '%' . $search . '%');
                        });
                    })
                    ->sortable()
                    ->description(fn ($record) => "({$record->bahan->kode})"),
                TextColumn::make('satuan')
                    ->label('Jumlah Satuan')
                    ->getStateUsing(function (BahanMutasi $record) {
                        // Satuan Terbesar: ... satuan <br> Satuan Terkecil: ... satuan
                        return new HtmlString("Satuan Terbesar: " . $record->jumlah_satuan_terbesar . ' ' . ($record->bahan->satuanTerbesar->nama ?? '') . " <br> Satuan Terkecil: " . $record->jumlah_satuan_terkecil . ' ' . ($record->bahan->satuanTerkecil->nama ?? ''));
                    }),
                TextColumn::make('jumlah_mutasi')
                    ->label('Jumlah Mutasi')
                    ->formatStateUsing(fn ($state, BahanMutasi $record) => $state ? formatRupiah($state) . ' ' . ($record->bahan->satuanTerkecil->nama ?? '') : '-'),
                TextColumn::make('harga')
                    ->label('Harga Mutasi')
                    ->getStateUsing(function (BahanMutasi $record) {
                        return new HtmlString("Harga Satuan Terbesar: " . formatRupiah($record->harga_satuan_terbesar) . " <br> Harga Satuan Terkecil: " . formatRupiah($record->harga_satuan_terkecil));
                    }),
                TextColumn::make('total_harga_mutasi')
                    ->label('Total Harga Mutasi')
                    ->money('IDR'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
