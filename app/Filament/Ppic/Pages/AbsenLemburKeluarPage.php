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

class AbsenLemburKeluarPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-left-start-on-rectangle';
    
    protected static ?string $navigationLabel = 'Absen Lembur Keluar';
    
    protected static ?string $title = 'Absen Lembur Keluar';

    protected static string $view = 'filament.ppic.pages.absen-lembur-keluar-page';

    public ?array $data = [];
    public ?string $message = null;
    public ?string $messageType = null;

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
                ->label('Absen Keluar')
                ->color('danger')
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

        // Cari lembur yang sudah absen masuk tapi belum absen keluar
        $lemburRecord = KaryawanPekerjaan::where('karyawan_id', $karyawan->id)
            ->where('tipe', TipeEnum::LEMBUR)
            ->where('apakah_diapprove_lembur', true)
            ->whereNotNull('jam_aktual_mulai') // Sudah absen masuk
            ->whereNull('jam_aktual_selesai') // Belum absen keluar
            ->orderBy('jam_lembur_mulai', 'desc')
            ->first();

        if (!$lemburRecord) {
            // Cek apakah sudah absen keluar
            $sudahAbsenKeluar = KaryawanPekerjaan::where('karyawan_id', $karyawan->id)
                ->where('tipe', TipeEnum::LEMBUR)
                ->where('apakah_diapprove_lembur', true)
                ->whereNotNull('jam_aktual_mulai')
                ->whereNotNull('jam_aktual_selesai')
                ->whereDate('jam_lembur_mulai', $now->toDateString())
                ->orderBy('jam_aktual_selesai', 'desc')
                ->first();

            if ($sudahAbsenKeluar) {
                $this->message = "Karyawan {$karyawan->name} sudah absen keluar hari ini pada jam " . 
                    Carbon::parse($sudahAbsenKeluar->jam_aktual_selesai)->format('H:i') . ".";
                $this->messageType = 'warning';
            } else {
                // Cek apakah belum absen masuk
                $belumAbsenMasuk = KaryawanPekerjaan::where('karyawan_id', $karyawan->id)
                    ->where('tipe', TipeEnum::LEMBUR)
                    ->where('apakah_diapprove_lembur', true)
                    ->whereNull('jam_aktual_mulai')
                    ->whereDate('jam_lembur_mulai', $now->toDateString())
                    ->first();

                if ($belumAbsenMasuk) {
                    $this->message = "Karyawan {$karyawan->name} belum absen masuk. Silakan absen masuk terlebih dahulu.";
                    $this->messageType = 'warning';
                } else {
                    $this->message = "Tidak ada sesi lembur aktif untuk {$karyawan->name}. " .
                        "Pastikan sudah absen masuk terlebih dahulu.";
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

        // Cek apakah absen keluar terlalu cepat (minimal 30 menit setelah masuk)
        $jamMasuk = Carbon::parse($lemburRecord->jam_aktual_mulai);
        $minAbsenKeluar = $jamMasuk->copy()->addMinutes(30);

        if ($now->lt($minAbsenKeluar)) {
            $this->message = "Belum bisa absen keluar. Minimal waktu kerja 30 menit. " .
                "Bisa absen keluar mulai jam " . $minAbsenKeluar->format('H:i') . ".";
            $this->messageType = 'warning';
            Notification::make()
                ->warning()
                ->title('Belum Waktunya')
                ->body($this->message)
                ->send();
            return;
        }

        // Hitung durasi kerja aktual
        $durasiKerja = $jamMasuk->diff($now);
        $durasiString = '';
        if ($durasiKerja->h > 0) {
            $durasiString .= $durasiKerja->h . ' jam ';
        }
        $durasiString .= $durasiKerja->i . ' menit';

        // Lakukan absen keluar
        $lemburRecord->update([
            'jam_aktual_selesai' => $now,
        ]);

        $jamLemburMulai = Carbon::parse($lemburRecord->jam_lembur_mulai);
        $jamLemburSelesai = Carbon::parse($lemburRecord->jam_lembur_selesai);

        $this->message = "Absen keluar berhasil untuk {$karyawan->name}! " .
            "Jam keluar tercatat: " . $now->format('H:i') . ". " .
            "Durasi kerja: {$durasiString}. " .
            "Jadwal lembur: " . $jamLemburMulai->format('H:i') . " - " . $jamLemburSelesai->format('H:i') . ".";
        $this->messageType = 'success';

        Notification::make()
            ->title('Absen Keluar Berhasil')
            ->body("Karyawan: {$karyawan->name} | Durasi: {$durasiString}")
            ->success()
            ->send();

        $this->form->fill();
    }
}
