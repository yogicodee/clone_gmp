<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('warehouse_inbounds', function (Blueprint $table): void {
            if (! Schema::hasColumn('warehouse_inbounds', 'kategori')) {
                $table->enum('kategori', ['basah', 'kering'])->default('kering')->after('nama_barang');
            }
        });
    }

    public function down(): void
    {
        Schema::table('warehouse_inbounds', function (Blueprint $table): void {
            if (Schema::hasColumn('warehouse_inbounds', 'kategori')) {
                $table->dropColumn('kategori');
            }
        });
    }
};
