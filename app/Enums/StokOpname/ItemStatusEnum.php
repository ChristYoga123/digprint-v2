<?php

namespace App\Enums\StokOpname;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ItemStatusEnum: string implements HasLabel, HasColor, HasIcon
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REVISED = 'revised';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PENDING => 'Menunggu',
            self::APPROVED => 'Disetujui',
            self::REVISED => 'Direvisi',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::APPROVED => 'success',
            self::REVISED => 'info',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::PENDING => 'heroicon-o-clock',
            self::APPROVED => 'heroicon-o-check-circle',
            self::REVISED => 'heroicon-o-pencil-square',
        };
    }
}
