<?php

namespace App\Filament\Admin\Resources\POResource\Pages;

use App\Models\PO;
use Filament\Actions;
use App\Models\Supplier;
use Filament\Resources\Pages\ManageRecords;
use App\Filament\Admin\Resources\POResource;

class ManagePOS extends ManageRecords
{
    protected static string $resource = POResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->mutateFormDataUsing(function (array $data): array {
                    $data['kode'] = generateKode('PO');

                    return $data;
                })
                ->after(function (PO $record) {
                    $record->total_harga_po_keseluruhan = $record->bahanPO->sum('total_harga_po');
                    $record->save();
                }),
        ];
    }
}
