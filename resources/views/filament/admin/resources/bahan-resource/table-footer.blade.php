@php
    use App\Models\BahanStokBatch;
    
    // Hitung total nilai stok dari semua batch yang tersedia (FIFO valuation)
    $totalNilaiStok = BahanStokBatch::where('jumlah_tersedia', '>', 0)
        ->selectRaw('SUM(jumlah_tersedia * harga_satuan_terkecil) as total')
        ->value('total') ?? 0;
    
    // Hitung jumlah total bahan
    $totalBahan = \App\Models\Bahan::count();
    
    // Hitung jumlah bahan dengan stok habis
    $bahanHabis = \App\Models\Bahan::whereRaw('(
        SELECT COALESCE(SUM(jumlah_tersedia), 0)
        FROM bahan_stok_batches
        WHERE bahan_stok_batches.bahan_id = bahans.id
    ) = 0')->count();
    
    // Hitung jumlah bahan dengan stok kurang dari minimal
    $bahanKurang = \App\Models\Bahan::whereRaw('(
        SELECT COALESCE(SUM(jumlah_tersedia), 0)
        FROM bahan_stok_batches
        WHERE bahan_stok_batches.bahan_id = bahans.id
    ) > 0')
    ->whereRaw('(
        SELECT COALESCE(SUM(jumlah_tersedia), 0)
        FROM bahan_stok_batches
        WHERE bahan_stok_batches.bahan_id = bahans.id
    ) < bahans.stok_minimal')->count();
@endphp

<div class="px-4 py-3 bg-gray-50 dark:bg-gray-800/50 rounded-b-xl border-t border-gray-200 dark:border-gray-700">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-6">
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-500 dark:text-gray-400">Total Bahan:</span>
                <span class="text-sm font-bold text-gray-900 dark:text-white">{{ number_format($totalBahan) }}</span>
            </div>
            
            @if($bahanHabis > 0)
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-2 h-2 rounded-full bg-danger-500"></span>
                <span class="text-sm text-danger-600 dark:text-danger-400">{{ $bahanHabis }} habis</span>
            </div>
            @endif
            
            @if($bahanKurang > 0)
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center justify-center w-2 h-2 rounded-full bg-warning-500"></span>
                <span class="text-sm text-warning-600 dark:text-warning-400">{{ $bahanKurang }} kurang</span>
            </div>
            @endif
        </div>
        
        <div class="flex items-center gap-2 px-4 py-2 bg-primary-50 dark:bg-primary-900/30 rounded-lg border border-primary-200 dark:border-primary-700">
            <svg class="w-5 h-5 text-primary-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
            </svg>
            <span class="text-sm font-medium text-primary-700 dark:text-primary-300">Total Nilai Inventory (FIFO):</span>
            <span class="text-lg font-bold text-primary-600 dark:text-primary-400">Rp {{ number_format($totalNilaiStok, 0, ',', '.') }}</span>
        </div>
    </div>
</div>
