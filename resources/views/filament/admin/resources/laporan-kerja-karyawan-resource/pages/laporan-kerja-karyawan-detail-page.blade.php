<x-filament-panels::page>
    @livewire(\App\Filament\Admin\Resources\LaporanKerjaKaryawanResource\Widgets\StatKerjaKaryawanWidget::class, ['record' => $this->karyawan])

    {{ $this->table }}
</x-filament-panels::page>
