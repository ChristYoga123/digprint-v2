<?php

namespace App\Filament\Admin\Resources\LaporanKerjaKaryawanResource\Widgets;

use App\Models\KaryawanPekerjaan;
use App\Enums\KaryawanPekerjaan\TipeEnum;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Model;

class StatKerjaKaryawanWidget extends BaseWidget
{
    public ?Model $record = null;

    protected function getStats(): array
    {
        $karyawanId = $this->record?->id;

        if (!$karyawanId) {
            return [];
        }

        $totalPekerjaan = KaryawanPekerjaan::where('karyawan_id', $karyawanId)
            ->where('tipe', TipeEnum::NORMAL)
            ->count();

        $bulanIni = KaryawanPekerjaan::where('karyawan_id', $karyawanId)
            ->where('tipe', TipeEnum::NORMAL)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        $mingguIni = KaryawanPekerjaan::where('karyawan_id', $karyawanId)
            ->where('tipe', TipeEnum::NORMAL)
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        $hariIni = KaryawanPekerjaan::where('karyawan_id', $karyawanId)
            ->where('tipe', TipeEnum::NORMAL)
            ->whereDate('created_at', now()->toDateString())
            ->count();

        return [
            Stat::make('Total Pekerjaan', $totalPekerjaan)
                ->description('Semua waktu')
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('primary'),
            Stat::make('Bulan Ini', $bulanIni)
                ->description(now()->translatedFormat('F Y'))
                ->descriptionIcon('heroicon-m-calendar')
                ->color('success'),
            Stat::make('Minggu Ini', $mingguIni)
                ->description('Minggu ' . now()->weekOfYear)
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('warning'),
            Stat::make('Hari Ini', $hariIni)
                ->description(now()->translatedFormat('l, d M'))
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),
        ];
    }
}
