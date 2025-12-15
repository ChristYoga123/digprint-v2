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
        Schema::create('transaksi_kalkulasi_produks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_kalkulasi_id')->constrained('transaksi_kalkulasis')->cascadeOnDelete();
            $table->foreignId('produk_id')->constrained('produks')->cascadeOnDelete();
            $table->unsignedInteger('jumlah');
            $table->decimal('panjang', 10, 2)->nullable();
            $table->decimal('lebar', 10, 2)->nullable();
            $table->foreignId('design_id')->nullable()->constrained('produk_proses')->cascadeOnDelete(); // ID dari ProdukProses design yang dipilih
            $table->json('addons')->nullable();
            $table->longText('keterangan')->nullable();
            $table->unsignedBigInteger('total_harga_produk');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tranaksi_kalkulasi_produks');
    }
};
