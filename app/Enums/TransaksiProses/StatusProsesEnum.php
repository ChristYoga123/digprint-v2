<?php

namespace App\Enums\TransaksiProses;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StatusProsesEnum: string implements HasLabel, HasColor
{
    case BELUM = 'Belum';
    case DALAM_PROSES = 'Dalam Proses';
    case SELESAI = 'Selesai';

    public function getLabel(): string
    {
        return match($this) {
            self::BELUM => 'Belum Dikerjakan',
            self::DALAM_PROSES => 'Dalam Proses',
            self::SELESAI => 'Selesai',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::BELUM => 'warning',
            self::DALAM_PROSES => 'info',
            self::SELESAI => 'success',
        };
    }
}
