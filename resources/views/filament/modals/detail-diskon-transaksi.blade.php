<div class="space-y-4">
    {{-- Info Transaksi --}}
    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
        <h4 class="text-sm font-semibold text-gray-500 dark:text-gray-400 mb-2">Informasi Transaksi</h4>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <span class="text-gray-500 dark:text-gray-400">Customer:</span>
                <p class="font-medium text-gray-900 dark:text-white">{{ $transaksi->customer?->nama ?? '-' }}</p>
            </div>
            <div>
                <span class="text-gray-500 dark:text-gray-400">Jenis Diskon:</span>
                <p class="font-medium text-gray-900 dark:text-white">
                    @if ($transaksi->jenis_diskon)
                        <span
                            class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-full 
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
        <h4 class="text-sm font-semibold text-gray-500 dark:text-gray-400 mb-2">Daftar Produk</h4>
        <div class="border dark:border-gray-700 rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                            Produk</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                            Qty</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                            Harga</th>
                        @if ($transaksi->jenis_diskon?->value === 'Per Item')
                            <th
                                class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                Diskon</th>
                        @endif
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                            Subtotal</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach ($transaksi->transaksiProduks as $produk)
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                                {{ $produk->produk?->nama ?? '-' }}
                                @if ($produk->panjang && $produk->lebar)
                                    <span class="text-gray-500 text-xs block">{{ $produk->panjang }} x
                                        {{ $produk->lebar }} m</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-white text-right">{{ $produk->jumlah }}
                            </td>
                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-white text-right">Rp
                                {{ number_format($produk->total_harga_produk ?? 0, 0, ',', '.') }}</td>
                            @if ($transaksi->jenis_diskon?->value === 'Per Item')
                                <td class="px-4 py-2 text-sm text-red-600 dark:text-red-400 text-right font-medium">- Rp
                                    {{ number_format($produk->diskon_produk ?? 0, 0, ',', '.') }}</td>
                            @endif
                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-white text-right font-medium">Rp
                                {{ number_format($produk->total_harga_produk_setelah_diskon ?? ($produk->total_harga_produk ?? 0), 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Summary --}}
    <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
        <div class="space-y-2 text-sm">
            <div class="flex justify-between">
                <span class="text-gray-500 dark:text-gray-400">Total Harga:</span>
                <span class="text-gray-900 dark:text-white">Rp
                    {{ number_format($transaksi->total_harga_transaksi, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between text-red-600 dark:text-red-400 font-medium">
                <span>Total Diskon ({{ $transaksi->jenis_diskon?->getLabel() ?? '-' }}):</span>
                <span>- Rp {{ number_format($transaksi->total_diskon_transaksi, 0, ',', '.') }}</span>
            </div>
            <hr class="border-gray-200 dark:border-gray-700">
            <div class="flex justify-between text-lg font-bold">
                <span class="text-gray-900 dark:text-white">Total Bayar:</span>
                <span class="text-green-600 dark:text-green-400">Rp
                    {{ number_format($transaksi->total_harga_transaksi_setelah_diskon, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>
</div>
