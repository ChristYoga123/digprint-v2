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
        Schema::table('bahan_mutasis', function (Blueprint $table) {
            // Change jumlah_mutasi from unsignedBigInteger to decimal(15, 2)
            $table->decimal('jumlah_mutasi', 15, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bahan_mutasis', function (Blueprint $table) {
            // Revert back to unsignedBigInteger
            $table->unsignedBigInteger('jumlah_mutasi')->nullable()->change();
        });
    }
};
