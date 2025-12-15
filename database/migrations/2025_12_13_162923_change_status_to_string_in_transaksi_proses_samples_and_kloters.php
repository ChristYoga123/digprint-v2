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
        // Ubah status di transaksi_proses_samples dari unsignedInteger ke string
        Schema::table('transaksi_proses_samples', function (Blueprint $table) {
            $table->string('status')->change();
        });

        // Ubah status di kloters dari unsignedInteger ke string
        Schema::table('kloters', function (Blueprint $table) {
            $table->string('status')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Kembalikan ke unsignedInteger (tapi data akan hilang jika ada string)
        Schema::table('transaksi_proses_samples', function (Blueprint $table) {
            $table->unsignedInteger('status')->change();
        });

        Schema::table('kloters', function (Blueprint $table) {
            $table->unsignedInteger('status')->change();
        });
    }
};
