<?php

namespace App\Filament\Admin\Resources\TransaksiResource\Pages;

use Filament\Forms\Get;
use Filament\Tables\Table;
use Filament\Support\RawJs;
use App\Models\ProdukProses;
use App\Models\TransaksiProduk;
use App\Models\TransaksiProses;
use Filament\Resources\Pages\Page;
use Filament\Forms\Components\Grid;
use Filament\Tables\Actions\Action;
use App\Models\ProdukProsesKategori;
use Filament\Forms\Components\Select;
use App\Models\TransaksiProdukSubjoin;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Forms\Components\FileUpload;
use Illuminate\Contracts\Support\Htmlable;
use App\Enums\TransaksiProduk\TipeSubjoinEnum;
use Filament\Tables\Concerns\InteractsWithTable;
use App\Filament\Admin\Resources\TransaksiResource;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;

class TransaksiDetailPage extends Page implements HasTable
{
    use InteractsWithTable, InteractsWithRecord;
    protected static string $resource = TransaksiResource::class;

    protected static string $view = 'filament.admin.resources.transaksi-resource.pages.transaksi-detail-page';

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string|Htmlable
    {
        return 'Detail Transaksi: ' . $this->record->kode;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(TransaksiProduk::query()->where('transaksi_id', $this->record->id))
            ->columns([
                TextColumn::make('produk.nama')
                    ->label('Nama')
                    ->sortable()
                    ->description(fn(TransaksiProduk $record) => 'Jumlah: ' . $record->jumlah . ' pesanan'),
                TextColumn::make('subjoin')
                    ->label('List Subjoin')
                    ->getStateUsing(fn(TransaksiProduk $record) => TransaksiProdukSubjoin::where('transaksi_produk_id', $record->id)->get()->map(function(TransaksiProdukSubjoin $record) {
                        return $record->produkProses->nama;
                    })->implode(', ')),
                TextColumn::make('total_harga_produk_setelah_diskon')
                    ->label('Total Harga')
                    ->money('IDR')
                    ->sortable()
                    ->weight('bold'),
            ])
            ->actions([
                Action::make('set_subjoin')
                    ->label('Set Subjoin')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->form(function(TransaksiProduk $record) {
                        return [
                            Grid::make(2)
                                ->schema([
                                    Select::make('tipe_subjoin_id')
                                        ->label('Tipe Subjoin')
                                        ->relationship('tipeSubjoin', 'nama')
                                        ->live()
                                        ->required(),
                                    Select::make('produk_proses_id')
                                        ->label('Proses')

                                        ->options(fn(Get $get) => ProdukProses::where('produk_proses_kategori_id', $get('tipe_subjoin_id'))->where('produk_id', $record->produk_id)->pluck('nama', 'id'))
                                        ->live()
                                        ->required(),
                                    TextInput::make('nama_vendor')
                                        ->label('Nama Vendor')
                                        ->required(),
                                    TextInput::make('harga_vendor')
                                        ->label('Harga Vendor')
                                        ->numeric()
                                        ->mask(RawJs::make('$money($input)'))
                                        ->stripCharacters(',')
                                        ->prefix('Rp')
                                        ->minValue(0)
                                        ->default(0)
                                        ->required(),
                                ]),
                            FileUpload::make('faktur_vendor')
                                ->label('Faktur Vendor')
                                ->image()
                                ->required()
                                ->optimize('webp'),
                        ];
                    })
                    ->action(function(TransaksiProduk $record, array $data) {
                        $record->update([
                            'tipe_subjoin_id' => $data['tipe_subjoin_id'],
                        ]);

                        if (TransaksiProdukSubjoin::where('transaksi_produk_id', $record->id)->where('produk_proses_id', $data['produk_proses_id'])->exists()) {
                            Notification::make()
                                ->title('Gagal')
                                ->body('Subjoin sudah ada')
                                ->danger()
                                ->send();
                            return;
                        }

                        $subjoin = TransaksiProdukSubjoin::create([
                            'transaksi_produk_id' => $record->id,
                            'produk_proses_id' => $data['produk_proses_id'],
                            'nama_vendor' => $data['nama_vendor'],
                            'harga_vendor' => $data['harga_vendor'],
                        ]);

                        $prosesTransaksi = TransaksiProses::query()
                            ->where('transaksi_produk_id', $record->id)
                            ->where('produk_proses_id', $data['produk_proses_id'])
                            ->first();

                        $prosesTransaksi->update([
                            'is_subjoin' => true,
                        ]);

                        $subjoin->addMedia(public_path('storage' . $data['faktur_vendor']))->toMediaCollection('faktur_vendor');

                        Notification::make()
                            ->title('Berhasil')
                            ->body('Subjoin berhasil dibuat')
                            ->success()
                            ->send();
                    }),
            ]);
    }
}
