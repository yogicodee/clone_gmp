<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penjualan', function (Blueprint $table): void {
            $table->id();
            $table->string('kode_penjualan', 50)->unique();
            $table->date('tanggal');
            $table->string('status', 20)->default('draft');
            $table->decimal('total_harga', 15, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('penjualan_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('penjualan_id')->constrained('penjualan')->cascadeOnDelete();
            $table->foreignId('order_penawaran_item_id')->nullable()->constrained('order_penawaran_items')->nullOnDelete();
            // Legacy master tables use signed INT ids, so these columns must match.
            $table->integer('produk_id')->nullable();
            $table->integer('gudang_id');
            $table->string('nama_barang', 100);
            $table->decimal('qty', 15, 2);
            $table->string('satuan', 50)->nullable();
            $table->decimal('harga_satuan', 15, 2);
            $table->decimal('total_harga', 15, 2);
            $table->timestamps();

            $table->foreign('produk_id')->references('id')->on('produk')->nullOnDelete();
            $table->foreign('gudang_id')->references('id')->on('gudang')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penjualan_items');
        Schema::dropIfExists('penjualan');
    }
};
