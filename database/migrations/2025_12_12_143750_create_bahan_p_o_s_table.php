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
        Schema::create('bahan_p_o_s', function (Blueprint $table) {
            $table->id();
            $table->foreignId('po_id')->constrained('p_o_s')->cascadeOnDelete();
            $table->foreignId('bahan_id')->constrained('bahans')->cascadeOnDelete();
            $table->bigInteger('jumlah_terbesar')->default(0);
            $table->bigInteger('jumlah_terkecil')->default(0);
            $table->unsignedBigInteger('total_harga_po')->default(0);
            $table->unsignedBigInteger('harga_satuan_terbesar')->default(0);
            $table->unsignedBigInteger('harga_satuan_terkecil')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bahan_p_o_s');
    }
};
