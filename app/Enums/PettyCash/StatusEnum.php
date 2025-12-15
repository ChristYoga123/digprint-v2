<?php

namespace App\Enums\PettyCash;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StatusEnum: string implements HasLabel, HasColor
{
    case BUKA = 'Buka';
    case TUTUP = 'Tutup';

    public function getLabel(): string
    {
        return match($this) {
            self::BUKA => 'Buka',
            self::TUTUP => 'Tutup',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::BUKA => 'success',
            self::TUTUP => 'danger',
        };
    }
}
