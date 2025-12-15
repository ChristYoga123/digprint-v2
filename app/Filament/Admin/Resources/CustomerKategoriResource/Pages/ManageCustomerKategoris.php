<?php

namespace App\Filament\Admin\Resources\CustomerKategoriResource\Pages;

use App\Filament\Admin\Resources\CustomerKategoriResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageCustomerKategoris extends ManageRecords
{
    protected static string $resource = CustomerKategoriResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
