<?php

namespace App\Filament\Admin\Pages;

use App\Models\Antrian;
use App\Enums\Antrian\StatusAntrianEnum;
use App\Events\AntrianDipanggil;
use App\Events\AntrianUpdated;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Contracts\Support\Htmlable;


class AntrianPage extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-queue-list';
    protected static ?string $navigationLabel = 'Antrian';
    protected static ?string $navigationGroup = 'Transaksi';
    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.admin.pages.antrian-page';

    public ?int $selectedDeskprint = null;
    public ?array $currentAntrian = null;
    public array $statistik = [];
    public array $allCalledAntrians = [];

    public function getTitle(): string|Htmlable
    {
        return 'Antrian';
    }

    public static function shouldRegisterNavigation(): bool
    {
        return Auth::user()?->can('page_AntrianPage') ?? false;
    }

    public static function canAccess(): bool
    {
        return Auth::user()?->can('page_AntrianPage') ?? false;
    }

    public function mount(): void
    {
        $this->refreshData();
    }

    public function refreshData(): void
    {
        $this->statistik = Antrian::getStatistikHariIni();
        $this->allCalledAntrians = Antrian::getAllCurrentCalled()->toArray();
        
        if ($this->selectedDeskprint) {
            $current = Antrian::getCurrentForDeskprint($this->selectedDeskprint);
            $this->currentAntrian = $current ? $current->toArray() : null;
        }
    }

    public function selectDeskprint(int $number): void
    {
        $this->selectedDeskprint = $number;
        
        $current = Antrian::getCurrentForDeskprint($number);
        $this->currentAntrian = $current ? $current->toArray() : null;
        
        $this->refreshData();
    }

    /**
     * Broadcast event untuk update display & voice
     */
    // Broadcast method removed


    public function panggilBerikutnya(): void
    {
        if (!$this->selectedDeskprint) {
            Notification::make()
                ->title('Pilih loket terlebih dahulu')
                ->warning()
                ->send();
            return;
        }

        // Cek apakah ada antrian yang sedang dipanggil
        if ($this->currentAntrian && $this->currentAntrian['status'] === StatusAntrianEnum::CALLED->value) {
            Notification::make()
                ->title('Selesaikan antrian saat ini terlebih dahulu')
                ->warning()
                ->send();
            return;
        }

        $antrian = Antrian::panggilBerikutnya($this->selectedDeskprint, Auth::id());

        if (!$antrian) {
            Notification::make()
                ->title('Tidak ada antrian menunggu')
                ->info()
                ->send();
            return;
        }

        $this->currentAntrian = $antrian->toArray();
        $this->refreshData();

        // Broadcast ke display via websocket
        // Broadcast removed


        Notification::make()
            ->title('Antrian berhasil dipanggil')
            ->body("Nomor {$antrian->nomor_antrian} ke Loket {$this->selectedDeskprint}")
            ->success()
            ->send();
    }

    public function selesaikanAntrian(): void
    {
        if (!$this->currentAntrian) {
            return;
        }

        $antrian = Antrian::find($this->currentAntrian['id']);
        if ($antrian) {
            $antrian->selesai();
        }

        $this->currentAntrian = null;
        $this->refreshData();

        // Broadcast update
        // Broadcast removed


        Notification::make()
            ->title('Antrian selesai dilayani')
            ->success()
            ->send();
    }

    public function lewatiAntrian(): void
    {
        if (!$this->currentAntrian) {
            return;
        }

        $antrian = Antrian::find($this->currentAntrian['id']);
        if ($antrian) {
            $antrian->lewati();
        }

        $this->currentAntrian = null;
        $this->refreshData();

        // Broadcast update
        // Broadcast removed


        Notification::make()
            ->title('Antrian dilewati')
            ->warning()
            ->send();
    }

    public function panggilUlang(): void
    {
        if (!$this->currentAntrian) {
            return;
        }

        // Get fresh antrian for broadcast
        $antrian = Antrian::find($this->currentAntrian['id']);
        if ($antrian) {
           // Broadcast removed
        }

        Notification::make()
            ->title('Memanggil ulang')
            ->body("Nomor {$this->currentAntrian['nomor_antrian']}")
            ->info()
            ->send();
    }

    public function resetAntrian(): void
    {
        $deleted = Antrian::resetAntrian();
        
        $this->currentAntrian = null;
        $this->refreshData();

        // Broadcast update
        // Broadcast removed

        Notification::make()
            ->title('Antrian berhasil direset')
            ->body("{$deleted} antrian dihapus")
            ->success()
            ->send();
    }

    public function getListeners(): array
    {
        return [
            'refresh-antrian' => 'refreshData',
        ];
    }
}
