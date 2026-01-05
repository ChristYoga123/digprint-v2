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
        Schema::table('produk_proses_bahans', function (Blueprint $table) {
            // Tambahkan produk_id untuk produk yang langsung selesai
            $table->foreignId('produk_id')->nullable()->after('id')->constrained('produks')->cascadeOnDelete();
            
            // Ubah produk_proses_id menjadi nullable
            $table->foreignId('produk_proses_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('produk_proses_bahans', function (Blueprint $table) {
            $table->dropForeign(['produk_id']);
            $table->dropColumn('produk_id');
            
            // Kembalikan produk_proses_id menjadi not null
            $table->foreignId('produk_proses_id')->nullable(false)->change();
        });
    }
};
