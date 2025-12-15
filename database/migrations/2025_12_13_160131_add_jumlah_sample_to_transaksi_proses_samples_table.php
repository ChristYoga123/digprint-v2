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
        Schema::table('transaksi_proses_samples', function (Blueprint $table) {
            $table->unsignedInteger('jumlah_sample')->default(0)->after('operator_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksi_proses_samples', function (Blueprint $table) {
            $table->dropColumn('jumlah_sample');
        });
    }
};
