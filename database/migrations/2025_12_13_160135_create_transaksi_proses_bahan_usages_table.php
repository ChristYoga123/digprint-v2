<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transaksi_proses_bahan_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_proses_id')->constrained('transaksi_proses')->cascadeOnDelete();
            $table->foreignId('bahan_id')->constrained('bahans')->cascadeOnDelete();
            $table->unsignedBigInteger('jumlah_digunakan'); // satuan terkecil
            $table->unsignedBigInteger('hpp')->default(0); // HPP dari FIFO calculation
            $table->timestamps();
            
            $table->index(['transaksi_proses_id']);
            $table->index(['bahan_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi_proses_bahan_usages');
    }
};
