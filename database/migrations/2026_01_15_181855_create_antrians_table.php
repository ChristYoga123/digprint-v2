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
        Schema::create('antrians', function (Blueprint $table) {
            $table->id();
            $table->integer('nomor_antrian');
            $table->date('tanggal'); // Untuk grouping per hari
            $table->string('status')->default('waiting'); // waiting, called, completed, skipped
            $table->integer('deskprint_number')->nullable(); // 1-6
            $table->foreignId('called_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('called_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            // Index untuk query cepat
            $table->index(['tanggal', 'status']);
            $table->index(['tanggal', 'nomor_antrian']);
            $table->unique(['tanggal', 'nomor_antrian']); // Nomor antrian unik per hari
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('antrians');
    }
};
