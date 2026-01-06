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
        // bahan_stok_batches - jumlah_masuk, jumlah_tersedia
        Schema::table('bahan_stok_batches', function (Blueprint $table) {
            $table->decimal('jumlah_masuk', 15, 2)->default(0)->change();
            $table->decimal('jumlah_tersedia', 15, 2)->default(0)->change();
        });

        // bahan_mutasi_penggunaan_batches - jumlah_digunakan
        Schema::table('bahan_mutasi_penggunaan_batches', function (Blueprint $table) {
            $table->decimal('jumlah_digunakan', 15, 2)->change();
        });

        // transaksi_proses_bahan_usages - jumlah_digunakan
        Schema::table('transaksi_proses_bahan_usages', function (Blueprint $table) {
            $table->decimal('jumlah_digunakan', 15, 2)->change();
        });

        // produk_proses_bahans - jumlah
        Schema::table('produk_proses_bahans', function (Blueprint $table) {
            $table->decimal('jumlah', 15, 2)->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bahan_stok_batches', function (Blueprint $table) {
            $table->unsignedBigInteger('jumlah_masuk')->default(0)->change();
            $table->unsignedBigInteger('jumlah_tersedia')->default(0)->change();
        });

        Schema::table('bahan_mutasi_penggunaan_batches', function (Blueprint $table) {
            $table->unsignedBigInteger('jumlah_digunakan')->change();
        });

        Schema::table('transaksi_proses_bahan_usages', function (Blueprint $table) {
            $table->unsignedBigInteger('jumlah_digunakan')->change();
        });

        Schema::table('produk_proses_bahans', function (Blueprint $table) {
            $table->unsignedBigInteger('jumlah')->default(0)->change();
        });
    }
};
