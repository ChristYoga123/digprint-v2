<?php

namespace App\Contracts;

interface WhatsappInterface
{
    public function send(string $noTujuan, string $message);
}
