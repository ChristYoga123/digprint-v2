<?php

namespace App\Enums\PettyCashFlow;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TipeEnum: string implements HasLabel, HasColor
{
    case PERMINTAAN = 'Permintaan';
    case PENGELUARAN = 'Pengeluaran';

    public function getLabel(): string
    {
        return match($this) {
            self::PERMINTAAN => 'Permintaan',
            self::PENGELUARAN => 'Pengeluaran',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::PERMINTAAN => 'success',
            self::PENGELUARAN => 'danger',
        };
    }
}
