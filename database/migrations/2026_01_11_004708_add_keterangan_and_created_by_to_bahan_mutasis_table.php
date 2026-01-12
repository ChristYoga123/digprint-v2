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
            $table->text('keterangan')->nullable()->after('apakah_include_ppn');
            $table->foreignId('created_by')->nullable()->after('keterangan')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bahan_mutasis', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['keterangan', 'created_by']);
        });
    }
};
