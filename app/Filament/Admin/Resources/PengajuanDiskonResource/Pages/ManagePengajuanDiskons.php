<?php

namespace App\Filament\Admin\Resources\PengajuanDiskonResource\Pages;

use App\Filament\Admin\Resources\PengajuanDiskonResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePengajuanDiskons extends ManageRecords
{
    protected static string $resource = PengajuanDiskonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
