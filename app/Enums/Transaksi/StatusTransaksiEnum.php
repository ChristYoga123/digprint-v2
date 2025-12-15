<?php

namespace App\Enums\Transaksi;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StatusTransaksiEnum: string implements HasLabel, HasColor
{
    case BELUM = 'Belum';
    case PRA_PRODUKSI = 'Pra Produksi';
    case PRODUKSI = 'Produksi';
    case FINISHING = 'Finishing';
    case SELESAI = 'Selesai';

    public function getLabel(): string
    {
        return match($this) {
            self::BELUM => 'Belum Dikerjakan',
            self::PRA_PRODUKSI => 'Pra Produksi',
            self::PRODUKSI => 'Produksi',
            self::FINISHING => 'Finishing',
            self::SELESAI => 'Selesai',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::BELUM => 'warning',
            self::PRA_PRODUKSI => 'info',
            self::PRODUKSI => 'primary',
            self::FINISHING => 'warning',
            self::SELESAI => 'success',
        };
    }
}
