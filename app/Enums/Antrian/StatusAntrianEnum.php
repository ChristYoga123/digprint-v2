<?php

namespace App\Enums\Antrian;

enum StatusAntrianEnum: string
{
    case WAITING = 'waiting';
    case CALLED = 'called';
    case COMPLETED = 'completed';
    case SKIPPED = 'skipped';

    public function label(): string
    {
        return match($this) {
            self::WAITING => 'Menunggu',
            self::CALLED => 'Dipanggil',
            self::COMPLETED => 'Selesai',
            self::SKIPPED => 'Dilewati',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::WAITING => 'gray',
            self::CALLED => 'warning',
            self::COMPLETED => 'success',
            self::SKIPPED => 'danger',
        };
    }
}
