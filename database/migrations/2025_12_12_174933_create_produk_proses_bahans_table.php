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
        Schema::create('produk_proses_bahans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produk_proses_id')->constrained('produk_proses')->cascadeOnDelete();
            $table->foreignId('bahan_id')->constrained('bahans')->cascadeOnDelete();
            $table->unsignedBigInteger('jumlah')->default(0);
            $table->boolean('apakah_dipengaruhi_oleh_dimensi')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produk_proses_bahans');
    }
};
