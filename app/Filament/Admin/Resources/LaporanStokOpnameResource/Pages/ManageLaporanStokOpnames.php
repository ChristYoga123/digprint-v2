<?php

namespace App\Filament\Admin\Resources\LaporanStokOpnameResource\Pages;

use Filament\Resources\Pages\ManageRecords;
use App\Filament\Admin\Resources\LaporanStokOpnameResource;

class ManageLaporanStokOpnames extends ManageRecords
{
    protected static string $resource = LaporanStokOpnameResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
