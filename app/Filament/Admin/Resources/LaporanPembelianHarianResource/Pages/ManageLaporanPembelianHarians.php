<?php

namespace App\Filament\Admin\Resources\LaporanPembelianHarianResource\Pages;

use App\Filament\Admin\Resources\LaporanPembelianHarianResource;
use Filament\Resources\Pages\ManageRecords;

class ManageLaporanPembelianHarians extends ManageRecords
{
    protected static string $resource = LaporanPembelianHarianResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
