<?php

namespace App\Enums\Utils;

enum TipeNotificationEnum: string
{
    case SUCCESS = 'Success';
    case ERROR = 'Error';

    public function getTitle(): string
    {
        return match($this){
            self::SUCCESS => 'Berhasil',
            self::ERROR => 'Gagal',
        };
    }

    public function getMethod(): string
    {
        return match($this) {
            self::SUCCESS => 'success',
            self::ERROR => 'danger',
        };
    }
}
