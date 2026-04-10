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
        if (! Schema::hasTable('produk') || Schema::hasColumn('produk', 'sku')) {
            return;
        }

        Schema::table('produk', function (Blueprint $table): void {
            $table->string('sku', 100)->nullable()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('produk') || ! Schema::hasColumn('produk', 'sku')) {
            return;
        }

        Schema::table('produk', function (Blueprint $table): void {
            $table->dropColumn('sku');
        });
    }
};
