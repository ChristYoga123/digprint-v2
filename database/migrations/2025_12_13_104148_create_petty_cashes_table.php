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
        Schema::create('petty_cashes', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->unique(); // satu session per hari
            $table->unsignedBigInteger('uang_buka'); // uang dari atasan saat buka toko
            $table->unsignedBigInteger('uang_tutup')->nullable(); // sisa uang saat tutup toko
            $table->foreignId('user_id_buka')->constrained('users')->cascadeOnDelete(); // user yang input buka toko
            $table->foreignId('user_id_tutup')->nullable()->constrained('users')->cascadeOnDelete(); // user yang input tutup toko
            $table->foreignId('approved_by_buka')->nullable()->constrained('users')->cascadeOnDelete(); // atasan yang approve buka toko
            $table->dateTime('approved_at_buka')->nullable();
            $table->text('alasan_penolakan_buka')->nullable();
            $table->text('catatan_persetujuan_buka')->nullable();
            $table->foreignId('approved_by_tutup')->nullable()->constrained('users')->cascadeOnDelete(); // atasan yang approve tutup toko
            $table->dateTime('approved_at_tutup')->nullable();
            $table->text('alasan_penolakan_tutup')->nullable();
            $table->text('catatan_persetujuan_tutup')->nullable();
            $table->string('status'); // enum: Buka, Tutup
            $table->text('keterangan_buka')->nullable();
            $table->text('keterangan_tutup')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('petty_cashes');
    }
};
