<?php

namespace App\Filament\Admin\Resources\LaporanLemburKaryawanResource\Pages;

use App\Filament\Admin\Resources\LaporanLemburKaryawanResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageLaporanLemburKaryawans extends ManageRecords
{
    protected static string $resource = LaporanLemburKaryawanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No create action for reports
        ];
    }
}
