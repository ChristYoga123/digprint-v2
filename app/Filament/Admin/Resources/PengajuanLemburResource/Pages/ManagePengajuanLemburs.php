<?php

namespace App\Filament\Admin\Resources\PengajuanLemburResource\Pages;

use App\Filament\Admin\Resources\PengajuanLemburResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePengajuanLemburs extends ManageRecords
{
    protected static string $resource = PengajuanLemburResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions are in table header
        ];
    }
}
