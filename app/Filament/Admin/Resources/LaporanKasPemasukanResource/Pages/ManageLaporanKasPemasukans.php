<?php

namespace App\Filament\Admin\Resources\LaporanKasPemasukanResource\Pages;

use App\Filament\Admin\Resources\LaporanKasPemasukanResource;
use Filament\Resources\Pages\ManageRecords;

class ManageLaporanKasPemasukans extends ManageRecords
{
    protected static string $resource = LaporanKasPemasukanResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
