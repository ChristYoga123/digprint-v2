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
        Schema::create('wallet_mutasis', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->foreignId('wallet_id')->constrained('wallets')->onDelete('cascade');
            $table->enum('tipe', ['masuk', 'keluar', 'transfer']); // masuk = income, keluar = expense, transfer = move between wallets
            $table->decimal('nominal', 20, 2);
            $table->decimal('saldo_sebelum', 20, 2);
            $table->decimal('saldo_sesudah', 20, 2);
            
            // Polymorphic relation untuk sumber mutasi (PencatatanKeuangan, BahanMutasiFaktur, dll)
            $table->nullableMorphs('sumber');
            
            // Untuk transfer antar wallet
            $table->foreignId('wallet_tujuan_id')->nullable()->constrained('wallets')->onDelete('set null');
            $table->foreignId('related_mutasi_id')->nullable()->constrained('wallet_mutasis')->onDelete('set null'); // Link mutasi transfer (keluar-masuk)
            
            // Referensi ke transaksi (opsional, untuk kemudahan query)
            $table->foreignId('transaksi_id')->nullable()->constrained('transaksis')->onDelete('set null');
            
            $table->text('keterangan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['wallet_id', 'created_at']);
            $table->index(['transaksi_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_mutasis');
    }
};
