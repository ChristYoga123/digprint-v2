<?php

namespace App\Enums\TransaksiProsesSample;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum StatusSampleApprovalEnum: string implements HasLabel, HasColor
{
    case PENDING = 'Menunggu';
    case APPROVED = 'Disetujui';
    case REJECTED = 'Ditolak';

    public function getLabel(): string
    {
        return match($this) {
            self::PENDING => 'Menunggu',
            self::APPROVED => 'Disetujui',
            self::REJECTED => 'Ditolak',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::PENDING => 'warning',
            self::APPROVED => 'success',
            self::REJECTED => 'danger',
        };
    }
}
