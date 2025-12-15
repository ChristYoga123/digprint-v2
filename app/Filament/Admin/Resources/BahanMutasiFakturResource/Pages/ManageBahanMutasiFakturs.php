<?php

namespace App\Filament\Admin\Resources\BahanMutasiFakturResource\Pages;

use Filament\Actions;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Resources\Pages\ManageRecords;
use App\Filament\Admin\Resources\BahanMutasiFakturResource;

class ManageBahanMutasiFakturs extends ManageRecords
{
    protected static string $resource = BahanMutasiFakturResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return 'Faktur Bahan Mutasi';
    }
}
