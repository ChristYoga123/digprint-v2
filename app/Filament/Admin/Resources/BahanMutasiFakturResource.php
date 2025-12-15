<?php

namespace App\Filament\Admin\Resources;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Support\RawJs;
use Filament\Resources\Resource;
use App\Models\BahanMutasiFaktur;
use App\Models\PencatatanKeuangan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\Livewire;
use App\Enums\BahanMutasiFaktur\StatusPembayaranEnum;
use App\Filament\Admin\Resources\BahanMutasiFakturResource\Pages;
use App\Livewire\Admin\BahanMutasiFakturResource\DetailPembayaranTable;
use App\Filament\Admin\Resources\BahanMutasiFakturResource\RelationManagers;

class BahanMutasiFakturResource extends Resource
{
    protected static ?string $model = BahanMutasiFaktur::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    protected static ?string $navigationLabel = 'Faktur';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('supplier'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('kode')
                    ->searchable(),
                Tables\Columns\TextColumn::make('supplier.nama_perusahaan')
                    ->label('Supplier')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_harga')
                    ->sortable()
                    ->getStateUsing(function(BahanMutasiFaktur $record) {
                        $totalHarga = $record->total_harga;
                        $totalDiskon = $record->total_diskon ?? 0;
                        $hargaSetelahDiskon = $record->total_harga_setelah_diskon ?? $totalHarga;
                        if (empty($totalDiskon) || $totalDiskon == 0 || $totalDiskon == '0') {
                            return new HtmlString('<span class="font-bold">' . formatRupiah($totalHarga) . '</span>');
                        }
                        if (!empty($totalDiskon) && $totalDiskon != 0 && $totalDiskon != '0') {
                            return new HtmlString(
                                '<div>' .
                                    '<span style="text-decoration: line-through; color: #ff0000; font-size: 1rem;">'
                                        . formatRupiah($totalHarga) .
                                    '</span><br>' .
                                    '<span style="color: #26d82f; font-weight:bold; font-size: 1rem; animation: blink 1s steps(1) infinite;">'
                                        . formatRupiah($hargaSetelahDiskon) .
                                    '</span>'
                                . '</div>'
                            );
                        }
                        return new HtmlString(formatRupiah($totalHarga));
                    }),
                Tables\Columns\TextColumn::make('status_pembayaran')
                    ->badge(StatusPembayaranEnum::class),
                Tables\Columns\TextColumn::make('tanggal_jatuh_tempo')
                    ->sortable()
                    ->getStateUsing(fn(BahanMutasiFaktur $record) => $record->tanggal_jatuh_tempo ? Carbon::parse($record->tanggal_jatuh_tempo)->translatedFormat('d M Y H:i:s') : '-'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('detail_faktur')
                    ->label('Detail Faktur')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->url(fn (BahanMutasiFaktur $record) => Pages\BahanMutasiFakturDetailPage::getUrl(['record' => $record])),
                Tables\Actions\Action::make('detail_pembayaran')
                    ->label('Detail Pembayaran')
                    ->icon('heroicon-o-credit-card')
                    ->color('primary')
                    ->infolist(fn (BahanMutasiFaktur $record) => [
                        Livewire::make(DetailPembayaranTable::class, ['faktur' => $record]),
                    ]),
                Tables\Actions\Action::make('bayar_top')
                    ->label('Bayar TOP')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->visible(fn (BahanMutasiFaktur $record) => $record->status_pembayaran === StatusPembayaranEnum::TERM_OF_PAYMENT)
                    ->form([
                        // filter metode pembayaran based on supplier master data include CASH
                        Forms\Components\Select::make('metode_pembayaran')
                            ->label('Metode Pembayaran')
                            ->options(function (BahanMutasiFaktur $record) {
                                return getSupplierPaymentMethods($record->supplier);
                            })
                            ->searchable()
                            ->helperText('Opsional, isi jika ingin mencatat metode pembayaran'),
                        Forms\Components\TextInput::make('jumlah_bayar')
                            ->label('Jumlah Bayar')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->minValue(1)
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters([',', '.'])
                            ->helperText(function (BahanMutasiFaktur $record) {
                                $dibayar = $record->pencatatanKeuangans()->sum('jumlah_bayar');
                                $totalTagihan = $record->total_harga_setelah_diskon ?? max(0, ($record->total_harga ?? 0) - ($record->total_diskon ?? 0));
                                $sisa = max(0, $totalTagihan - $dibayar);
                                return 'Sisa tagihan: ' . formatRupiah($sisa);
                            }),
                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->rows(3)
                            ->placeholder('Keterangan pembayaran (opsional)'),
                    ])
                    ->action(function (BahanMutasiFaktur $record, array $data) {
                        $jumlahBayarParsed = (int) str_replace(['.', ','], '', (string) ($data['jumlah_bayar'] ?? 0));

                        if ($jumlahBayarParsed <= 0) {
                            Notification::make()
                                ->title('Jumlah bayar harus lebih dari 0')
                                ->warning()
                                ->send();
                            return;
                        }

                        $totalTagihan = $record->total_harga_setelah_diskon ?? max(0, ($record->total_harga ?? 0) - ($record->total_diskon ?? 0));
                        $sudahBayar = $record->pencatatanKeuangans()->sum('jumlah_bayar');
                        $sisaTagihan = max(0, $totalTagihan - $sudahBayar);

                        if ($jumlahBayarParsed > $sisaTagihan) {
                            Notification::make()
                                ->title('Jumlah bayar tidak boleh lebih dari sisa tagihan')
                                ->warning()
                                ->send();
                            return;
                        }

                        try {
                            DB::beginTransaction();

                            PencatatanKeuangan::create([
                                'pencatatan_keuangan_type' => BahanMutasiFaktur::class,
                                'pencatatan_keuangan_id' => $record->id,
                                'user_id' => Auth::id(),
                                'jumlah_bayar' => $jumlahBayarParsed,
                                'metode_pembayaran' => $data['metode_pembayaran'] ?? null,
                                'keterangan' => $data['keterangan'] ?? 'Pembayaran tambahan faktur ' . $record->kode,
                                'approved_by' => null,
                                'approved_at' => null,
                            ]);

                            $totalPembayaran = PencatatanKeuangan::where('pencatatan_keuangan_type', BahanMutasiFaktur::class)
                                ->where('pencatatan_keuangan_id', $record->id)
                                ->sum('jumlah_bayar');

                            $record->update([
                                'metode_pembayaran' => $data['metode_pembayaran'] ?? $record->metode_pembayaran,
                            ]);

                            if ($totalPembayaran >= $totalTagihan) {
                                $record->update([
                                    'status_pembayaran' => StatusPembayaranEnum::LUNAS->value,
                                    'tanggal_pembayaran' => now(),
                                ]);

                                Notification::make()
                                    ->title('Faktur sudah lunas')
                                    ->success()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title('Pembayaran berhasil ditambahkan')
                                    ->success()
                                    ->send();
                            }

                            DB::commit();
                        } catch (\Exception $e) {
                            DB::rollBack();

                            Notification::make()
                                ->title('Gagal menambah pembayaran')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageBahanMutasiFakturs::route('/'),
            'detail' => Pages\BahanMutasiFakturDetailPage::route('/{record}/detail'),
        ];
    }
}
