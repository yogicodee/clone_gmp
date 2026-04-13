<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('daftar_pembelanjaan')) {
            Schema::create('daftar_pembelanjaan', function (Blueprint $table): void {
                $table->id();
                $table->date('tanggal_pesan');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('daftar_pembelanjaan_items')) {
            Schema::create('daftar_pembelanjaan_items', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('daftar_pembelanjaan_id')->constrained('daftar_pembelanjaan')->cascadeOnDelete();
                $table->string('nama_barang', 100);
                $table->decimal('qty', 12, 2);
                $table->string('satuan', 50);
                $table->decimal('stok', 12, 2)->default(0);
                $table->decimal('kebutuhan', 12, 2)->default(0);
                $table->string('nama_supplier', 100);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('daftar_pembelanjaan_items');
        Schema::dropIfExists('daftar_pembelanjaan');
    }
};
