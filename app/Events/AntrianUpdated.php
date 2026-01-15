<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AntrianUpdated
{
    use Dispatchable, SerializesModels;

    public array $statistik;
    public array $calledAntrians;
    public array $waitingAntrians;

    /**
     * Create a new event instance.
     */
    public function __construct(array $statistik, array $calledAntrians, array $waitingAntrians)
    {
        $this->statistik = $statistik;
        $this->calledAntrians = $calledAntrians;
        $this->waitingAntrians = $waitingAntrians;
    }
}
