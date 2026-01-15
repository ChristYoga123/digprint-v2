<?php

namespace App\Models;

use App\Enums\Antrian\StatusAntrianEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Antrian extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'status' => StatusAntrianEnum::class,
        'tanggal' => 'date',
        'called_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Relasi ke user yang memanggil
     */
    public function calledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'called_by');
    }

    /**
     * Ambil nomor antrian berikutnya untuk hari ini
     */
    public static function getNextNomorAntrian(): int
    {
        $today = now()->toDateString();
        
        $lastNumber = self::where('tanggal', $today)
            ->max('nomor_antrian');
        
        return ($lastNumber ?? 0) + 1;
    }

    /**
     * Buat tiket antrian baru
     */
    public static function ambilTiket(): self
    {
        return DB::transaction(function () {
            $today = now()->toDateString();
            
            // Lock untuk mencegah race condition
            $lastNumber = self::where('tanggal', $today)
                ->lockForUpdate()
                ->max('nomor_antrian');
            
            $nomorBaru = ($lastNumber ?? 0) + 1;
            
            return self::create([
                'nomor_antrian' => $nomorBaru,
                'tanggal' => $today,
                'status' => StatusAntrianEnum::WAITING,
            ]);
        });
    }

    /**
     * Panggil antrian berikutnya untuk deskprint tertentu
     * Menggunakan pessimistic locking untuk handle concurrent requests
     */
    public static function panggilBerikutnya(int $deskprintNumber, int $userId): ?self
    {
        return DB::transaction(function () use ($deskprintNumber, $userId) {
            $today = now()->toDateString();
            
            // Ambil antrian waiting dengan nomor terkecil dan lock
            $antrian = self::where('tanggal', $today)
                ->where('status', StatusAntrianEnum::WAITING)
                ->orderBy('nomor_antrian', 'asc')
                ->lockForUpdate()
                ->first();
            
            if (!$antrian) {
                return null;
            }
            
            // Update status menjadi called
            $antrian->update([
                'status' => StatusAntrianEnum::CALLED,
                'deskprint_number' => $deskprintNumber,
                'called_by' => $userId,
                'called_at' => now(),
            ]);
            
            return $antrian->fresh();
        });
    }

    /**
     * Selesaikan antrian
     */
    public function selesai(): self
    {
        $this->update([
            'status' => StatusAntrianEnum::COMPLETED,
            'completed_at' => now(),
        ]);
        
        return $this->fresh();
    }

    /**
     * Lewati antrian (skip)
     */
    public function lewati(): self
    {
        $this->update([
            'status' => StatusAntrianEnum::SKIPPED,
        ]);
        
        return $this->fresh();
    }

    /**
     * Get antrian yang sedang dipanggil untuk deskprint tertentu
     */
    public static function getCurrentForDeskprint(int $deskprintNumber): ?self
    {
        $today = now()->toDateString();
        
        return self::where('tanggal', $today)
            ->where('deskprint_number', $deskprintNumber)
            ->where('status', StatusAntrianEnum::CALLED)
            ->first();
    }

    /**
     * Get semua antrian yang sedang dipanggil hari ini
     */
    public static function getAllCurrentCalled(): \Illuminate\Database\Eloquent\Collection
    {
        $today = now()->toDateString();
        
        return self::where('tanggal', $today)
            ->where('status', StatusAntrianEnum::CALLED)
            ->orderBy('deskprint_number')
            ->get();
    }

    /**
     * Get statistik antrian hari ini
     */
    public static function getStatistikHariIni(): array
    {
        $today = now()->toDateString();
        
        $stats = self::where('tanggal', $today)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();
        
        return [
            'waiting' => $stats[StatusAntrianEnum::WAITING->value] ?? 0,
            'called' => $stats[StatusAntrianEnum::CALLED->value] ?? 0,
            'completed' => $stats[StatusAntrianEnum::COMPLETED->value] ?? 0,
            'skipped' => $stats[StatusAntrianEnum::SKIPPED->value] ?? 0,
            'total' => array_sum($stats),
        ];
    }

    /**
     * Reset antrian (untuk hari baru atau manual reset)
     */
    public static function resetAntrian(?string $tanggal = null): int
    {
        $tanggal = $tanggal ?? now()->toDateString();
        
        return self::where('tanggal', $tanggal)->delete();
    }
}
