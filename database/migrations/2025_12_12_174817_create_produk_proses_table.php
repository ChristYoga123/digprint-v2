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
        Schema::create('produk_proses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produk_id')->constrained('produks')->cascadeOnDelete();
            $table->foreignId('produk_proses_kategori_id')->constrained('produk_proses_kategoris')->cascadeOnDelete();
            $table->string('nama');
            $table->unsignedBigInteger('harga')->nullable(); // khusus addon/finishing
            $table->foreignId('mesin_id')->nullable()->constrained('mesins')->cascadeOnDelete(); // jika kategorinya produksi
            $table->integer('urutan')->nullable(); // terisi jika kategorinya produksi. Addon/Finishing tidak perlu urutan
            $table->boolean('apakah_mengurangi_bahan')->default(false); // jika finishing bisa true bisa false
            $table->boolean('apakah_perlu_sample_approval')->default(false); // apakah proses ini perlu sample approval
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produk_proses');
    }
};
