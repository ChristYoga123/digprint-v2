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
        Schema::create('kloters', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->foreignId('mesin_id')->constrained('mesins')->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('status'); // enum: Aktif, Selesai
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->cascadeOnDelete();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
            
            $table->index(['mesin_id', 'tanggal', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kloters');
    }
};
