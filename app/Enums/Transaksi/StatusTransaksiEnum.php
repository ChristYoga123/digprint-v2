<?php

namespace App\Enums\Transaksi;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StatusTransaksiEnum: string implements HasLabel, HasColor
{
    case BELUM = 'Belum';
    case DALAM_PROSES = 'Dalam Proses';
    case SIAP_DIAMBIL = 'Siap Diambil';
    case SELESAI = 'Selesai';

    public function getLabel(): string
    {
        return match($this) {
            self::BELUM => 'Belum Dikerjakan',
            self::DALAM_PROSES => 'Dalam Proses',
            self::SIAP_DIAMBIL => 'Siap Diambil',
            self::SELESAI => 'Selesai',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::BELUM => 'warning',
            self::DALAM_PROSES => 'info',
            self::SIAP_DIAMBIL => 'success',
            self::SELESAI => 'gray',
        };
    }
}

