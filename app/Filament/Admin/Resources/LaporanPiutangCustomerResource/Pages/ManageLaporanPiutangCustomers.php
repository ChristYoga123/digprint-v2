<?php

namespace App\Filament\Admin\Resources\LaporanPiutangCustomerResource\Pages;

use App\Filament\Admin\Resources\LaporanPiutangCustomerResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageLaporanPiutangCustomers extends ManageRecords
{
    protected static string $resource = LaporanPiutangCustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
