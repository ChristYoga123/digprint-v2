<?php

namespace App\Filament\Admin\Resources\LaporanKerjaKaryawanResource\Pages;

use App\Filament\Admin\Resources\LaporanKerjaKaryawanResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageLaporanKerjaKaryawans extends ManageRecords
{
    protected static string $resource = LaporanKerjaKaryawanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action for reports
        ];
    }
}
