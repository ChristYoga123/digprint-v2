<x-filament-panels::page>
    <style>
        .kasir-grid {
            display: grid;
            gap: 20px;
        }
        @media (min-width: 1024px) {
            .kasir-grid.has-kalkulasi {
                grid-template-columns: 1fr 380px;
            }
            .kasir-grid.no-kalkulasi {
                grid-template-columns: 1fr 1fr;
            }
        }
        
        /* Customer Header */
        .customer-banner {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 12px;
            padding: 16px 20px;
            color: white;
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        .customer-banner .info-label {
            font-size: 11px;
            opacity: 0.85;
            text-transform: uppercase;
        }
        .customer-banner .info-value {
            font-size: 16px;
            font-weight: 700;
        }
        
        /* Cart Items */
        .cart-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            max-height: 45vh;
            overflow-y: auto;
            padding-right: 8px;
        }
        .cart-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 14px;
        }
        .dark .cart-card {
            background: #1f2937;
            border-color: #374151;
        }
        .cart-card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
        }
        .cart-card-info {
            flex: 1;
            min-width: 0;
        }
        .cart-label {
            display: inline-block;
            background: #dbeafe;
            color: #1d4ed8;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 4px;
            margin-bottom: 6px;
        }
        .dark .cart-label {
            background: #1e3a5f;
            color: #93c5fd;
        }
        .cart-name {
            font-size: 15px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 6px;
        }
        .dark .cart-name {
            color: #f9fafb;
        }
        .cart-specs {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            font-size: 12px;
            color: #6b7280;
        }
        .cart-price-row {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .cart-price {
            font-size: 16px;
            font-weight: 700;
            color: #059669;
            white-space: nowrap;
        }
        .cart-delete-btn {
            width: 28px;
            height: 28px;
            border: none;
            background: #fee2e2;
            color: #dc2626;
            border-radius: 6px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
        .cart-delete-btn:hover {
            background: #fecaca;
        }
        .dark .cart-delete-btn {
            background: rgba(220, 38, 38, 0.2);
        }
        .cart-note {
            margin-top: 10px;
            padding: 8px 10px;
            background: #fef3c7;
            border-left: 3px solid #f59e0b;
            border-radius: 0 6px 6px 0;
            font-size: 12px;
            color: #92400e;
        }
        .dark .cart-note {
            background: rgba(245, 158, 11, 0.15);
            color: #fcd34d;
        }
        .cart-detail-design {
            margin-top: 8px;
            padding: 6px 10px;
            background: #ede9fe;
            border-radius: 6px;
            font-size: 12px;
            color: #6d28d9;
        }
        .dark .cart-detail-design {
            background: rgba(139, 92, 246, 0.15);
            color: #c4b5fd;
        }
        .cart-detail-addon {
            margin-top: 6px;
            padding: 6px 10px;
            background: #dbeafe;
            border-radius: 6px;
            font-size: 12px;
            color: #1d4ed8;
        }
        .dark .cart-detail-addon {
            background: rgba(59, 130, 246, 0.15);
            color: #93c5fd;
        }
        .cart-price-breakdown {
            margin-top: 10px;
            padding: 10px;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        .dark .cart-price-breakdown {
            background: #0f172a;
            border-color: #334155;
        }
        .price-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            padding: 4px 0;
            color: #475569;
        }
        .dark .price-line {
            color: #94a3b8;
        }
        .price-line.design {
            color: #7c3aed;
        }
        .dark .price-line.design {
            color: #a78bfa;
        }
        .price-line.addon {
            color: #0284c7;
            font-size: 11px;
            padding-left: 8px;
        }
        .dark .price-line.addon {
            color: #7dd3fc;
        }
        .price-line span:last-child {
            font-weight: 600;
            font-family: 'SF Mono', 'Monaco', 'Inconsolata', monospace;
        }
        .cart-discount {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px dashed #e5e7eb;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .dark .cart-discount {
            border-color: #374151;
        }
        .cart-discount label {
            font-size: 12px;
            color: #6b7280;
            white-space: nowrap;
        }
        .cart-discount input {
            flex: 1;
            padding: 6px 10px;
            font-size: 13px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background: #f9fafb;
        }
        .dark .cart-discount input {
            background: #111827;
            border-color: #374151;
            color: white;
        }
        
        /* Checkout Panel */
        .checkout-panel {
            background: #f9fafb;
            border-radius: 12px;
            padding: 16px;
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        .dark .checkout-panel {
            background: #111827;
        }
        .checkout-title {
            font-size: 13px;
            font-weight: 700;
            color: #374151;
            margin-bottom: 10px;
        }
        .dark .checkout-title {
            color: #d1d5db;
        }
        .checkout-box {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 12px;
        }
        .dark .checkout-box {
            background: #1f2937;
            border-color: #374151;
        }
        .payment-btns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-bottom: 12px;
        }
        .payment-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 10px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            background: white;
            color: #374151;
        }
        .dark .payment-btn {
            background: #1f2937;
            border-color: #374151;
            color: #f9fafb;
        }
        .payment-btn.active-lunas {
            border-color: #10b981;
            background: #ecfdf5;
            color: #059669;
        }
        .dark .payment-btn.active-lunas {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
        }
        .payment-btn.active-top {
            border-color: #f97316;
            background: #fff7ed;
            color: #ea580c;
        }
        .dark .payment-btn.active-top {
            background: rgba(249, 115, 22, 0.15);
            color: #fb923c;
        }
        .form-row {
            margin-bottom: 10px;
        }
        .form-label {
            display: block;
            font-size: 11px;
            font-weight: 500;
            color: #6b7280;
            margin-bottom: 4px;
        }
        .form-input {
            width: 100%;
            padding: 8px 10px;
            font-size: 13px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background: white;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }
        .dark .form-input {
            background: #111827;
            border-color: #374151;
            color: white;
        }
        select.form-input {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 8px center;
            background-size: 16px;
            padding-right: 32px;
        }
        .form-hint {
            font-size: 10px;
            color: #9ca3af;
            margin-top: 3px;
        }
        
        /* Summary */
        .summary-box {
            background: white;
            border: 2px solid #10b981;
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 12px;
        }
        .dark .summary-box {
            background: #1f2937;
        }
        .summary-header {
            background: #10b981;
            color: white;
            padding: 10px 14px;
            font-weight: 700;
            font-size: 13px;
        }
        .summary-content {
            padding: 14px;
        }
        .summary-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 4px 0;
            font-size: 13px;
        }
        .summary-line.discount {
            color: #dc2626;
        }
        .summary-line.total {
            border-top: 2px solid #10b981;
            margin-top: 8px;
            padding-top: 10px;
            font-weight: 700;
        }
        .summary-line.total .amount {
            font-size: 20px;
            color: #059669;
        }
        .summary-diskon-input {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px dashed #e5e7eb;
        }
        .dark .summary-diskon-input {
            border-color: #374151;
        }
        .summary-diskon-input label {
            display: block;
            font-size: 11px;
            color: #6b7280;
            margin-bottom: 4px;
        }
        .summary-diskon-input input {
            width: 100%;
            padding: 6px 10px;
            font-size: 13px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            background: #f9fafb;
        }
        .dark .summary-diskon-input input {
            background: #111827;
            border-color: #374151;
            color: white;
        }
        .summary-line.kembalian {
            background: #dcfce7;
            margin: 10px -14px -14px;
            padding: 10px 14px;
            color: #16a34a;
            font-weight: 600;
        }
        .dark .summary-line.kembalian {
            background: rgba(22, 163, 74, 0.15);
        }
        .summary-line.sisa {
            background: #ffedd5;
            margin: 10px -14px -14px;
            padding: 10px 14px;
            color: #ea580c;
            font-weight: 600;
        }
        .dark .summary-line.sisa {
            background: rgba(234, 88, 12, 0.15);
        }
        
        /* Submit Button */
        .btn-bayar {
            width: 100%;
            padding: 14px;
            font-size: 15px;
            font-weight: 700;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }
        .btn-bayar:hover {
            box-shadow: 0 6px 16px rgba(16, 185, 129, 0.5);
        }
        
        /* Empty State */
        .empty-box {
            text-align: center;
            padding: 60px 40px;
        }
        .empty-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: #f3f4f6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .dark .empty-icon {
            background: #374151;
        }
        .empty-title {
            font-size: 18px;
            font-weight: 600;
            color: #111827;
            margin-bottom: 8px;
        }
        .dark .empty-title {
            color: #f9fafb;
        }
        .empty-desc {
            font-size: 14px;
            color: #6b7280;
        }
    </style>

    @if($selectedKalkulasi)
        <div class="kasir-grid has-kalkulasi">
            {{-- LEFT --}}
            <div>
                {{-- Kalkulasi Table --}}
                <x-filament::section style="margin-bottom: 16px;">
                    <x-slot name="heading">📋 Pilih Kalkulasi</x-slot>
                    {{ $this->table }}
                </x-filament::section>

                {{-- Customer Banner --}}
                <div class="customer-banner">
                    <div>
                        <div class="info-label">Kalkulasi</div>
                        <div class="info-value">{{ $selectedKalkulasi['kode'] }}</div>
                    </div>
                    <div style="text-align: right;">
                        <div class="info-label">Customer</div>
                        <div class="info-value">{{ $selectedKalkulasi['customer_nama'] }}</div>
                    </div>
                    <x-filament::button color="danger" size="sm" wire:click="clearCart" icon="heroicon-o-x-mark">
                        Batal
                    </x-filament::button>
                </div>

                {{-- Cart Items --}}
                <x-filament::section>
                    <x-slot name="heading">🛒 Item Pesanan ({{ count($cartItems) }})</x-slot>
                    
                    <div class="cart-list">
                        @foreach($cartItems as $index => $item)
                            <div class="cart-card">
                                <div class="cart-card-top">
                                    <div class="cart-card-info">
                                        @if(!empty($item['judul_pesanan']))
                                            <span class="cart-label">{{ $item['judul_pesanan'] }}</span>
                                        @endif
                                        <div class="cart-name">{{ $item['produk_nama'] }}</div>
                                        <div class="cart-specs">
                                            <span>📦 {{ $item['jumlah'] }} pcs</span>
                                            @if($item['panjang'] && $item['lebar'])
                                                <span>📐 {{ $item['panjang'] }} × {{ $item['lebar'] }} m</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="cart-price-row">
                                        <div class="cart-price">Rp {{ number_format($item['total_harga_produk'], 0, ',', '.') }}</div>
                                        <button type="button" class="cart-delete-btn" wire:click="removeFromCart({{ $index }})" title="Hapus">
                                            🗑️
                                        </button>
                                    </div>
                                </div>

                                {{-- Price Breakdown --}}
                                <div class="cart-price-breakdown">
                                    {{-- Harga Produk --}}
                                    <div class="price-line">
                                        <span>🏷️ Harga Produk</span>
                                        <span>Rp {{ number_format($item['harga_produk'] ?? 0, 0, ',', '.') }}</span>
                                    </div>
                                    
                                    {{-- Design --}}
                                    @if(!empty($item['design_nama']))
                                        <div class="price-line design">
                                            <span>🎨 Design: {{ $item['design_nama'] }}</span>
                                            <span>Rp {{ number_format($item['design_harga'] ?? 0, 0, ',', '.') }}</span>
                                        </div>
                                    @elseif(!empty($item['link_design']))
                                        <div class="price-line design">
                                            <span>🎨 <a href="{{ $item['link_design'] }}" target="_blank" style="text-decoration: underline;">Design Customer</a></span>
                                            <span>Rp 0</span>
                                        </div>
                                    @endif

                                    {{-- Addons --}}
                                    @if(!empty($item['addon_details']))
                                        @foreach($item['addon_details'] as $addon)
                                            <div class="price-line addon">
                                                <span>✨ {{ $addon['nama'] }} ({{ number_format($addon['harga'], 0, ',', '.') }} × {{ $item['jumlah'] }})</span>
                                                <span>Rp {{ number_format($addon['total'], 0, ',', '.') }}</span>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>

                                {{-- Detail: Keterangan --}}
                                @if(!empty($item['keterangan']))
                                    <div class="cart-note">📝 <strong>Catatan:</strong> {{ $item['keterangan'] }}</div>
                                @endif

                                <div class="cart-discount">
                                    <label>Diskon:</label>
                                    <input type="text" 
                                        wire:model.blur="itemDiscounts.{{ $index }}" 
                                        wire:change="validateItemDiscount({{ $index }}, {{ $item['total_harga_produk'] }})"
                                        placeholder="0"
                                        x-data
                                        x-on:input="$el.value = $el.value.replace(/[^0-9]/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.')"
                                    >
                                    <span style="font-size: 10px; color: #9ca3af;">max: Rp {{ number_format($item['total_harga_produk'], 0, ',', '.') }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>
            </div>

            {{-- RIGHT: Checkout Panel --}}
            <div>
                <form wire:submit="checkout">
                    <div class="checkout-panel">
                        {{-- Pembayaran --}}
                        <div class="checkout-title">💳 Pembayaran</div>
                        <div class="checkout-box">
                            <div class="payment-btns">
                                <button type="button" 
                                    class="payment-btn {{ $statusPembayaran == \App\Enums\BahanMutasiFaktur\StatusPembayaranEnum::LUNAS->value ? 'active-lunas' : '' }}"
                                    wire:click="selectPembayaranLunas"
                                >
                                    ✅ Lunas
                                </button>
                                <button type="button" 
                                    class="payment-btn {{ $statusPembayaran == \App\Enums\BahanMutasiFaktur\StatusPembayaranEnum::TERM_OF_PAYMENT->value ? 'active-top' : '' }}"
                                    wire:click="selectPembayaranTOP"
                                >
                                    ⏳ TOP
                                </button>
                            </div>

                            @if($statusPembayaran)
                                <div class="form-row">
                                    <label class="form-label">Metode Pembayaran</label>
                                    <select class="form-input" wire:model="metodePembayaran">
                                        <option value="">-- Pilih --</option>
                                        @foreach(getBankData() as $key => $value)
                                            <option value="{{ $key }}">{{ $value }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="form-row">
                                    <label class="form-label">Jumlah Bayar {{ $statusPembayaran == \App\Enums\BahanMutasiFaktur\StatusPembayaranEnum::TERM_OF_PAYMENT->value ? '(DP)' : '' }}</label>
                                    <input type="text" class="form-input" wire:model.blur="jumlahBayar" placeholder="0"
                                        x-data
                                        x-on:input="$el.value = $el.value.replace(/[^0-9]/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.')"
                                    >
                                    @if($statusPembayaran == \App\Enums\BahanMutasiFaktur\StatusPembayaranEnum::LUNAS->value)
                                        <div class="form-hint">Min: Rp {{ number_format($this->getCartTotalAfterDiscount(), 0, ',', '.') }}</div>
                                    @else
                                        <div class="form-hint">Bisa 0 jika menyusul</div>
                                    @endif
                                </div>

                                <div class="form-row" style="margin-bottom: 0;">
                                    @if($statusPembayaran == \App\Enums\BahanMutasiFaktur\StatusPembayaranEnum::LUNAS->value)
                                        <label class="form-label">Tanggal Bayar</label>
                                        <input type="date" class="form-input" wire:model="tanggalPembayaran"
                                            x-data
                                            x-init="if(!$wire.tanggalPembayaran) $wire.tanggalPembayaran = '{{ date('Y-m-d') }}'"
                                        >
                                    @else
                                        <label class="form-label">Jatuh Tempo</label>
                                        <input type="date" class="form-input" wire:model="tanggalJatuhTempo"
                                            x-data
                                            x-init="if(!$wire.tanggalJatuhTempo) $wire.tanggalJatuhTempo = '{{ date('Y-m-d') }}'"
                                        >
                                    @endif
                                </div>
                            @endif
                        </div>

                        {{-- Cetak Nota --}}
                        <div class="checkout-title">🖨️ Cetak</div>
                        <div class="checkout-box" style="margin-bottom: 12px;">
                            <select class="form-input" wire:model="printNotaSize">
                                <option value="thermal">📱 Thermal (80mm)</option>
                                <option value="a5">📄 A5</option>
                                <option value="a4">📑 A4</option>
                            </select>
                        </div>

                        {{-- Summary --}}
                        <div class="summary-box">
                            <div class="summary-header">💰 RINGKASAN</div>
                            <div class="summary-content">
                                <div class="summary-line">
                                    <span>Subtotal</span>
                                    <span>Rp {{ number_format($this->getCartTotal(), 0, ',', '.') }}</span>
                                </div>
                                
                                @php
                                    $totalDiskonItem = 0;
                                    foreach (($itemDiscounts ?? []) as $disc) {
                                        $totalDiskonItem += (int) str_replace(['.', ','], '', (string) $disc);
                                    }
                                    $totalDiskonInvoiceVal = !empty($totalDiskonInvoice) ? (int) str_replace(['.', ','], '', (string) $totalDiskonInvoice) : 0;
                                    $totalDiskon = $totalDiskonItem + $totalDiskonInvoiceVal;
                                @endphp
                                
                                @if($totalDiskonItem > 0)
                                    <div class="summary-line discount">
                                        <span>Diskon Item</span>
                                        <span>- Rp {{ number_format($totalDiskonItem, 0, ',', '.') }}</span>
                                    </div>
                                @endif
                                
                                @if($totalDiskonInvoiceVal > 0)
                                    <div class="summary-line discount">
                                        <span>Diskon Invoice</span>
                                        <span>- Rp {{ number_format($totalDiskonInvoiceVal, 0, ',', '.') }}</span>
                                    </div>
                                @endif

                                {{-- Diskon Invoice Input (di bawah subtotal) --}}
                                <div class="summary-diskon-input">
                                    <label>💸 Diskon Invoice (Rp)</label>
                                    <input type="text" 
                                        wire:model.blur="totalDiskonInvoice" 
                                        wire:change="validateInvoiceDiscount"
                                        placeholder="0"
                                        x-data
                                        x-on:input="$el.value = $el.value.replace(/[^0-9]/g, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.')"
                                    >
                                    <div style="font-size: 10px; color: #9ca3af; margin-top: 2px;">max: Rp {{ number_format($this->getCartTotal() - $totalDiskonItem, 0, ',', '.') }}</div>
                                </div>
                                
                                <div class="summary-line total">
                                    <span>TOTAL</span>
                                    <span class="amount">Rp {{ number_format($this->getCartTotalAfterDiscount(), 0, ',', '.') }}</span>
                                </div>
                                
                                @if(!empty($statusPembayaran) && !empty($jumlahBayar) && (int)str_replace('.', '', $jumlahBayar) > 0)
                                    <div class="summary-line" style="margin-top: 8px; padding-top: 8px; border-top: 1px dashed #e5e7eb;">
                                        <span>Bayar</span>
                                        <span>Rp {{ number_format((int)str_replace('.', '', $jumlahBayar), 0, ',', '.') }}</span>
                                    </div>
                                    @if($statusPembayaran == \App\Enums\BahanMutasiFaktur\StatusPembayaranEnum::LUNAS->value && $this->getKembalian() > 0)
                                        <div class="summary-line kembalian">
                                            <span>💵 Kembalian</span>
                                            <span>Rp {{ number_format($this->getKembalian(), 0, ',', '.') }}</span>
                                        </div>
                                    @elseif($statusPembayaran == \App\Enums\BahanMutasiFaktur\StatusPembayaranEnum::TERM_OF_PAYMENT->value)
                                        <div class="summary-line sisa">
                                            <span>⏳ Sisa</span>
                                            <span>Rp {{ number_format(max(0, $this->getCartTotalAfterDiscount() - (int)str_replace('.', '', $jumlahBayar)), 0, ',', '.') }}</span>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>

                        <button type="submit" class="btn-bayar">✅ PROSES PEMBAYARAN</button>
                    </div>
                </form>
            </div>
        </div>
    @else
        <div class="kasir-grid no-kalkulasi">
            <x-filament::section>
                <x-slot name="heading">📋 Daftar Kalkulasi</x-slot>
                <x-slot name="description">Pilih kalkulasi untuk diproses menjadi transaksi</x-slot>
                {{ $this->table }}
            </x-filament::section>

            <x-filament::section>
                <div class="empty-box">
                    <div class="empty-icon">
                        <x-filament::icon icon="heroicon-o-shopping-cart" style="width: 40px; height: 40px; color: #9ca3af;"/>
                    </div>
                    <div class="empty-title">Kasir</div>
                    <div class="empty-desc">Pilih kalkulasi dari daftar di sebelah kiri untuk memulai transaksi</div>
                </div>
            </x-filament::section>
        </div>
    @endif
</x-filament-panels::page>
