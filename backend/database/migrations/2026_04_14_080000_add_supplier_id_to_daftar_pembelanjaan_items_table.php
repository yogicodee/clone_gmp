<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('supplier')) {
            return;
        }

        $driver = DB::getDriverName();
        $supplierIdType = strtolower(Schema::getColumnType('supplier', 'id'));
        $nativeSupplierIdType = $supplierIdType;

        if ($driver === 'mysql') {
            $column = DB::selectOne("SHOW COLUMNS FROM supplier WHERE Field = 'id'");
            $nativeSupplierIdType = strtolower($column->Type ?? $supplierIdType);
        }

        $usesBigInteger = str_contains($nativeSupplierIdType, 'big');
        $isUnsigned = $driver !== 'mysql' || str_contains($nativeSupplierIdType, 'unsigned');

        Schema::table('daftar_pembelanjaan_items', function (Blueprint $table) use ($isUnsigned, $usesBigInteger): void {
            if (! Schema::hasColumn('daftar_pembelanjaan_items', 'supplier_id')) {
                if ($usesBigInteger) {
                    if ($isUnsigned) {
                        $table->unsignedBigInteger('supplier_id')->nullable()->after('daftar_pembelanjaan_id');
                    } else {
                        $table->bigInteger('supplier_id')->nullable()->after('daftar_pembelanjaan_id');
                    }
                } else {
                    if ($isUnsigned) {
                        $table->unsignedInteger('supplier_id')->nullable()->after('daftar_pembelanjaan_id');
                    } else {
                        $table->integer('supplier_id')->nullable()->after('daftar_pembelanjaan_id');
                    }
                }
            }
        });

        Schema::table('daftar_pembelanjaan_items', function (Blueprint $table): void {
            $table->foreign('supplier_id', 'daftar_pembelanjaan_items_supplier_id_foreign')
                ->references('id')
                ->on('supplier')
                ->nullOnDelete();
        });

        $items = DB::table('daftar_pembelanjaan_items')
            ->whereNull('supplier_id')
            ->whereNotNull('nama_supplier')
            ->get(['id', 'nama_supplier']);

        foreach ($items as $item) {
            $supplierId = DB::table('supplier')
                ->where('nama', $item->nama_supplier)
                ->value('id');

            if ($supplierId) {
                DB::table('daftar_pembelanjaan_items')
                    ->where('id', $item->id)
                    ->update(['supplier_id' => $supplierId]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('daftar_pembelanjaan_items', function (Blueprint $table): void {
            if (Schema::hasColumn('daftar_pembelanjaan_items', 'supplier_id')) {
                $table->dropForeign('daftar_pembelanjaan_items_supplier_id_foreign');
                $table->dropColumn('supplier_id');
            }
        });
    }
};
