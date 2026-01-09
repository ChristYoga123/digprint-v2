<?php

use App\Http\Controllers\PrintNotaController;
use App\Http\Controllers\PrintSpkController;
use App\Http\Controllers\StokOpnamePrintController;
use App\Http\Controllers\StokOpnameExportController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Print SPK Route
Route::get('/print/spk', [PrintSpkController::class, 'print'])->name('print.spk');

// Print Nota Route (supports: thermal, a5, a4)
Route::get('/print/nota', [PrintNotaController::class, 'print'])->name('print.nota');

// Stok Opname Routes
Route::middleware('auth')->group(function () {
    // Print Stok Opname Form Route
    Route::get('/print/stok-opname', [StokOpnamePrintController::class, 'printForm'])
        ->name('stok-opname.print-form');
    
    // Export Stok Opname Routes
    Route::get('/export/stok-opname/{id}', [StokOpnameExportController::class, 'export'])
        ->name('stok-opname.export');
    Route::get('/export/stok-opname', [StokOpnameExportController::class, 'exportAll'])
        ->name('stok-opname.export-all');
});

