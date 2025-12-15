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
        Schema::create('bahans', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama')->unique();
            $table->foreignId('satuan_terbesar_id')->constrained('satuans')->cascadeOnDelete();
            $table->foreignId('satuan_terkecil_id')->constrained('satuans')->cascadeOnDelete();
            // $table->bigInteger('stok')->default(0); diambil secara aggregat
            $table->bigInteger('stok_minimal')->default(0);
            $table->longText('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bahans');
    }
};
