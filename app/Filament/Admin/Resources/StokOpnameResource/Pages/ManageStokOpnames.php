<?php

namespace App\Filament\Admin\Resources\StokOpnameResource\Pages;

use Filament\Actions;
use App\Models\Bahan;
use App\Models\StokOpname;
use Illuminate\Support\Facades\Auth;
use Filament\Resources\Pages\ManageRecords;
use App\Filament\Admin\Resources\StokOpnameResource;

class ManageStokOpnames extends ManageRecords
{
    protected static string $resource = StokOpnameResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Buat Stok Opname Baru')
                ->mutateFormDataUsing(function (array $data): array {
                    $data['created_by'] = Auth::id();
                    $data['status'] = 'draft';
                    return $data;
                })
                ->after(function (StokOpname $record) {
                    // Automatically create items for all bahan
                    $bahans = Bahan::all();
                    
                    foreach ($bahans as $bahan) {
                        $record->items()->create([
                            'bahan_id' => $bahan->id,
                            'stock_system' => $bahan->stok, // Get current stock from FIFO batches
                            'stock_physical' => null,
                            'difference' => null,
                            'status' => 'pending',
                        ]);
                    }
                })
                ->successRedirectUrl(fn (StokOpname $record) => ManageStokOpnameItems::getUrl(['record' => $record->id])),
        ];
    }
}
