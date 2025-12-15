<?php

namespace App\Filament\Admin\Resources\KaryawanResource\Pages;

use App\Filament\Admin\Resources\KaryawanResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageKaryawans extends ManageRecords
{
    protected static string $resource = KaryawanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    $data['password'] = bcrypt('password');
                    return $data;
                })
                ->closeModalByClickingAway(false),
        ];
    }

    public function getTitle(): string
    {
        return 'Karyawan';
    }
}
