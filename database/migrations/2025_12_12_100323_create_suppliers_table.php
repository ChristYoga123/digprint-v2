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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->string('nama_perusahaan')->nullable();
            $table->string('nama_sales');
            $table->string('no_hp_sales');
            $table->longText('alamat_perusahaan')->nullable();
            $table->longText('alamat_gudang');
            $table->string('metode_pembayaran1');
            $table->string('nomor_rekening1')->nullable();
            $table->string('nama_rekening1')->nullable();
            $table->string('metode_pembayaran2');
            $table->string('nomor_rekening2')->nullable();
            $table->string('nama_rekening2')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_pkp')->default(false);
            $table->string('npwp')->nullable();
            $table->boolean('is_po')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
