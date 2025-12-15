<?php

namespace App\Enums\Kloter;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StatusEnum: string implements HasLabel, HasColor
{
    case AKTIF = 'Aktif';
    case SELESAI = 'Selesai';

    public function getLabel(): string
    {
        return match($this) {
            self::AKTIF => 'Aktif',
            self::SELESAI => 'Selesai',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::AKTIF => 'warning',
            self::SELESAI => 'success',
        };
    }
}

