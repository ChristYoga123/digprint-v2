<?php

namespace App\Filament\Ppic\Pages;

use Carbon\Carbon;
use App\Models\User;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use App\Models\KaryawanPekerjaan;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use App\Enums\KaryawanPekerjaan\TipeEnum;

class AbsenLemburMasukPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-right-start-on-rectangle';
    
    protected static ?string $navigationLabel = 'Absen Lembur Masuk';
    
    protected static ?string $title = 'Absen Lembur Masuk';

    protected static string $view = 'filament.ppic.pages.absen-lembur-masuk-page';

    public ?array $data = [];
    public ?string $message = null;
    public ?string $messageType = null;

    // Window time in minutes (15 menit sebelum jam masuk)
    protected const WINDOW_MINUTES = 15;

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(1)
                    ->schema([
                        TextInput::make('nik')
                            ->label('Masukkan NIK Anda')
                            ->placeholder('Scan atau ketik NIK')
                            ->required()
                            ->autofocus()
                            ->extraInputAttributes(['style' => 'font-size: 1.5rem; padding: 1rem; text-align: center;']),
                    ])
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Absen Masuk')
                ->submit('save'),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $nik = $data['nik'] ?? null;

        $this->message = null;
        $this->messageType = null;

        if (!$nik) {
            Notification::make()
                ->danger()
                ->title('Gagal')
                ->body('NIK harus diisi')
                ->send();
            return;
        }

        // Cari karyawan berdasarkan NIK
        $karyawan = User::where('nik', $nik)
            ->where('is_active', true)
            ->first();

        if (!$karyawan) {
            $this->message = 'NIK tidak ditemukan atau karyawan tidak aktif.';
            $this->messageType = 'error';
            Notification::make()
                ->danger()
                ->title('Gagal')
                ->body($this->message)
                ->send();
            return;
        }

        $now = Carbon::now();

        // Cari lembur yang diapprove untuk karyawan ini hari ini
        // yang belum absen masuk dan dalam window waktu yang diizinkan
        $lemburRecord = KaryawanPekerjaan::where('karyawan_id', $karyawan->id)
            ->where('tipe', TipeEnum::LEMBUR)
            ->where('apakah_diapprove_lembur', true)
            ->whereNull('jam_aktual_mulai') // Belum absen masuk
            ->whereDate('jam_lembur_mulai', $now->toDateString()) // Lembur hari ini
            ->orderBy('jam_lembur_mulai', 'asc')
            ->first();

        if (!$lemburRecord) {
            // Cek apakah sudah absen
            $sudahAbsen = KaryawanPekerjaan::where('karyawan_id', $karyawan->id)
                ->where('tipe', TipeEnum::LEMBUR)
                ->where('apakah_diapprove_lembur', true)
                ->whereNotNull('jam_aktual_mulai')
                ->whereDate('jam_lembur_mulai', $now->toDateString())
                ->first();

            if ($sudahAbsen) {
                $this->message = "Karyawan {$karyawan->name} sudah absen masuk hari ini pada jam " . 
                    Carbon::parse($sudahAbsen->jam_aktual_mulai)->format('H:i') . ".";
                $this->messageType = 'warning';
            } else {
                // Cek apakah ada lembur yang pending approval
                $pendingLembur = KaryawanPekerjaan::where('karyawan_id', $karyawan->id)
                    ->where('tipe', TipeEnum::LEMBUR)
                    ->whereNull('apakah_diapprove_lembur')
                    ->whereDate('jam_lembur_mulai', $now->toDateString())
                    ->first();

                if ($pendingLembur) {
                    $this->message = "Pengajuan lembur untuk {$karyawan->name} belum disetujui.";
                    $this->messageType = 'warning';
                } else {
                    $this->message = "Tidak ada jadwal lembur yang disetujui untuk {$karyawan->name} hari ini.";
                    $this->messageType = 'error';
                }
            }
            Notification::make()
                ->danger()
                ->title('Gagal')
                ->body($this->message)
                ->send();
            return;
        }

        // Cek window waktu absen masuk
        // Karyawan bisa absen mulai dari 15 menit sebelum jam_lembur_mulai
        $jamMulai = Carbon::parse($lemburRecord->jam_lembur_mulai);
        $windowStart = $jamMulai->copy()->subMinutes(self::WINDOW_MINUTES);
        $windowEnd = $jamMulai->copy()->addMinutes(15); // Toleransi 30 menit setelah jam mulai

        if ($now->lt($windowStart)) {
            $this->message = "Belum waktunya absen masuk. " .
                "Absen bisa dilakukan mulai jam " . $windowStart->format('H:i') . " " .
                "(15 menit sebelum jam lembur " . $jamMulai->format('H:i') . ").";
            $this->messageType = 'warning';
            Notification::make()
                ->warning()
                ->title('Belum Waktunya')
                ->body($this->message)
                ->send();
            return;
        }

        if ($now->gt($windowEnd)) {
            $this->message = "Waktu absen masuk sudah terlewat. " .
                "Batas absen masuk adalah jam " . $windowEnd->format('H:i') . ". " .
                "Silakan hubungi admin.";
            $this->messageType = 'error';
            Notification::make()
                ->danger()
                ->title('Waktu Terlewat')
                ->body($this->message)
                ->send();
            return;
        }

        // Lakukan absen masuk
        $lemburRecord->update([
            'jam_aktual_mulai' => $now,
        ]);

        $this->message = "Absen masuk berhasil untuk {$karyawan->name}! " .
            "Jam masuk tercatat: " . $now->format('H:i') . ". " .
            "Jadwal lembur: " . $jamMulai->format('H:i') . " - " . 
            Carbon::parse($lemburRecord->jam_lembur_selesai)->format('H:i') . ".";
        $this->messageType = 'success';

        Notification::make()
            ->title('Absen Masuk Berhasil')
            ->body("Karyawan: {$karyawan->name} | Jam: " . $now->format('H:i'))
            ->success()
            ->send();

        $this->form->fill();
    }
}
