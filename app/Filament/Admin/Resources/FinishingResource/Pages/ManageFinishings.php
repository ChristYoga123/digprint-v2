<?php

namespace App\Filament\Admin\Resources\FinishingResource\Pages;

use App\Filament\Admin\Resources\FinishingResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageFinishings extends ManageRecords
{
    protected static string $resource = FinishingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
