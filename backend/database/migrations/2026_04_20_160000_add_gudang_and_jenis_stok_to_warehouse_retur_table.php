<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('warehouse_retur')) {
            return;
        }

        Schema::table('warehouse_retur', function (Blueprint $table): void {
            if (! Schema::hasColumn('warehouse_retur', 'gudang_id')) {
                $table->unsignedInteger('gudang_id')->nullable()->after('id');
            }

            if (! Schema::hasColumn('warehouse_retur', 'jenis_stok')) {
                $table->string('jenis_stok', 20)->nullable()->after('gudang_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('warehouse_retur')) {
            return;
        }

        Schema::table('warehouse_retur', function (Blueprint $table): void {
            if (Schema::hasColumn('warehouse_retur', 'jenis_stok')) {
                $table->dropColumn('jenis_stok');
            }

            if (Schema::hasColumn('warehouse_retur', 'gudang_id')) {
                $table->dropColumn('gudang_id');
            }
        });
    }
};
