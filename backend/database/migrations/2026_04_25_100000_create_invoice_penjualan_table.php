<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_penjualan', function (Blueprint $table): void {
            $table->id();
            $table->string('nomor_invoice', 50)->unique();
            $table->foreignId('penjualan_id')->constrained('penjualan')->cascadeOnDelete();
            $table->date('tanggal_invoice');
            $table->decimal('total_tagihan', 15, 2)->default(0);
            $table->string('status_pembayaran', 20)->default('belum lunas');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_penjualan');
    }
};
