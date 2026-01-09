<?php

namespace App\Enums\StokOpname;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum StatusEnum: string implements HasLabel, HasColor, HasIcon
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case APPROVED = 'approved';
    case REVISED = 'revised';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SUBMITTED => 'Diajukan',
            self::APPROVED => 'Disetujui',
            self::REVISED => 'Direvisi',
        };
    }

    public function getColor(): string | array | null
    {
        return match ($this) {
            self::DRAFT => 'gray',
            self::SUBMITTED => 'warning',
            self::APPROVED => 'success',
            self::REVISED => 'info',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::DRAFT => 'heroicon-o-document',
            self::SUBMITTED => 'heroicon-o-clock',
            self::APPROVED => 'heroicon-o-check-circle',
            self::REVISED => 'heroicon-o-pencil-square',
        };
    }
}
