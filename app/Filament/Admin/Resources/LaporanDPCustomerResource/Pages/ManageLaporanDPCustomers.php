<?php

namespace App\Filament\Admin\Resources\LaporanDPCustomerResource\Pages;

use App\Filament\Admin\Resources\LaporanDPCustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageLaporanDPCustomers extends ManageRecords
{
    protected static string $resource = LaporanDPCustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
