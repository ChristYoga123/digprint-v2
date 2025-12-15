<?php

namespace App\Filament\Admin\Resources\BahanResource\Pages;

use App\Filament\Admin\Resources\BahanResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageBahans extends ManageRecords
{
    protected static string $resource = BahanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
