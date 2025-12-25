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
        Schema::create('transaksis', function (Blueprint $table) {
            $table->id();
            $table->string('kode')->unique();
            $table->foreignId('transaksi_kalkulasi_id')->constrained('transaksi_kalkulasis')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->unsignedBigInteger('total_harga_transaksi');
            $table->string('jenis_diskon')->nullable(); // [Per Item, Per Invoice]
            $table->unsignedBigInteger('total_diskon_transaksi')->nullable();
            $table->unsignedBigInteger('total_harga_transaksi_setelah_diskon'); // hasil dari total_harga_transaksi - total_diskon_transaksi
            $table->foreignId('approved_diskon_by')->nullable()->constrained('users')->cascadeOnDelete(); // jika ada diskon
            $table->string('status_transaksi'); // [Belum, Dalam Proses, Selesai]
            $table->string('status_pembayaran'); // 1: Lunas 2: Term of Payment
            $table->string('metode_pembayaran')->nullable(); // metode pembayaran
            $table->unsignedBigInteger('jumlah_bayar')->default(0); // total yang sudah dibayar
            $table->unsignedBigInteger('jumlah_kembalian')->default(0); // jumlah kembalian jika lunas saja. jika top selalu 0
            $table->date('tanggal_pembayaran')->nullable(); // jika lunas/top
            $table->date('tanggal_jatuh_tempo')->nullable(); // jika term of payment
            $table->string('design')->nullable(); // Akan diisi setelah design diapprove
            $table->string('link_design')->nullable(); // link design jika customer sudah punya design sendiri
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); // user yang checkout transaksi
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaksis');
    }
};
