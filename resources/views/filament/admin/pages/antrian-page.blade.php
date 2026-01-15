<x-filament-panels::page wire:poll.5s="refreshData">
    <style>
        .antrian-container {
            display: grid;
            gap: 20px;
        }

        @media (min-width: 1024px) {
            .antrian-container {
                grid-template-columns: 300px 1fr;
            }
        }

        /* Deskprint Selector */
        .deskprint-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }

        .deskprint-btn {
            padding: 20px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            background: white;
            cursor: pointer;
            text-align: center;
            transition: all 0.2s;
        }

        .dark .deskprint-btn {
            background: #1f2937;
            border-color: #374151;
        }

        .deskprint-btn:hover {
            border-color: #10b981;
            transform: translateY(-2px);
        }

        .deskprint-btn.active {
            border-color: #10b981;
            background: #ecfdf5;
        }

        .dark .deskprint-btn.active {
            background: rgba(16, 185, 129, 0.15);
        }

        .deskprint-btn.busy {
            border-color: #f59e0b;
            background: #fffbeb;
        }

        .dark .deskprint-btn.busy {
            background: rgba(245, 158, 11, 0.15);
        }

        .deskprint-number {
            font-size: 28px;
            font-weight: 700;
            color: #111827;
        }

        .dark .deskprint-number {
            color: #f9fafb;
        }

        .deskprint-label {
            font-size: 12px;
            color: #6b7280;
            margin-top: 4px;
        }

        .deskprint-status {
            font-size: 11px;
            margin-top: 6px;
            padding: 2px 8px;
            border-radius: 10px;
            display: inline-block;
        }

        .deskprint-status.free {
            background: #dcfce7;
            color: #16a34a;
        }

        .deskprint-status.busy {
            background: #fef3c7;
            color: #d97706;
        }

        /* Current Antrian Panel */
        .current-panel {
            background: white;
            border-radius: 16px;
            padding: 30px;
            text-align: center;
            border: 2px solid #e5e7eb;
        }

        .dark .current-panel {
            background: #1f2937;
            border-color: #374151;
        }

        .current-panel.has-antrian {
            border-color: #10b981;
            background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
        }

        .dark .current-panel.has-antrian {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(16, 185, 129, 0.2) 100%);
        }

        .current-title {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 10px;
        }

        .current-number {
            font-size: 120px;
            font-weight: 800;
            color: #059669;
            line-height: 1;
            font-family: 'SF Pro Display', -apple-system, BlinkMacSystemFont, sans-serif;
        }

        .current-deskprint {
            font-size: 24px;
            color: #374151;
            margin-top: 10px;
        }

        .dark .current-deskprint {
            color: #d1d5db;
        }

        .current-empty {
            color: #9ca3af;
            font-size: 18px;
            padding: 40px 0;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 10px;
            margin-top: 30px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 14px 28px;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            transition: all 0.2s;
        }

        .action-btn.primary {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }

        .action-btn.primary:hover {
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.5);
            transform: translateY(-2px);
        }

        .action-btn.secondary {
            background: #f3f4f6;
            color: #374151;
        }

        .dark .action-btn.secondary {
            background: #374151;
            color: #f9fafb;
        }

        .action-btn.warning {
            background: #fef3c7;
            color: #d97706;
        }

        .action-btn.danger {
            background: #fee2e2;
            color: #dc2626;
        }

        /* Stats - FIXED overflow */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }

        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 12px 8px;
            text-align: center;
            border: 1px solid #e5e7eb;
        }

        .dark .stat-card {
            background: #1f2937;
            border-color: #374151;
        }

        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #111827;
        }

        .dark .stat-value {
            color: #f9fafb;
        }

        .stat-label {
            font-size: 10px;
            color: #6b7280;
            margin-top: 2px;
        }

        .stat-card.waiting .stat-value {
            color: #6b7280;
        }

        .stat-card.called .stat-value {
            color: #f59e0b;
        }

        .stat-card.completed .stat-value {
            color: #10b981;
        }

        .stat-card.skipped .stat-value {
            color: #ef4444;
        }

        /* All Called List */
        .called-list {
            margin-top: 20px;
        }

        .called-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 16px;
            background: #fef3c7;
            border-radius: 8px;
            margin-bottom: 8px;
        }

        .dark .called-item {
            background: rgba(245, 158, 11, 0.15);
        }

        .called-item-number {
            font-size: 24px;
            font-weight: 700;
            color: #d97706;
        }

        .called-item-deskprint {
            font-size: 14px;
            color: #92400e;
        }

        .dark .called-item-deskprint {
            color: #fcd34d;
        }
    </style>

    <div class="antrian-container">
        {{-- LEFT: Deskprint Selector --}}
        <div>
            <x-filament::section>
                <x-slot name="heading">🖥️ Pilih Loket</x-slot>

                <div class="deskprint-grid">
                    @for ($i = 1; $i <= 6; $i++)
                        @php
                            $isBusy = collect($allCalledAntrians)->contains('deskprint_number', $i);
                            $busyAntrian = collect($allCalledAntrians)->firstWhere('deskprint_number', $i);
                        @endphp
                        <div class="deskprint-btn {{ $selectedDeskprint === $i ? 'active' : '' }} {{ $isBusy ? 'busy' : '' }}"
                            wire:click="selectDeskprint({{ $i }})">
                            <div class="deskprint-number">{{ $i }}</div>
                            <div class="deskprint-label">Loket</div>
                            @if ($isBusy && $busyAntrian)
                                <div class="deskprint-status busy">No. {{ $busyAntrian['nomor_antrian'] }}</div>
                            @else
                                <div class="deskprint-status free">Kosong</div>
                            @endif
                        </div>
                    @endfor
                </div>
            </x-filament::section>

            {{-- Stats --}}
            <x-filament::section style="margin-top: 20px;">
                <x-slot name="heading">📊 Statistik Hari Ini</x-slot>

                <div class="stats-grid">
                    <div class="stat-card waiting">
                        <div class="stat-value">{{ $statistik['waiting'] ?? 0 }}</div>
                        <div class="stat-label">Menunggu</div>
                    </div>
                    <div class="stat-card called">
                        <div class="stat-value">{{ $statistik['called'] ?? 0 }}</div>
                        <div class="stat-label">Dipanggil</div>
                    </div>
                    <div class="stat-card completed">
                        <div class="stat-value">{{ $statistik['completed'] ?? 0 }}</div>
                        <div class="stat-label">Selesai</div>
                    </div>
                    <div class="stat-card skipped">
                        <div class="stat-value">{{ $statistik['skipped'] ?? 0 }}</div>
                        <div class="stat-label">Dilewati</div>
                    </div>
                </div>

                <div style="margin-top: 16px; text-align: center;">
                    <button type="button" class="action-btn danger" wire:click="resetAntrian"
                        wire:confirm="Yakin ingin mereset semua antrian hari ini?">
                        🔄 Reset Antrian
                    </button>
                </div>
            </x-filament::section>
        </div>

        {{-- RIGHT: Current Antrian --}}
        <div>
            @if ($selectedDeskprint)
                <div class="current-panel {{ $currentAntrian ? 'has-antrian' : '' }}">
                    @if ($currentAntrian)
                        <div class="current-title">SEDANG DILAYANI</div>
                        <div class="current-number">
                            {{ str_pad($currentAntrian['nomor_antrian'], 3, '0', STR_PAD_LEFT) }}</div>
                        <div class="current-deskprint">Loket {{ $selectedDeskprint }}</div>

                        <div class="action-buttons">
                            <button type="button" class="action-btn secondary" wire:click="panggilUlang">
                                🔊 Panggil Ulang
                            </button>
                            <button type="button" class="action-btn warning" wire:click="lewatiAntrian">
                                ⏭️ Lewati
                            </button>
                            <button type="button" class="action-btn primary" wire:click="selesaikanAntrian">
                                ✅ Selesai
                            </button>
                        </div>
                    @else
                        <div class="current-empty">
                            <p>Tidak ada antrian yang sedang dilayani</p>
                            <p style="font-size: 14px; margin-top: 10px;">Loket {{ $selectedDeskprint }}</p>
                        </div>

                        <div class="action-buttons">
                            <button type="button" class="action-btn primary" wire:click="panggilBerikutnya">
                                📢 Panggil Berikutnya
                            </button>
                        </div>
                    @endif
                </div>

                {{-- Called List --}}
                @if (count($allCalledAntrians) > 0)
                    <div class="called-list">
                        <x-filament::section>
                            <x-slot name="heading">📋 Sedang Dipanggil</x-slot>

                            @foreach ($allCalledAntrians as $called)
                                <div class="called-item">
                                    <div class="called-item-number">
                                        {{ str_pad($called['nomor_antrian'], 3, '0', STR_PAD_LEFT) }}</div>
                                    <div class="called-item-deskprint">→ Loket {{ $called['deskprint_number'] }}</div>
                                </div>
                            @endforeach
                        </x-filament::section>
                    </div>
                @endif
            @else
                <div class="current-panel">
                    <div class="current-empty">
                        <p style="font-size: 48px; margin-bottom: 20px;">👈</p>
                        <p>Pilih Loket untuk memulai</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- 
        Voice announcement HANYA di halaman Display (/antrian/display)
        Admin page tidak perlu voice, hanya update data via Livewire
    --}}
</x-filament-panels::page>
