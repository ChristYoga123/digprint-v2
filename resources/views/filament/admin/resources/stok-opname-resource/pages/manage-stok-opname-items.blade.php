<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Info Card --}}
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-content p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Kode</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $record->kode }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Tanggal Opname</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $record->tanggal_opname?->format('d M Y') ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Status</p>
                        <div class="mt-1">
                            <x-filament::badge :color="$record->status->getColor()" class="inline-flex">
                                {{ $record->status->getLabel() }}
                            </x-filament::badge>
                        </div>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Item</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $record->total_items }}</p>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Item Approved</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $record->approved_items }} / {{ $record->total_items }}
                        </p>
                    </div>
                </div>

                @if ($record->nama)
                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Nama/Deskripsi</p>
                        <p class="text-gray-900 dark:text-white">{{ $record->nama }}</p>
                    </div>
                @endif

                @if ($record->catatan)
                    <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Catatan</p>
                        <p class="text-gray-900 dark:text-white">{{ $record->catatan }}</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Summary Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div
                class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-green-100 p-2 dark:bg-green-500/20">
                        <x-heroicon-o-arrow-trending-up class="h-6 w-6 text-green-600 dark:text-green-400" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Stok Lebih (+)</p>
                        <p class="text-xl font-semibold text-green-600 dark:text-green-400">
                            {{ number_format($record->total_positive_difference, 2) }}
                        </p>
                    </div>
                </div>
            </div>

            <div
                class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-red-100 p-2 dark:bg-red-500/20">
                        <x-heroicon-o-arrow-trending-down class="h-6 w-6 text-red-600 dark:text-red-400" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Stok Kurang (-)</p>
                        <p class="text-xl font-semibold text-red-600 dark:text-red-400">
                            {{ number_format($record->total_negative_difference, 2) }}
                        </p>
                    </div>
                </div>
            </div>

            <div
                class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-4">
                <div class="flex items-center gap-3">
                    <div class="rounded-lg bg-blue-100 p-2 dark:bg-blue-500/20">
                        <x-heroicon-o-scale class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Selisih Bersih</p>
                        <p
                            class="text-xl font-semibold {{ $record->net_difference >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $record->net_difference >= 0 ? '+' : '' }}{{ number_format($record->net_difference, 2) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Table --}}
        {{ $this->table }}
    </div>
</x-filament-panels::page>
