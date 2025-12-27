<?php

namespace App\Filament\Admin\Resources\LaporanLemburKaryawanResource\Pages;

use Carbon\Carbon;
use App\Models\User;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Pages\Page;
use App\Models\KaryawanPekerjaan;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Concerns\InteractsWithTable;
use Illuminate\Contracts\Support\Htmlable;
use App\Enums\KaryawanPekerjaan\TipeEnum;
use App\Filament\Admin\Resources\LaporanLemburKaryawanResource;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;

class LaporanLemburKaryawanDetailPage extends Page implements HasTable
{
    use InteractsWithTable;
    
    protected static string $resource = LaporanLemburKaryawanResource::class;

    protected static string $view = 'filament.admin.resources.laporan-lembur-karyawan-resource.pages.laporan-lembur-karyawan-detail-page';
    
    public ?int $record = null;
    public ?User $karyawan = null;

    public function mount(int $record): void
    {
        $this->record = $record;
        $this->karyawan = User::findOrFail($record);
    }

    public function getTitle(): string|Htmlable
    {
        return 'Detail Lembur: ' . ($this->karyawan?->name ?? 'Karyawan');
    }

    public function getBreadcrumbs(): array
    {
        return [
            LaporanLemburKaryawanResource::getUrl() => 'Laporan Lembur Karyawan',
            '#' => $this->karyawan?->name ?? 'Detail',
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                KaryawanPekerjaan::query()
                    ->where('karyawan_id', $this->record)
                    ->where('tipe', TipeEnum::LEMBUR)
                    ->orderBy('jam_lembur_mulai', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('jam_lembur_mulai')
                    ->label('Jadwal Mulai')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('jam_lembur_selesai')
                    ->label('Jadwal Selesai')
                    ->dateTime('H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('durasi_jadwal')
                    ->label('Durasi Jadwal')
                    ->getStateUsing(function (KaryawanPekerjaan $record) {
                        if ($record->jam_lembur_mulai && $record->jam_lembur_selesai) {
                            $diff = Carbon::parse($record->jam_lembur_mulai)
                                ->diff(Carbon::parse($record->jam_lembur_selesai));
                            return $diff->h . ' jam ' . $diff->i . ' menit';
                        }
                        return '-';
                    })
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('jam_aktual_mulai')
                    ->label('Aktual Masuk')
                    ->dateTime('H:i')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('jam_aktual_selesai')
                    ->label('Aktual Keluar')
                    ->dateTime('H:i')
                    ->placeholder('-'),
                Tables\Columns\TextColumn::make('durasi_aktual')
                    ->label('Durasi Aktual')
                    ->getStateUsing(function (KaryawanPekerjaan $record) {
                        if ($record->jam_aktual_mulai && $record->jam_aktual_selesai) {
                            $diff = Carbon::parse($record->jam_aktual_mulai)
                                ->diff(Carbon::parse($record->jam_aktual_selesai));
                            return $diff->h . ' jam ' . $diff->i . ' menit';
                        }
                        return '-';
                    })
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->getStateUsing(function (KaryawanPekerjaan $record) {
                        if ($record->apakah_diapprove_lembur === null) {
                            return 'Pending';
                        } elseif ($record->apakah_diapprove_lembur === true) {
                            return 'Disetujui';
                        } else {
                            return 'Ditolak';
                        }
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Pending' => 'warning',
                        'Disetujui' => 'success',
                        'Ditolak' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('approvedBy.name')
                    ->label('Diproses Oleh')
                    ->placeholder('-'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Disetujui',
                        'rejected' => 'Ditolak',
                    ])
                    ->query(function ($query, array $data) {
                        if ($data['value'] === 'pending') {
                            $query->whereNull('apakah_diapprove_lembur');
                        } elseif ($data['value'] === 'approved') {
                            $query->where('apakah_diapprove_lembur', true);
                        } elseif ($data['value'] === 'rejected') {
                            $query->where('apakah_diapprove_lembur', false);
                        }
                    }),
                DateRangeFilter::make('jam_lembur_mulai')
                    ->label('Tanggal'),
            ])
            ->actions([])
            ->bulkActions([]);
    }
}
