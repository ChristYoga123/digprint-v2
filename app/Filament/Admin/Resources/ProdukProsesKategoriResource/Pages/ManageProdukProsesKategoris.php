<?php

namespace App\Filament\Admin\Resources\ProdukProsesKategoriResource\Pages;

use Filament\Actions;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Resources\Pages\ManageRecords;
use App\Filament\Admin\Resources\ProdukProsesKategoriResource;

class ManageProdukProsesKategoris extends ManageRecords
{
    protected static string $resource = ProdukProsesKategoriResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTitle(): string|Htmlable
    {
        return 'Kategori Proses Produk';
    }
}
