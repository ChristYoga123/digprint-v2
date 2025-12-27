<?php

namespace App\Filament\Admin\Resources\LaporanLemburKaryawanResource\Widgets;

use App\Models\KaryawanPekerjaan;
use App\Enums\KaryawanPekerjaan\TipeEnum;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class StatLemburKaryawanWidget extends BaseWidget
{
    public ?Model $record = null;

    protected function getStats(): array
    {
        $karyawanId = $this->record?->id;

        if (!$karyawanId) {
            return [];
        }

        $totalLembur = KaryawanPekerjaan::where('karyawan_id', $karyawanId)
            ->where('tipe', TipeEnum::LEMBUR)
            ->count();

        $disetujui = KaryawanPekerjaan::where('karyawan_id', $karyawanId)
            ->where('tipe', TipeEnum::LEMBUR)
            ->where('apakah_diapprove_lembur', true)
            ->count();

        $ditolak = KaryawanPekerjaan::where('karyawan_id', $karyawanId)
            ->where('tipe', TipeEnum::LEMBUR)
            ->where('apakah_diapprove_lembur', false)
            ->count();

        $pending = KaryawanPekerjaan::where('karyawan_id', $karyawanId)
            ->where('tipe', TipeEnum::LEMBUR)
            ->whereNull('apakah_diapprove_lembur')
            ->count();

        return [
            Stat::make('Total Lembur', $totalLembur)
                ->description('Semua pengajuan')
                ->descriptionIcon('heroicon-m-clock')
                ->color('primary'),
            Stat::make('Disetujui', $disetujui)
                ->description('Lembur diapprove')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            Stat::make('Ditolak', $ditolak)
                ->description('Lembur ditolak')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),
            Stat::make('Pending', $pending)
                ->description('Menunggu approval')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }
}
