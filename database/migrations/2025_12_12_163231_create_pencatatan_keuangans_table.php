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
        Schema::create('pencatatan_keuangans', function (Blueprint $table) {
            $table->id();
            $table->string('pencatatan_keuangan_type'); // [Transaksi, Bahan Mutasi Faktur]
            $table->unsignedBigInteger('pencatatan_keuangan_id');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('jumlah_bayar')->default(0); // jumlah yang dibayar baik lunas atau term of payment
            $table->string('metode_pembayaran')->nullable(); // [dari getBankData()]
            $table->string('keterangan')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->cascadeOnDelete(); // jika ada pencatatan keuangan
            $table->dateTime('approved_at')->nullable(); // jika ada pencatatan keuangan
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pencatatan_keuangans');
    }
};
