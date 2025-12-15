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
            // Drop kolom yang tidak dipakai karena sample approval ada di tabel terpisah
            $table->dropColumn(['apakah_perlu_sample_approval', 'status_sample_approval']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksi_proses', function (Blueprint $table) {
            $table->boolean('apakah_perlu_sample_approval')->default(false)->after('status_proses');
            $table->string('status_sample_approval')->nullable()->after('apakah_perlu_sample_approval');
        });
    }
};
