<?php

namespace App\Livewire\Admin\TransaksiDetailPage;

use App\Models\TransaksiProduk;
use App\Models\TransaksiProdukSubjoin;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Livewire\Component;

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
                    ->label('Status approval')
                    ->boolean(),
            ])
            ->actions([
                DeleteAction::make()
                    ->hidden(fn(TransaksiProdukSubjoin $record) => $record->apakah_subjoin_diapprove)
            ]);
    }
}
