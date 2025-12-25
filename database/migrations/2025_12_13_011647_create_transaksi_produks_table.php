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
        Schema::create('transaksi_produks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_id')->constrained('transaksis')->cascadeOnDelete();
            $table->foreignId('produk_id')->constrained('produks')->cascadeOnDelete();
            $table->string('judul_pesanan');
            $table->unsignedInteger('jumlah');
            $table->decimal('panjang', 10, 2)->nullable();
            $table->decimal('lebar', 10, 2)->nullable();
            $table->foreignId('design_id')->nullable()->constrained('produk_proses')->cascadeOnDelete(); // ID dari ProdukProses design yang dipilih
            $table->string('link_design')->nullable(); // link design jika customer sudah punya design sendiri
            $table->json('addons')->nullable();
            $table->unsignedBigInteger('total_harga_produk_sebelum_diskon');
            $table->unsignedBigInteger('total_diskon_produk')->default(0); // jika diskon per item
            $table->unsignedBigInteger('total_harga_produk_setelah_diskon'); // hasil dari total_harga_produk_sebelum_diskon - total_diskon_produk
            $table->longText('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksi_produks');
    }
};
