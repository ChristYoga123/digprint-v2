<?php

use App\Enums\Utils\TipeNotificationEnum;
use App\Models\Supplier;
use Illuminate\Support\Str;
use Filament\Notifications\Notification;

function generateKode($prefix = null)
{
    return $prefix ? $prefix . '-' . Str::random(6) : Str::random(3) . '-' . Str::random(6);
}

function formatRupiah($amount)
{
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function creatableState()
{
    return 'Jika data belum ada, bisa menambah data baru dengan klik (+)';
}

function customableState()
{
    return 'Data terisi otomatis tetapi bisa di-custom';
}

function getBankData()
{
    return collect(json_decode(file_get_contents(config_path('bank.json')), true))->pluck('name', 'name')->toArray();
}

function getSupplierPaymentMethods($supplier)
{
    $methods = ['Cash' => 'Cash'];
    
    if (!$supplier) {
        return $methods;
    }
    
    // Jika $supplier adalah ID, load model
    if (is_numeric($supplier)) {
        $supplier = Supplier::find($supplier);
    }
    
    if (!$supplier || !($supplier instanceof Supplier)) {
        return $methods;
    }
    
    // Tambahkan metode_pembayaran1 jika ada
    if (!empty($supplier->metode_pembayaran1)) {
        $methods[$supplier->metode_pembayaran1] = $supplier->metode_pembayaran1;
    }
    
    // Tambahkan metode_pembayaran2 jika ada
    if (!empty($supplier->metode_pembayaran2)) {
        $methods[$supplier->metode_pembayaran2] = $supplier->metode_pembayaran2;
    }
    
    return $methods;
}

function filamentNotification(TipeNotificationEnum $tipe, string $message)
{
    return Notification::make()
        ->title($tipe->getTitle())
        ->body($message)
        ->{$tipe->getMethod()}()
        ->send();
}