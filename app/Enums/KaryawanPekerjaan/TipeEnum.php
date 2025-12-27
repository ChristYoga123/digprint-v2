<?php

namespace App\Enums\KaryawanPekerjaan;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TipeEnum: string implements HasLabel, HasColor
{
    case NORMAL = 'Normal';
    case LEMBUR = 'Lembur';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::NORMAL => 'Normal',
            self::LEMBUR => 'Lembur',
        };
    }

    public function getColor(): ?string
    {
        return match ($this) {
            self::NORMAL => 'success',
            self::LEMBUR => 'warning',
        };
    }
}
