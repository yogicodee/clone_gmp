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
        if (Schema::hasTable('produk')) {
            return;
        }

        Schema::create('produk', function (Blueprint $table): void {
            $table->id();
            $table->string('sku', 100)->nullable();
            $table->string('nama', 100)->nullable();
            $table->string('kategori', 50)->nullable();
            $table->string('satuan', 50)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produk');
    }
};
