<?php

namespace App\Filament\Admin\Resources\TransaksiProsesResource\Pages;

use App\Filament\Admin\Resources\TransaksiProsesResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageTransaksiProses extends ManageRecords
{
    protected static string $resource = TransaksiProsesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
