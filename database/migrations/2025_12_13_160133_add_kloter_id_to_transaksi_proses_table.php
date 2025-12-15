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
        Schema::table('transaksi_proses', function (Blueprint $table) {
            $table->foreignId('kloter_id')->nullable()->after('produk_proses_id')->constrained('kloters')->cascadeOnDelete();
            $table->index('kloter_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksi_proses', function (Blueprint $table) {
            $table->dropForeign(['kloter_id']);
            $table->dropColumn('kloter_id');
        });
    }
};
