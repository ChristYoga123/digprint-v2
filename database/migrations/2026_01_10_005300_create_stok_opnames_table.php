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
        Schema::create('stok_opnames', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama')->nullable(); // Nama/deskripsi stok opname (e.g., "Stok Opname Januari 2026")
            $table->text('catatan')->nullable();
            $table->string('status')->default('draft'); // draft, submitted, approved, revised
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        Schema::create('stok_opname_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stok_opname_id')->constrained('stok_opnames')->cascadeOnDelete();
            $table->foreignId('bahan_id')->constrained('bahans')->cascadeOnDelete();
            $table->decimal('stock_system', 20, 4)->default(0); // Stok sistem saat opname dibuat
            $table->decimal('stock_physical', 20, 4)->nullable(); // Stok fisik yang diinput manual
            $table->decimal('difference', 20, 4)->nullable(); // Selisih (physical - system)
            $table->string('status')->default('pending'); // pending, approved, revised
            $table->text('catatan')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });

        // History table untuk mencatat perubahan stok dari stok opname
        Schema::create('stok_opname_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stok_opname_item_id')->constrained('stok_opname_items')->cascadeOnDelete();
            $table->foreignId('bahan_id')->constrained('bahans')->cascadeOnDelete();
            $table->decimal('stock_before', 20, 4);
            $table->decimal('stock_after', 20, 4);
            $table->decimal('adjustment', 20, 4); // Penyesuaian (+ atau -)
            $table->string('adjustment_type'); // increase, decrease
            $table->foreignId('adjusted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('adjusted_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stok_opname_histories');
        Schema::dropIfExists('stok_opname_items');
        Schema::dropIfExists('stok_opnames');
    }
};
