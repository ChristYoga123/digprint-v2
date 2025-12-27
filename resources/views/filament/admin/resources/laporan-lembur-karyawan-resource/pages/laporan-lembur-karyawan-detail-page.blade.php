<x-filament-panels::page>
    @livewire(\App\Filament\Admin\Resources\LaporanLemburKaryawanResource\Widgets\StatLemburKaryawanWidget::class, ['record' => $this->karyawan])

    {{ $this->table }}
</x-filament-panels::page>
