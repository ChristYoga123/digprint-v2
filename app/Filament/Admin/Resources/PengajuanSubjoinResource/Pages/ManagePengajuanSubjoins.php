<?php

namespace App\Filament\Admin\Resources\PengajuanSubjoinResource\Pages;

use App\Filament\Admin\Resources\PengajuanSubjoinResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManagePengajuanSubjoins extends ManageRecords
{
    protected static string $resource = PengajuanSubjoinResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
