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
        Schema::create('bahan_stok_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bahan_id')->constrained('bahans')->cascadeOnDelete();
            $table->foreignId('bahan_mutasi_id')->constrained('bahan_mutasis')->cascadeOnDelete();
            $table->unsignedBigInteger('jumlah_masuk')->default(0); // Jumlah yang masuk dalam batch ini (satuan terkecil)
            $table->unsignedBigInteger('jumlah_tersedia')->default(0); // Jumlah yang masih tersedia (satuan terkecil)
            $table->unsignedBigInteger('harga_satuan_terkecil')->default(0); // Harga per satuan terkecil
            $table->unsignedBigInteger('harga_satuan_terbesar')->default(0); // Harga per satuan terbesar
            $table->dateTime('tanggal_masuk'); // Tanggal batch masuk
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bahan_stok_batches');
    }
};
