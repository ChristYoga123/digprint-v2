<?php

namespace App\Filament\Admin\Resources;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use App\Models\Transaksi;
use Filament\Tables\Table;
use Filament\Support\RawJs;
use Filament\Resources\Resource;
use App\Models\PencatatanKeuangan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use App\Enums\Transaksi\TipeSubjoinEnum;
use Filament\Notifications\Notification;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\Livewire;
use App\Enums\Transaksi\StatusTransaksiEnum;
use App\Enums\Transaksi\StatusPembayaranEnum;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Admin\Resources\TransaksiResource\Pages;
use App\Livewire\Admin\TransaksiResource\DetailPembayaranTable;
use App\Filament\Admin\Resources\TransaksiResource\RelationManagers;

class TransaksiResource extends Resource
{
    protected static ?string $model = Transaksi::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Transaksi';

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
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('kode')
                    ->searchable(query: fn(Builder $query, string $search) => $query->where('kode', 'like', '%' . $search . '%')->orWhereHas('customer', function ($query) use ($search) {
                        $query->where('nama', 'like', '%' . $search . '%');
                    }))
                    ->weight('bold')
                    ->description(fn(Transaksi $record) => "({$record->customer->nama})"),
                Tables\Columns\TextColumn::make('total_harga_transaksi_setelah_diskon')
                    ->label('Total Transaksi')
                    ->money('IDR')
                    ->sortable()
                    ->description(function(Transaksi $record) {
                        $dibayar = $record->pencatatanKeuangans->sum('jumlah_bayar');
                        $sisa = max(0, $record->total_harga_transaksi_setelah_diskon - $dibayar);
                        if ($sisa > 0) {
                            return new HtmlString('<span style="color: red;" class="font-bold">Tagihan: ' . formatRupiah($sisa) . '</span>');
                        }
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y')
                    ->weight('bold')
                    ->description(fn(Transaksi $record) => Carbon::parse($record->created_at)->format('H:i'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('status_pembayaran')
                    ->label('Status Pembayaran')
                    ->badge(StatusPembayaranEnum::class)
                    ->sortable(),
                Tables\Columns\TextColumn::make('status_transaksi')
                    ->label('Status Pengerjaan')
                    ->badge(StatusTransaksiEnum::class)
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status_transaksi')
                    ->label('Status Pengerjaan')
                    ->options(StatusTransaksiEnum::class),
                Tables\Filters\SelectFilter::make('status_pembayaran')
                    ->label('Status Pembayaran')
                    ->options(StatusPembayaranEnum::class),
            ], layout: FiltersLayout::AboveContent)
            ->actions([
                Tables\Actions\Action::make('detail_transaksi')
                    ->label('Detail Transaksi')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->url(fn(Transaksi $record) => Pages\TransaksiDetailPage::getUrl(['record' => $record->id])),
                Tables\Actions\Action::make('detail_pembayaran')
                    ->label('Detail Pembayaran')
                    ->icon('heroicon-o-credit-card')
                    ->color('primary')
                    ->infolist(fn(Transaksi $record) => [
                        Livewire::make(DetailPembayaranTable::class, ['transaksi' => $record]),
                    ]),
                Tables\Actions\Action::make('bayar_top')
                    ->label('Bayar TOP')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->visible(function (Transaksi $record) {
                        // Cek apakah status pembayaran adalah TOP
                        // Handle baik enum instance maupun string value
                        $status = $record->status_pembayaran;
                        if ($status instanceof StatusPembayaranEnum) {
                            return $status === StatusPembayaranEnum::TERM_OF_PAYMENT;
                        }
                        // Fallback: bandingkan dengan value jika masih string
                        return $status === StatusPembayaranEnum::TERM_OF_PAYMENT->value;
                    })
                    ->form([
                        Forms\Components\Select::make('metode_pembayaran')
                            ->label('Metode Pembayaran')
                            ->options(getBankData())
                            ->searchable()
                            ->required(),
                        Forms\Components\TextInput::make('jumlah_bayar')
                            ->label('Jumlah Bayar')
                            ->numeric()
                            ->prefix('Rp')
                            ->required()
                            ->minValue(1)
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->helperText(fn (Transaksi $record) => 'Sisa tagihan: ' . formatRupiah($record->total_harga_transaksi_setelah_diskon - $record->pencatatanKeuangans->sum('jumlah_bayar'))),
                        Forms\Components\Textarea::make('keterangan')
                            ->label('Keterangan')
                            ->placeholder('Keterangan pembayaran (opsional)')
                            ->rows(3),
                    ])
                    ->action(function (Transaksi $record, array $data) {
                        // Parse jumlah bayar (remove comma separator)
                        $jumlahBayarParsed = (int) str_replace(',', '', (string) $data['jumlah_bayar']);
                        
                        if ($jumlahBayarParsed <= 0) {
                            Notification::make()
                                ->title('Jumlah bayar harus lebih dari 0')
                                ->warning()
                                ->send();
                            return;
                        }

                        if ($jumlahBayarParsed > $record->total_harga_transaksi_setelah_diskon - $record->pencatatanKeuangans->sum('jumlah_bayar')) {
                            Notification::make()
                                ->title('Jumlah bayar tidak boleh lebih dari sisa tagihan')
                                ->warning()
                                ->send();
                            return;
                        }

                        try {
                            DB::beginTransaction();

                            // Create PencatatanKeuangan
                            PencatatanKeuangan::create([
                                'pencatatan_keuangan_type' => Transaksi::class,
                                'pencatatan_keuangan_id' => $record->id,
                                'user_id' => Auth::id(),
                                'jumlah_bayar' => $jumlahBayarParsed,
                                'metode_pembayaran' => $data['metode_pembayaran'],
                                'keterangan' => $data['keterangan'] ?? 'Pembayaran tambahan transaksi ' . $record->kode,
                                'approved_by' => null,
                                'approved_at' => null,
                            ]);

                            // Update jumlah_bayar di Transaksi dari aggregate pencatatan_keuangans
                            $totalPembayaran = PencatatanKeuangan::where('pencatatan_keuangan_type', Transaksi::class)
                                ->where('pencatatan_keuangan_id', $record->id)
                                ->sum('jumlah_bayar');

                            $record->update([
                                'jumlah_bayar' => $totalPembayaran,
                            ]);

                            // Cek apakah sudah lunas
                            if ($totalPembayaran >= $record->total_harga_transaksi_setelah_diskon) {
                                $record->update([
                                    'status_pembayaran' => StatusPembayaranEnum::LUNAS->value,
                                    'tanggal_pembayaran' => now(),
                                ]);

                                Notification::make()
                                    ->title('Transaksi sudah lunas')
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
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ManageTransaksis::route('/'),
            'detail' => Pages\TransaksiDetailPage::route('/{record}/detail'),
        ];
    }
}
