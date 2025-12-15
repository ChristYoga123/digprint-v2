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
        Schema::create('transaksi_proses_samples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaksi_proses_id')->constrained('transaksi_proses')->cascadeOnDelete();
            $table->foreignId('operator_id')->constrained('users')->cascadeOnDelete();
            $table->text('catatan_operator')->nullable();
            $table->string('status'); // enum: Menunggu, Disetujui, Ditolak
            $table->text('catatan_customer')->nullable();
            $table->foreignId('responded_by')->nullable()->constrained('users')->cascadeOnDelete(); // Deskprint user
            $table->dateTime('responded_at')->nullable();
            $table->timestamps();
        });

        // Tambahkan FK constraint ke bahan_mutasis setelah tabel ini dibuat
        Schema::table('bahan_mutasis', function (Blueprint $table) {
            $table->foreign('transaksi_proses_sample_id')
                ->references('id')
                ->on('transaksi_proses_samples')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bahan_mutasis', function (Blueprint $table) {
            $table->dropForeign(['transaksi_proses_sample_id']);
        });

        Schema::dropIfExists('transaksi_proses_samples');
    }
};
