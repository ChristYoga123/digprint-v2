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
        Schema::create('karyawan_pekerjaans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('karyawan_id')->constrained('users')->cascadeOnDelete();
            $table->string('tipe')->default('Normal'); // Normal, Lembur
            $table->string('karyawan_pekerjaan_type')->nullable(); // jika Normal, maka polymorphic relation, jika Lembur, maka null
            $table->unsignedBigInteger('karyawan_pekerjaan_id')->nullable(); // jika Normal, maka polymorphic relation, jika Lembur, maka null
            $table->dateTime('jam_lembur_mulai')->nullable(); // jika Lembur, maka required
            $table->dateTime('jam_lembur_selesai')->nullable(); // jika Lembur, maka required
            $table->dateTime('jam_aktual_mulai')->nullable(); // jika Lembur, maka required
            $table->dateTime('jam_aktual_selesai')->nullable(); // jika Lembur, maka required
            $table->boolean('apakah_diapprove_lembur')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->cascadeOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('karyawan_pekerjaans');
    }
};
