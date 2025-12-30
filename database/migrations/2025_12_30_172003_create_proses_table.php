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
        Schema::create('proses', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->foreignId('produk_proses_kategori_id')->constrained('produk_proses_kategoris')->cascadeOnDelete();
            $table->unsignedBigInteger('harga_default')->nullable(); // Harga default, bisa null jika tidak ada
            $table->timestamps();
            
            // Unique constraint: nama + kategori harus unik
            $table->unique(['nama', 'produk_proses_kategori_id']);
        });
        
        // Tambah kolom proses_id ke produk_proses sebagai foreign key (nullable untuk backward compatibility)
        Schema::table('produk_proses', function (Blueprint $table) {
            $table->foreignId('proses_id')->nullable()->after('id')->constrained('proses')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('produk_proses', function (Blueprint $table) {
            $table->dropForeign(['proses_id']);
            $table->dropColumn('proses_id');
        });
        
        Schema::dropIfExists('proses');
    }
};
