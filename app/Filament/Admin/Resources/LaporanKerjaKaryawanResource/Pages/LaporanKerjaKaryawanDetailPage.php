<?php

namespace App\Filament\Admin\Resources\LaporanKerjaKaryawanResource\Pages;

use Carbon\Carbon;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Pages\Page;
use App\Models\KaryawanPekerjaan;
use App\Models\TransaksiKalkulasi;
use App\Models\Transaksi;
use App\Models\TransaksiProses;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Contracts\Support\Htmlable;
use App\Enums\KaryawanPekerjaan\TipeEnum;
use App\Filament\Admin\Resources\LaporanKerjaKaryawanResource;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class LaporanKerjaKaryawanDetailPage extends Page implements HasTable
{
    use InteractsWithTable;
    
    protected static string $resource = LaporanKerjaKaryawanResource::class;

    protected static string $view = 'filament.admin.resources.laporan-kerja-karyawan-resource.pages.laporan-kerja-karyawan-detail-page';
    
    public ?int $record = null;
    public ?User $karyawan = null;

    public function mount(int $record): void
    {
        $this->record = $record;
        $this->karyawan = User::findOrFail($record);
    }

    public function getTitle(): string|Htmlable
    {
        return 'Detail Pekerjaan: ' . ($this->karyawan?->name ?? 'Karyawan');
    }

    public function getBreadcrumbs(): array
    {
        return [
            LaporanKerjaKaryawanResource::getUrl() => 'Laporan Kerja Karyawan',
            '#' => $this->karyawan?->name ?? 'Detail',
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                KaryawanPekerjaan::query()
                    ->where('karyawan_id', $this->record)
                    ->where('tipe', TipeEnum::NORMAL)
                    ->orderBy('created_at', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('jenis_pekerjaan')
                    ->label('Jenis Pekerjaan')
                    ->getStateUsing(function (KaryawanPekerjaan $record) {
                        $type = $record->karyawan_pekerjaan_type;
                        return match ($type) {
                            TransaksiKalkulasi::class => 'Kalkulasi (Deskprint)',
                            Transaksi::class => 'Kasir (Transaksi)',
                            TransaksiProses::class => 'Produksi/Finishing',
                            default => class_basename($type),
                        };
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Kalkulasi (Deskprint)' => 'info',
                        'Kasir (Transaksi)' => 'success',
                        'Produksi/Finishing' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('detail_pekerjaan')
                    ->label('Detail')
                    ->getStateUsing(function (KaryawanPekerjaan $record) {
                        $type = $record->karyawan_pekerjaan_type;
                        $id = $record->karyawan_pekerjaan_id;
                        
                        if ($type === TransaksiKalkulasi::class) {
                            $kalkulasi = TransaksiKalkulasi::find($id);
                            return $kalkulasi ? $kalkulasi->kode : '-';
                        } elseif ($type === Transaksi::class) {
                            $transaksi = Transaksi::find($id);
                            return $transaksi ? $transaksi->kode : '-';
                        } elseif ($type === TransaksiProses::class) {
                            $proses = TransaksiProses::with(['produkProses', 'transaksiProduk.produk'])->find($id);
                            if ($proses) {
                                $produkNama = $proses->transaksiProduk?->produk?->nama ?? '-';
                                $prosesNama = $proses->produkProses?->nama ?? '-';
                                return "{$produkNama} - {$prosesNama}";
                            }
                            return '-';
                        }
                        return '-';
                    })
                    ->wrap(),
                Tables\Columns\TextColumn::make('tipe')
                    ->label('Tipe')
                    ->badge(),
            ])
            ->filters([
                DateRangeFilter::make('created_at')
                    ->label('Tanggal'),
            ])
            ->actions([])
            ->bulkActions([]);
    }
}
