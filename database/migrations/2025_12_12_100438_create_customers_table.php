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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_kategori_id')->constrained('customer_kategoris')->cascadeOnDelete();
            $table->string('nama')->unique();
            $table->longText('alamat')->nullable();
            $table->string('no_hp1')->nullable();
            $table->string('no_hp2')->nullable();
            $table->string('nama_perusahaan')->nullable();
            $table->longText('alamat_perusahaan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
