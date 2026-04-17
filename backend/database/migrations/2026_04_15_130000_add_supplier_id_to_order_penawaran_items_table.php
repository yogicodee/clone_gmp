<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('order_penawaran_items') || ! Schema::hasTable('supplier')) {
            return;
        }

        [$supplierType, $supplierUnsigned] = $this->resolveColumnDefinition('supplier', 'id');

        Schema::table('order_penawaran_items', function (Blueprint $table) use ($supplierType, $supplierUnsigned): void {
            if (! Schema::hasColumn('order_penawaran_items', 'supplier_id')) {
                $this->addMatchingIntegerColumn($table, 'supplier_id', $supplierType, $supplierUnsigned);
            }
        });

        Schema::table('order_penawaran_items', function (Blueprint $table): void {
            if (! $this->hasForeignKey('order_penawaran_items', 'order_penawaran_items_supplier_id_foreign')) {
                $table->foreign('supplier_id', 'order_penawaran_items_supplier_id_foreign')
                    ->references('id')
                    ->on('supplier')
                    ->nullOnDelete();
            }
        });

        $supplierIds = DB::table('supplier')->orderBy('id')->pluck('id')->all();

        if ($supplierIds === []) {
            return;
        }

        $preferredSuppliers = [
            'beras' => 'PT Sumber Pangan',
            'minyak' => 'CV Makmur Jaya',
            'minyak goreng' => 'CV Makmur Jaya',
            'tepung' => 'UD Manis Jaya',
            'gula' => 'PT Sinar',
        ];

        $items = DB::table('order_penawaran_items')->get(['id', 'nama_barang']);

        foreach ($items as $index => $item) {
            $normalizedName = strtolower((string) $item->nama_barang);
            $matchedSupplierId = null;

            foreach ($preferredSuppliers as $keyword => $supplierName) {
                if (str_contains($normalizedName, $keyword)) {
                    $matchedSupplierId = DB::table('supplier')->where('nama', $supplierName)->value('id');
                    if ($matchedSupplierId !== null) {
                        break;
                    }
                }
            }

            $matchedSupplierId ??= $supplierIds[$index % count($supplierIds)];

            DB::table('order_penawaran_items')
                ->where('id', $item->id)
                ->update(['supplier_id' => $matchedSupplierId]);
        }
    }

    public function down(): void
    {
        Schema::table('order_penawaran_items', function (Blueprint $table): void {
            if (Schema::hasColumn('order_penawaran_items', 'supplier_id')) {
                if ($this->hasForeignKey('order_penawaran_items', 'order_penawaran_items_supplier_id_foreign')) {
                    $table->dropForeign('order_penawaran_items_supplier_id_foreign');
                }

                $table->dropColumn('supplier_id');
            }
        });
    }

    private function addMatchingIntegerColumn(Blueprint $table, string $name, string $type, bool $unsigned): void
    {
        if (str_contains($type, 'big')) {
            if ($unsigned) {
                $table->unsignedBigInteger($name)->nullable();

                return;
            }

            $table->bigInteger($name)->nullable();

            return;
        }

        if ($unsigned) {
            $table->unsignedInteger($name)->nullable();

            return;
        }

        $table->integer($name)->nullable();
    }

    /**
     * @return array{0:string,1:bool}
     */
    private function resolveColumnDefinition(string $table, string $column): array
    {
        $driver = DB::getDriverName();
        $type = strtolower(Schema::getColumnType($table, $column));

        if ($driver === 'mysql') {
            $native = DB::selectOne("SHOW COLUMNS FROM {$table} WHERE Field = ?", [$column]);
            $nativeType = strtolower($native->Type ?? $type);

            return [$nativeType, str_contains($nativeType, 'unsigned')];
        }

        return [$type, true];
    }

    private function hasForeignKey(string $table, string $name): bool
    {
        if (DB::getDriverName() !== 'mysql') {
            return false;
        }

        $result = DB::selectOne(
            'SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?',
            [$table, $name]
        );

        return $result !== null;
    }
};
