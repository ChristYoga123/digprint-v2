<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Widgets\BahanMinimalWidget;
use App\Filament\Admin\Widgets\DashboardStatsWidget;
use App\Filament\Admin\Widgets\TopProdukWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Dasbor';

    protected static ?string $title = 'Dasbor';

    protected static ?int $navigationSort = -2;

    public function getColumns(): int|string|array
    {
        return [
            'md' => 4,
            'xl' => 6,
        ];
    }

    public function getWidgets(): array
    {
        return [
            DashboardStatsWidget::class,
            TopProdukWidget::class,
            BahanMinimalWidget::class,
        ];
    }
}
