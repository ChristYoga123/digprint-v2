<?php

namespace App\Enums\TransaksiProduk;

use Filament\Support\Contracts\HasLabel;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;

enum StatusTransaksiProdukEnum: string implements HasLabel, HasColor, HasIcon
{
    case BELUM = 'Belum';
    case DALAM_PROSES = 'Dalam Proses';
    case SIAP_DIAMBIL = 'Siap Diambil';
    case SELESAI = 'Selesai';

    public function getLabel(): string
    {
        return match ($this) {
            self::BELUM => 'Belum Dikerjakan',
            self::DALAM_PROSES => 'Dalam Proses',
            self::SIAP_DIAMBIL => 'Siap Diambil',
            self::SELESAI => 'Selesai',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::BELUM => 'warning',
            self::DALAM_PROSES => 'info',
            self::SIAP_DIAMBIL => 'success',
            self::SELESAI => 'success',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::BELUM => 'heroicon-o-clock',
            self::DALAM_PROSES => 'heroicon-o-arrow-path',
            self::SIAP_DIAMBIL => 'heroicon-o-check-circle',
            self::SELESAI => 'heroicon-o-archive-box',
        };
    }
}
