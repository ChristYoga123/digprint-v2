<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\PrintNotaController;
use App\Http\Controllers\PrintSpkController;
use App\Http\Controllers\StokOpnamePrintController;
use App\Http\Controllers\StokOpnameExportController;
use App\Http\Controllers\AntrianController;
use Illuminate\Support\Facades\Route;

// Login Routes
Route::get('/login', [LoginController::class, 'index'])->name('login');
Route::post('/login', [LoginController::class, 'authenticate'])->name('login.authenticate');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::redirect('/', '/login');
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

// ==================== ANTRIAN ROUTES ====================
// Public routes (no auth required)
Route::prefix('antrian')->name('antrian.')->group(function () {
    Route::get('/', [AntrianController::class, 'ambilTiket'])->name('ambil-tiket');
    Route::post('/ambil', [AntrianController::class, 'prosesAmbilTiket'])->name('proses-ambil');
    Route::get('/tiket/{antrian}', [AntrianController::class, 'showTiket'])->name('tiket');
    Route::get('/display', [AntrianController::class, 'display'])->name('display');
    Route::get('/display-data', [AntrianController::class, 'getDisplayData'])->name('display-data');
});
