<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_penawaran', function (Blueprint $table): void {
            $table->id();
            $table->date('tanggal_pesan');
            $table->date('tanggal_dikirim')->nullable();
            $table->string('nama_pembeli', 100);
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });

        Schema::create('order_penawaran_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_penawaran_id')
                ->constrained('order_penawaran')
                ->cascadeOnDelete();
            $table->string('nama_barang', 100);
            $table->decimal('qty', 12, 2);
            $table->string('satuan', 50);
            $table->decimal('harga_satuan', 15, 2)->default(0);
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_penawaran_items');
        Schema::dropIfExists('order_penawaran');
    }
};
