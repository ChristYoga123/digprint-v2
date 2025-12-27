<?php

namespace App\Filament\Admin\Resources\LaporanPembayaranSupplierResource\Pages;

use App\Filament\Admin\Resources\LaporanPembayaranSupplierResource;
use Filament\Resources\Pages\ManageRecords;

class ManageLaporanPembayaranSuppliers extends ManageRecords
{
    protected static string $resource = LaporanPembayaranSupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
