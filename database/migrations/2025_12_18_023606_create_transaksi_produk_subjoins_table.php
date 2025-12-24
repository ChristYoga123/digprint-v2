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
        Schema::create('transaksi_produk_subjoins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_produk_id')->constrained('transaksi_produks')->cascadeOnDelete();
            $table->foreignId('produk_proses_id')->constrained('produk_proses')->cascadeOnDelete();
            $table->string('nama_vendor')->nullable();
            $table->unsignedBigInteger('harga_vendor')->nullable();
            $table->boolean('apakah_subjoin_diapprove')->default(false);
            $table->boolean('apakah_subjoin_selesai')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi_produk_subjoins');
    }
};
