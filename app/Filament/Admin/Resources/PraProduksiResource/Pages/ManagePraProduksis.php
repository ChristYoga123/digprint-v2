<?php

namespace App\Filament\Admin\Resources\PraProduksiResource\Pages;

use App\Filament\Admin\Resources\PraProduksiResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePraProduksis extends ManageRecords
{
    protected static string $resource = PraProduksiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
