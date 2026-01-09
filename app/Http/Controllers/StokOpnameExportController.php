<?php

namespace App\Http\Controllers;

use App\Models\StokOpname;
use App\Exports\StokOpnameExport;
use App\Exports\StokOpnameSummaryExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class StokOpnameExportController extends Controller
{
    /**
     * Export single stok opname detail
     */
    public function export(Request $request, $id)
    {
        $stokOpname = StokOpname::findOrFail($id);
        
        $filename = 'Stok_Opname_' . $stokOpname->kode . '_' . now()->format('Y-m-d') . '.xlsx';
        
        return Excel::download(new StokOpnameExport($stokOpname), $filename);
    }

    /**
     * Export all stok opname summary
     */
    public function exportAll()
    {
        $filename = 'Laporan_Stok_Opname_' . now()->format('Y-m-d') . '.xlsx';
        
        return Excel::download(new StokOpnameSummaryExport(), $filename);
    }
}
