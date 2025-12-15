<?php

namespace App\Filament\Admin\Resources\MesinResource\Pages;

use App\Filament\Admin\Resources\MesinResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageMesins extends ManageRecords
{
    protected static string $resource = MesinResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
