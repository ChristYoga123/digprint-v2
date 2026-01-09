<?php

namespace App\Http\Controllers;

use App\Models\Bahan;
use App\Models\StokOpname;
use Illuminate\Http\Request;

class StokOpnamePrintController extends Controller
{
    /**
     * Print blank form for stock opname (all items)
     */
    public function printForm(Request $request)
    {
        $stokOpnameId = $request->get('stok_opname_id');
        $stokOpname = null;
        $items = collect();
        
        if ($stokOpnameId) {
            // Print form for specific stok opname
            $stokOpname = StokOpname::with(['items.bahan.satuanTerkecil'])->findOrFail($stokOpnameId);
            $items = $stokOpname->items->map(function ($item) {
                return [
                    'kode' => $item->bahan->kode,
                    'nama' => $item->bahan->nama,
                    'satuan' => $item->bahan->satuanTerkecil->nama ?? '-',
                    'stok_sistem' => $item->stock_system,
                ];
            });
        } else {
            // Print blank form with all bahan
            $bahans = Bahan::with('satuanTerkecil')->orderBy('nama')->get();
            $items = $bahans->map(function ($bahan) {
                return [
                    'kode' => $bahan->kode,
                    'nama' => $bahan->nama,
                    'satuan' => $bahan->satuanTerkecil->nama ?? '-',
                    'stok_sistem' => $bahan->stok,
                ];
            });
        }
        
        return view('print.stok-opname-form', [
            'stokOpname' => $stokOpname,
            'items' => $items,
            'printDate' => now()->format('d/m/Y H:i'),
        ]);
    }
}
