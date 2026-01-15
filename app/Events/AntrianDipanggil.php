<?php

namespace App\Events;

use App\Models\Antrian;
use Illuminate\Broadcasting\Channel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AntrianDipanggil
{
    use Dispatchable, SerializesModels;

    public int $nomorAntrian;
    public int $loket;
    public string $calledAt;

    /**
     * Create a new event instance.
     */
    public function __construct(Antrian $antrian)
    {
        $this->nomorAntrian = $antrian->nomor_antrian;
        $this->loket = $antrian->deskprint_number;
        $this->calledAt = $antrian->called_at?->format('H:i:s') ?? now()->format('H:i:s');
    }
}
