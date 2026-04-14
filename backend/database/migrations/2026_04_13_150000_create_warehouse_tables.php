<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('warehouse_inbounds')) {
            Schema::create('warehouse_inbounds', function (Blueprint $table): void {
                $table->id();
                $table->string('nama_barang', 100);
                $table->date('tanggal_masuk');
                $table->decimal('qty', 12, 2);
                $table->string('satuan', 50);
                $table->decimal('harga_satuan', 15, 2);
                $table->decimal('total_harga', 15, 2);
                $table->string('nama_supplier', 100);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('warehouse_stok_kering')) {
            Schema::create('warehouse_stok_kering', function (Blueprint $table): void {
                $table->id();
                $table->string('nama_barang', 100);
                $table->decimal('qty', 12, 2)->default(0);
                $table->string('satuan_terkecil', 50);
                $table->decimal('harga_beli', 15, 2)->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('warehouse_stok_basah')) {
            Schema::create('warehouse_stok_basah', function (Blueprint $table): void {
                $table->id();
                $table->string('nama_barang', 100);
                $table->decimal('qty', 12, 2)->default(0);
                $table->string('satuan_terkecil', 50);
                $table->decimal('harga_beli', 15, 2)->default(0);
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('warehouse_retur')) {
            Schema::create('warehouse_retur', function (Blueprint $table): void {
                $table->id();
                $table->string('nama_barang', 100);
                $table->decimal('qty_retur', 12, 2);
                $table->string('satuan_terkecil', 50);
                $table->decimal('harga_beli', 15, 2)->default(0);
                $table->string('alasan', 255);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouse_retur');
        Schema::dropIfExists('warehouse_stok_basah');
        Schema::dropIfExists('warehouse_stok_kering');
        Schema::dropIfExists('warehouse_inbounds');
    }
};
