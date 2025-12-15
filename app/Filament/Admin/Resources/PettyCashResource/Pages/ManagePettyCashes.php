<?php

namespace App\Filament\Admin\Resources\PettyCashResource\Pages;

use App\Filament\Admin\Resources\PettyCashResource;
use App\Enums\PettyCash\StatusEnum;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Auth;

class ManagePettyCashes extends ManageRecords
{
    protected static string $resource = PettyCashResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    // Set user_id_buka dan status saat create
                    $data['user_id_buka'] = Auth::id();
                    $data['status'] = StatusEnum::BUKA->value;
                    
                    // Parse uang_buka jika ada (remove comma separator dari mask)
                    if (isset($data['uang_buka'])) {
                        $data['uang_buka'] = (int) str_replace(',', '', (string) $data['uang_buka']);
                    }
                    
                    return $data;
                }),
        ];
    }
}
