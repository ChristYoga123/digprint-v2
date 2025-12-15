<?php

namespace App\Enums\Transaksi;
use Filament\Support\Contracts\HasLabel;

enum JenisDiskonEnum: string implements HasLabel
{
    //
    case PER_ITEM = 'Per Item';
    case PER_INVOICE = 'Per Invoice';

    public function getLabel(): string
    {
        return match($this) {
            self::PER_ITEM => 'Per Item',
            self::PER_INVOICE => 'Per Invoice',
        };
    }
}
