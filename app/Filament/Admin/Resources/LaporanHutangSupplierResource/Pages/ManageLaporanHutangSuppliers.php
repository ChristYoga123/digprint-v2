<?php

namespace App\Filament\Admin\Resources\LaporanHutangSupplierResource\Pages;

use App\Filament\Admin\Resources\LaporanHutangSupplierResource;
use Filament\Resources\Pages\ManageRecords;

class ManageLaporanHutangSuppliers extends ManageRecords
{
    protected static string $resource = LaporanHutangSupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
