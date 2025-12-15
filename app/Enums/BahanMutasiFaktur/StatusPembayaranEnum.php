<?php

namespace App\Enums\BahanMutasiFaktur;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StatusPembayaranEnum: string implements HasLabel, HasColor
{
    case LUNAS = 'Lunas';
    case TERM_OF_PAYMENT = 'Term of Payment';

    public function getLabel(): string
    {
        return match($this) {
            self::LUNAS => 'Lunas',
            self::TERM_OF_PAYMENT => 'Term of Payment',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::LUNAS => 'success',
            self::TERM_OF_PAYMENT => 'info',
        };
    }
}
