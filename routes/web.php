<?php

use App\Http\Controllers\PrintNotaController;
use App\Http\Controllers\PrintSpkController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Print SPK Route
Route::get('/print/spk', [PrintSpkController::class, 'print'])->name('print.spk');

// Print Nota Route (supports: thermal, a5, a4)
Route::get('/print/nota', [PrintNotaController::class, 'print'])->name('print.nota');
