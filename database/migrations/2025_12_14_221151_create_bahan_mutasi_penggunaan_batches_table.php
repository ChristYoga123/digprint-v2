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
        Schema::create('bahan_mutasi_penggunaan_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bahan_mutasi_id')->constrained('bahan_mutasis')->cascadeOnDelete();
            $table->foreignId('bahan_stok_batch_id')->constrained('bahan_stok_batches')->cascadeOnDelete();
            $table->unsignedBigInteger('jumlah_digunakan');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bahan_mutasi_penggunaan_batches');
    }
};
