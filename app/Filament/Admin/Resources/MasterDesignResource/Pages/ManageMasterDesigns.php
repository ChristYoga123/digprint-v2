<?php

namespace App\Filament\Admin\Resources\MasterDesignResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use App\Filament\Admin\Resources\MasterDesignResource;

class ManageMasterDesigns extends ManageRecords
{
    protected static string $resource = MasterDesignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Design'),
        ];
    }
}
