<?php

namespace App\Http\Controllers;

use App\Models\Antrian;
use App\Enums\Antrian\StatusAntrianEnum;

use Illuminate\Http\Request;

class AntrianController extends Controller
{
    /**
     * Halaman ambil tiket
     */
    public function ambilTiket()
    {
        $today = now()->toDateString();
        
        $antrianMenunggu = Antrian::where('tanggal', $today)
            ->where('status', StatusAntrianEnum::WAITING)
            ->count();
            
        $antrianSelesai = Antrian::where('tanggal', $today)
            ->where('status', StatusAntrianEnum::COMPLETED)
            ->count();
        
        return view('antrian.ambil-tiket', compact('antrianMenunggu', 'antrianSelesai'));
    }

    /**
     * Proses ambil tiket - broadcast ke display
     */
    public function prosesAmbilTiket()
    {
        $antrian = Antrian::ambilTiket();
        
        // Broadcast update ke display
        // Broadcast removed

        
        return redirect()->route('antrian.tiket', $antrian->id);
    }
    
    /**
     * Broadcast update antrian ke display
     */
    // Broadcast method removed


    /**
     * Tampilkan tiket
     */
    public function showTiket(Antrian $antrian)
    {
        return view('antrian.tiket', compact('antrian'));
    }

    /**
     * Halaman display antrian
     */
    public function display()
    {
        $today = now()->toDateString();
        
        // Antrian yang sedang dipanggil
        $calledAntrians = Antrian::where('tanggal', $today)
            ->where('status', StatusAntrianEnum::CALLED)
            ->orderBy('called_at', 'desc')
            ->get()
            ->map(fn($a) => [
                'nomor_antrian' => $a->nomor_antrian,
                'deskprint_number' => $a->deskprint_number,
                'called_at' => $a->called_at?->format('H:i'),
            ])
            ->toArray();
        
        // Antrian yang sedang menunggu
        $waitingAntrians = Antrian::where('tanggal', $today)
            ->where('status', StatusAntrianEnum::WAITING)
            ->orderBy('nomor_antrian', 'asc')
            ->get()
            ->map(fn($a) => [
                'nomor_antrian' => $a->nomor_antrian,
                'created_at' => $a->created_at,
            ])
            ->toArray();
        
        return view('antrian.display', compact('calledAntrians', 'waitingAntrians'));
    }

    /**
     * API: Get data antrian untuk display (polling)
     */
    public function getDisplayData()
    {
        $today = now()->toDateString();
        
        // Antrian yang sedang dipanggil
        $calledAntrians = Antrian::where('tanggal', $today)
            ->where('status', StatusAntrianEnum::CALLED)
            ->orderBy('called_at', 'desc')
            ->get()
            ->map(fn($a) => [
                'nomor_antrian' => $a->nomor_antrian,
                'deskprint_number' => $a->deskprint_number,
                'called_at' => $a->called_at?->format('H:i'),
            ])
            ->toArray();
        
        // Antrian yang sedang menunggu
        $waitingAntrians = Antrian::where('tanggal', $today)
            ->where('status', StatusAntrianEnum::WAITING)
            ->orderBy('nomor_antrian', 'asc')
            ->get()
            ->map(fn($a) => [
                'nomor_antrian' => $a->nomor_antrian,
                'created_at' => $a->created_at?->format('H:i'),
            ])
            ->toArray();
        
        return response()->json([
            'calledAntrians' => $calledAntrians,
            'waitingAntrians' => $waitingAntrians,
            'timestamp' => now()->format('H:i:s'),
        ]);
    }
}
