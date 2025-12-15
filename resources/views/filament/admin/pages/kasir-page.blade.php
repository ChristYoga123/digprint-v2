<x-filament-panels::page>
    <div class="grid grid-cols-12 gap-4">
        {{-- Left Side: Kalkulasi Table --}}
        <div class="col-span-7">
            <x-filament::section>
                <x-slot name="heading">
                    Daftar Kalkulasi
                </x-slot>
                <x-slot name="description">
                    Pilih kalkulasi untuk diproses menjadi transaksi
                </x-slot>
                
                {{ $this->table }}
            </x-filament::section>
        </div>

        {{-- Right Side: Cart / Invoice --}}
        <div class="col-span-5">
            @if($selectedKalkulasi)
                {{-- Customer Info --}}
                <x-filament::section class="mb-4">
                    <x-slot name="heading">
                        Informasi Customer
                    </x-slot>
                    
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="font-medium">Kode Kalkulasi:</span>
                            <span>{{ $selectedKalkulasi['kode'] }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-medium">Customer:</span>
                            <span>{{ $selectedKalkulasi['customer_nama'] }}</span>
                        </div>
                    </div>
                </x-filament::section>

                {{-- Cart Items --}}
                <x-filament::section class="mb-4">
                    <x-slot name="heading">
                        Item Pesanan
                    </x-slot>
                    <x-slot name="headerEnd">
                        <x-filament::button 
                            color="danger" 
                            size="xs"
                            wire:click="clearCart"
                        >
                            Hapus Semua
                        </x-filament::button>
                    </x-slot>
                    
                    <div class="space-y-3">
                        @foreach($cartItems as $index => $item)
                            <div class="border rounded-lg p-3 bg-gray-50 dark:bg-gray-800">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="flex-1">
                                        <h4 class="font-semibold">{{ $item['produk_nama'] }}</h4>
                                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                            <div>Jumlah: {{ $item['jumlah'] }}</div>
                                            @if($item['panjang'] && $item['lebar'])
                                                <div>Dimensi: {{ $item['panjang'] }} × {{ $item['lebar'] }} cm</div>
                                            @endif
                                            @if(!empty($item['addons']))
                                                <div>Addon: {{ count($item['addons']) }} item</div>
                                            @endif
                                            @if(!empty($item['keterangan']))
                                                <div class="mt-2 p-2 bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 rounded">
                                                    <div class="text-xs font-semibold text-yellow-800 dark:text-yellow-200">Keterangan:</div>
                                                    <div class="text-xs text-yellow-700 dark:text-yellow-300 whitespace-pre-wrap">{{ $item['keterangan'] }}</div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-bold text-primary-600">
                                            {{ 'Rp ' . number_format($item['total_harga_produk'], 0, ',', '.') }}
                                        </div>
                                        <x-filament::button
                                            color="danger"
                                            size="xs"
                                            wire:click="removeFromCart({{ $index }})"
                                            class="mt-2"
                                        >
                                            Hapus
                                        </x-filament::button>
                                    </div>
                                </div>

                                {{-- Item Discount Input --}}
                                <div class="mt-2 pt-2 border-t">
                                    <x-filament::input.wrapper>
                                        <x-filament::input
                                            type="number"
                                            wire:model.blur="itemDiscounts.{{ $index }}"
                                            placeholder="Diskon untuk item ini (opsional)"
                                            min="0"
                                        />
                                    </x-filament::input.wrapper>
                                    <div class="text-xs text-gray-500 mt-1">
                                        Diskon: Rp {{ number_format((int)($itemDiscounts[$index] ?? 0), 0, ',', '.') }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </x-filament::section>

                {{-- Checkout Form --}}
                <form wire:submit="checkout">
                    {{ $this->checkoutForm }}

                    {{-- Summary --}}
                    <x-filament::section class="mb-4 mt-4">
                        <x-slot name="heading">
                            Ringkasan
                        </x-slot>
                        
                        <div class="space-y-2">
                            <div class="flex justify-between text-sm">
                                <span>Subtotal:</span>
                                <span>{{ 'Rp ' . number_format($this->getCartTotal(), 0, ',', '.') }}</span>
                            </div>
                            
                            @php
                                $totalDiskonItem = (int)array_sum(array_map('intval', $itemDiscounts ?? []));
                                $totalDiskonInvoice = (int)($totalDiskonInvoice ?? 0);
                                $totalDiskon = $totalDiskonItem + $totalDiskonInvoice;
                            @endphp
                            @if($totalDiskon > 0)
                                @if($totalDiskonItem > 0)
                                    <div class="flex justify-between text-sm text-red-600">
                                        <span>Total Diskon (Per Item):</span>
                                        <span>- Rp {{ number_format($totalDiskonItem, 0, ',', '.') }}</span>
                                    </div>
                                @endif
                                @if($totalDiskonInvoice > 0)
                                    <div class="flex justify-between text-sm text-red-600">
                                        <span>Total Diskon (Invoice):</span>
                                        <span>- Rp {{ number_format($totalDiskonInvoice, 0, ',', '.') }}</span>
                                    </div>
                                @endif
                                @if($totalDiskonItem > 0 && $totalDiskonInvoice > 0)
                                    <div class="flex justify-between text-sm text-red-700 font-semibold">
                                        <span>Total Diskon Keseluruhan:</span>
                                        <span>- Rp {{ number_format($totalDiskon, 0, ',', '.') }}</span>
                                    </div>
                                @endif
                            @endif
                            
                            <div class="flex justify-between text-lg font-bold pt-2 border-t">
                                <span>TOTAL:</span>
                                <span class="text-primary-600">
                                    {{ 'Rp ' . number_format($this->getCartTotalAfterDiscount(), 0, ',', '.') }}
                                </span>
                            </div>
                            
                            @if(!empty($statusPembayaran) && !empty($jumlahBayar))
                                <div class="flex justify-between text-sm pt-2 border-t">
                                    <span>Jumlah Bayar:</span>
                                    <span>{{ 'Rp ' . number_format((int)($jumlahBayar ?? 0), 0, ',', '.') }}</span>
                                </div>
                                @if($statusPembayaran == \App\Enums\BahanMutasiFaktur\StatusPembayaranEnum::LUNAS->value && $this->getKembalian() > 0)
                                    <div class="flex justify-between text-sm text-green-600 font-semibold">
                                        <span>Kembalian:</span>
                                        <span>{{ 'Rp ' . number_format($this->getKembalian(), 0, ',', '.') }}</span>
                                    </div>
                                @endif
                                @if($statusPembayaran == \App\Enums\BahanMutasiFaktur\StatusPembayaranEnum::TERM_OF_PAYMENT->value)
                                    <div class="flex justify-between text-sm text-orange-600">
                                        <span>Sisa Tagihan:</span>
                                        <span>{{ 'Rp ' . number_format(max(0, $this->getCartTotalAfterDiscount() - (int)($jumlahBayar ?? 0)), 0, ',', '.') }}</span>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </x-filament::section>

                    {{-- Checkout Button --}}
                    <x-filament::button
                        type="submit"
                        size="xl"
                        class="w-full"
                    >
                        Proses Pembayaran
                    </x-filament::button>
                </form>
            @else
                {{-- Empty State --}}
                <x-filament::section>
                    <div class="text-center py-12">
                        <x-filament::icon
                            icon="heroicon-o-shopping-cart"
                            class="mx-auto h-12 w-12 text-gray-400"
                        />
                        <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-100">
                            Belum ada kalkulasi dipilih
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Pilih kalkulasi dari tabel di sebelah kiri untuk memulai transaksi.
                        </p>
                    </div>
                </x-filament::section>
            @endif
        </div>
    </div>
</x-filament-panels::page>
