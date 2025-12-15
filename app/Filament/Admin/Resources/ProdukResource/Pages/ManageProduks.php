<?php

namespace App\Filament\Admin\Resources\ProdukResource\Pages;

use App\Filament\Admin\Resources\ProdukResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageProduks extends ManageRecords
{
    protected static string $resource = ProdukResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->closeModalByClickingAway(false),
        ];
    }
}
