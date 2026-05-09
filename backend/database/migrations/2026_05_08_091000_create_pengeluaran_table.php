<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengeluaran', function (Blueprint $table): void {
            $table->id();
            $table->string('nama_operasional', 100);
            $table->date('tanggal_keluar');
            $table->decimal('qty', 15, 2);
            $table->string('satuan', 50);
            $table->decimal('harga_satuan', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengeluaran');
    }
};
