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
        Schema::create('petty_cash_flows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('petty_cash_id')->constrained('petty_cashes')->cascadeOnDelete();
            $table->string('tipe'); // [Permintaan, Pengeluaran]
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // user yang request
            $table->unsignedBigInteger('jumlah');
            $table->text('keterangan')->nullable();
            $table->string('status_approval'); // [Pending, Approved, Rejected]
            $table->foreignId('approved_by')->nullable()->constrained('users')->cascadeOnDelete(); // admin yang approve
            $table->dateTime('approved_at')->nullable();
            $table->text('alasan_penolakan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('petty_cash_flows');
    }
};
