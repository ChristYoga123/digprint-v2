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
        Schema::table('stok_opnames', function (Blueprint $table) {
            $table->date('tanggal_opname')->nullable()->after('kode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stok_opnames', function (Blueprint $table) {
            $table->dropColumn('tanggal_opname');
        });
    }
};
