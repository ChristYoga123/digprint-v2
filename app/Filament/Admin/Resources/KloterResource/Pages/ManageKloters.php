<?php

namespace App\Filament\Admin\Resources\KloterResource\Pages;

use id;
use Filament\Actions;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Pages\ManageRecords;
use App\Filament\Admin\Resources\KloterResource;

class ManageKloters extends ManageRecords
{
    protected static string $resource = KloterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    $data['created_by'] = Auth::id();
                    return $data;
                }),
        ];
    }
}
