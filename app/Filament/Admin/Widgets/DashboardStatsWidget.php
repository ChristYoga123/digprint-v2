<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Transaksi;
use App\Models\TransaksiProsesBahanUsage;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Number;

class DashboardStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    
    protected int | string | array $columnSpan = 'full';
    
    public static function canView(): bool
    {
        return Auth::user()->can('widget_DashboardStatsWidget');
    }

    protected function getStats(): array
    {
        // Total Omzet - sum of total_harga_transaksi_setelah_diskon
        $totalOmzet = Transaksi::sum('total_harga_transaksi_setelah_diskon') ?? 0;
        
        // Total HPP - sum from transaksi_proses_bahan_usages
        $totalHpp = TransaksiProsesBahanUsage::sum('hpp') ?? 0;
        
        // Gross Profit - Omzet - HPP
        $grossProfit = $totalOmzet - $totalHpp;
        
        // Margin Percentage
        $marginPercent = $totalOmzet > 0 ? ($grossProfit / $totalOmzet) * 100 : 0;

        return [
            Stat::make('Total Omzet', 'Rp ' . Number::abbreviate($totalOmzet, precision: 1))
                ->description('Total pendapatan')
                ->descriptionIcon('heroicon-m-banknotes')
                ->chart($this->getOmzetChart())
                ->color('success')
                ->extraAttributes([
                    'class' => 'cursor-pointer ring-2 ring-emerald-500/20 bg-gradient-to-br from-emerald-500/10 via-transparent to-transparent',
                ]),
            
            Stat::make('Total HPP', 'Rp ' . Number::abbreviate($totalHpp, precision: 1))
                ->description('Harga Pokok Penjualan')
                ->descriptionIcon('heroicon-m-cube')
                ->chart($this->getHppChart())
                ->color('warning')
                ->extraAttributes([
                    'class' => 'cursor-pointer ring-2 ring-amber-500/20 bg-gradient-to-br from-amber-500/10 via-transparent to-transparent',
                ]),
            
            Stat::make('Gross Profit', 'Rp ' . Number::abbreviate($grossProfit, precision: 1))
                ->description($grossProfit >= 0 ? 'Laba Kotor' : 'Rugi Kotor')
                ->descriptionIcon($grossProfit >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->chart($this->getProfitChart())
                ->color($grossProfit >= 0 ? 'info' : 'danger')
                ->extraAttributes([
                    'class' => 'cursor-pointer ring-2 ring-cyan-500/20 bg-gradient-to-br from-cyan-500/10 via-transparent to-transparent',
                ]),
            
            Stat::make('Margin', number_format($marginPercent, 1) . '%')
                ->description('Persentase keuntungan')
                ->descriptionIcon('heroicon-m-chart-pie')
                ->chart($this->getMarginChart())
                ->color($marginPercent >= 20 ? 'success' : ($marginPercent >= 10 ? 'warning' : 'danger'))
                ->extraAttributes([
                    'class' => 'cursor-pointer ring-2 ring-purple-500/20 bg-gradient-to-br from-purple-500/10 via-transparent to-transparent',
                ]),
        ];
    }

    protected function getOmzetChart(): array
    {
        // Generate sample chart data - last 7 periods
        return [7, 3, 8, 5, 9, 4, 10];
    }

    protected function getHppChart(): array
    {
        return [4, 2, 5, 3, 6, 2, 7];
    }

    protected function getProfitChart(): array
    {
        return [3, 1, 3, 2, 3, 2, 3];
    }

    protected function getMarginChart(): array
    {
        return [20, 25, 22, 28, 24, 30, 27];
    }
}
