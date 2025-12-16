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
        Schema::table('transaksi_kalkulasi_produks', function (Blueprint $table) {
            $table->string('link_design')->nullable()->after('design_id');
        });
        
        Schema::table('transaksi_produks', function (Blueprint $table) {
            $table->string('link_design')->nullable()->after('design_id');
        });
        
        Schema::table('transaksis', function (Blueprint $table) {
            $table->string('link_design')->nullable()->after('design');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaksi_kalkulasi_produks', function (Blueprint $table) {
            $table->dropColumn('link_design');
        });
        
        Schema::table('transaksi_produks', function (Blueprint $table) {
            $table->dropColumn('link_design');
        });
        
        Schema::table('transaksis', function (Blueprint $table) {
            $table->dropColumn('link_design');
        });
    }
};
