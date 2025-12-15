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
        Schema::create('p_o_s', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->string('status_kirim')->default('Ambil'); // [Ambil, Kirim]
            $table->date('tanggal_kirim')->nullable();
            $table->unsignedBigInteger('total_harga_po_keseluruhan')->default(0);
            $table->boolean('is_approved')->nullable();
            $table->date('tanggal_approved')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('p_o_s');
    }
};
