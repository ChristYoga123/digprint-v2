<?php

namespace App\Filament\Admin\Resources\SatuanResource\Pages;

use App\Filament\Admin\Resources\SatuanResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageSatuans extends ManageRecords
{
    protected static string $resource = SatuanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
