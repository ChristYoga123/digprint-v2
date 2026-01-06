<div class="space-y-6">
    {{-- Info Transaksi --}}
    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
        <h4 class="text-sm font-semibold text-gray-500 dark:text-gray-400 mb-3">Informasi Transaksi</h4>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <span class="text-gray-500 dark:text-gray-400">Customer:</span>
                <p class="font-medium text-gray-900 dark:text-white mt-1">{{ $transaksi->customer?->nama ?? '-' }}</p>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">Jenis Diskon:</span>
                <p class="font-medium text-gray-900 dark:text-white mt-1">
                    @if ($transaksi->jenis_diskon)
                        <span
                            class="inline-flex items-center px-2.5 py-1 text-xs font-medium rounded-full 
                            {{ $transaksi->jenis_diskon->value === 'Per Item' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' }}">
                            {{ $transaksi->jenis_diskon->getLabel() }}
                        </span>
                    @else
                        -
                    @endif
                </p>
            </div>
        </div>
    </div>

    {{-- Daftar Produk --}}
    <div>
        <h4 class="text-sm font-semibold text-gray-500 dark:text-gray-400 mb-3">Daftar Produk</h4>
        <div class="border dark:border-gray-700 rounded-lg overflow-x-auto">
            <table class="w-full table-auto divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th
                            class="px-4 py-3 text-left text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                            Produk
                        </th>
                        <th
                            class="px-4 py-3 text-center text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider w-20">
                            Qty
                        </th>
                        <th
                            class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider w-32">
                            Harga
                        </th>
                        @if ($transaksi->jenis_diskon?->value === 'Per Item')
                            <th
                                class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider w-32">
                                Diskon
                            </th>
                        @endif
                        <th
                            class="px-4 py-3 text-right text-xs font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider w-32">
                            Subtotal
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($transaksi->transaksiProduks as $produk)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                <div class="font-medium">{{ $produk->produk?->nama ?? '-' }}</div>
                                @if ($produk->panjang && $produk->lebar)
                                    <div class="text-gray-500 dark:text-gray-400 text-xs mt-1">
                                        📏 {{ $produk->panjang }} x {{ $produk->lebar }} m
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white text-center font-medium">
                                {{ $produk->jumlah }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white text-right">
                                Rp {{ number_format($produk->total_harga_produk ?? 0, 0, ',', '.') }}
                            </td>
                            @if ($transaksi->jenis_diskon?->value === 'Per Item')
                                <td class="px-4 py-3 text-sm text-red-600 dark:text-red-400 text-right font-semibold">
                                    - Rp {{ number_format($produk->total_diskon_produk ?? 0, 0, ',', '.') }}
                                </td>
                            @endif
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white text-right font-semibold">
                                Rp
                                {{ number_format($produk->total_harga_produk_setelah_diskon ?? ($produk->total_harga_produk ?? 0), 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Summary --}}
    <div
        class="bg-gradient-to-br from-gray-50 to-gray-100 dark:from-gray-900 dark:to-gray-800 rounded-lg p-5 border border-gray-200 dark:border-gray-700">
        <div class="space-y-3">
            <div class="flex justify-between items-center text-sm">
                <span class="text-gray-600 dark:text-gray-400">Total Harga:</span>
                <span class="text-gray-900 dark:text-white font-medium">
                    Rp {{ number_format($transaksi->total_harga_transaksi, 0, ',', '.') }}
                </span>
            </div>
            <div class="flex justify-between items-center text-sm">
                <span class="text-red-600 dark:text-red-400 font-medium">
                    Total Diskon ({{ $transaksi->jenis_diskon?->getLabel() ?? '-' }}):
                </span>
                <span class="text-red-600 dark:text-red-400 font-semibold">
                    - Rp {{ number_format($transaksi->total_diskon_transaksi, 0, ',', '.') }}
                </span>
            </div>
            <hr class="border-gray-300 dark:border-gray-600">
            <div class="flex justify-between items-center pt-2">
                <span class="text-gray-900 dark:text-white text-lg font-bold">Total Bayar:</span>
                <span class="text-green-600 dark:text-green-400 text-xl font-bold">
                    Rp {{ number_format($transaksi->total_harga_transaksi_setelah_diskon, 0, ',', '.') }}
                </span>
            </div>
        </div>
    </div>
</div>
