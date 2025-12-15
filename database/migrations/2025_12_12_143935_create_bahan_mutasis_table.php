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
        Schema::create('bahan_mutasis', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('tipe'); // 1: Masuk, 2: Keluar
            $table->foreignId('bahan_mutasi_faktur_id')->nullable()->constrained('bahan_mutasi_fakturs')->cascadeOnDelete();
            $table->foreignId('bahan_id')->constrained('bahans')->cascadeOnDelete();
            $table->unsignedBigInteger('transaksi_proses_sample_id')->nullable(); // FK akan dibuat setelah tabel transaksi_proses_samples ada
            $table->unsignedBigInteger('jumlah_satuan_terbesar')->nullable();
            $table->unsignedBigInteger('jumlah_satuan_terkecil')->nullable();
            $table->unsignedBigInteger('jumlah_mutasi')->nullable();
            $table->unsignedBigInteger('total_harga_mutasi')->nullable();
            $table->unsignedBigInteger('harga_satuan_terbesar')->nullable();
            $table->unsignedBigInteger('harga_satuan_terkecil')->nullable();
            $table->boolean('apakah_include_ppn')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bahan_mutasis');
    }
};
