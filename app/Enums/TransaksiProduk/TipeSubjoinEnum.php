<?php

namespace App\Enums\TransaksiProduk;

use Filament\Support\Contracts\HasLabel;

enum TipeSubjoinEnum: string implements HasLabel
{
    case PRA_PRODUKSI = 'Pra Produksi';
    case PRODUKSI = 'Produksi';
    case FINISHING = 'Finishing';

    public function getLabel(): string
    {
        return match($this) {
            self::PRA_PRODUKSI => 'Pra Produksi',
            self::PRODUKSI => 'Produksi',
            self::FINISHING => 'Finishing',
        };
    }
}
