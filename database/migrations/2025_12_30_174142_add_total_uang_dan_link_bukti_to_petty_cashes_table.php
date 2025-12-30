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
        Schema::table('petty_cashes', function (Blueprint $table) {
            // Link bukti transfer/drive collection (untuk pembayaran transfer)
            $table->string('link_bukti_transfer')->nullable()->after('uang_tutup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('petty_cashes', function (Blueprint $table) {
            $table->dropColumn(['link_bukti_transfer']);
        });
    }
};
