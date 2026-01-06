<?php

namespace App\Filament\Admin\Resources\LaporanHPPResource\Pages;

use App\Filament\Admin\Resources\LaporanHPPResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageLaporanHPPS extends ManageRecords
{
    protected static string $resource = LaporanHPPResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
