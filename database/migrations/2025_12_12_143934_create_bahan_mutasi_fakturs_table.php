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
        Schema::create('bahan_mutasi_fakturs', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->foreignId('po_id')->nullable()->constrained('p_o_s')->cascadeOnDelete(); // jika mutasi masuk dan dari PO
            $table->unsignedBigInteger('total_harga')->nullable();
            $table->unsignedBigInteger('total_diskon')->nullable();
            $table->unsignedBigInteger('total_harga_setelah_diskon')->nullable();
            $table->string('status_pembayaran'); // 1: Lunas 2: Term of Payment
            $table->date('tanggal_jatuh_tempo')->nullable(); // jika term of payment
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bahan_mutasi_fakturs');
    }
};
