<?php

namespace App\Enums\BahanMutasi;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TipeEnum: string implements HasLabel, HasColor
{
    case MASUK = 'Masuk';
    case KELUAR = 'Keluar';

    public function getLabel(): string
    {
        return match($this) {
            self::MASUK => 'Masuk',
            self::KELUAR => 'Keluar',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::MASUK => 'success',
            self::KELUAR => 'danger',
        };
    }
}
