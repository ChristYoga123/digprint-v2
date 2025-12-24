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
        Schema::create('transaksi_proses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_produk_id')->constrained('transaksi_produks')->cascadeOnDelete();
            $table->foreignId('produk_proses_id')->constrained('produk_proses')->cascadeOnDelete();
            $table->unsignedInteger('urutan');
            $table->string('status_proses'); // [Belum, Dalam Proses, Selesai]
            $table->boolean('apakah_perlu_sample_approval')->default(false);
            $table->string('status_sample_approval')->nullable(); // 1: Pending, 2: Approved, 3: Rejected
            $table->boolean('apakah_menggunakan_subjoin')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi_proses');
    }
};
