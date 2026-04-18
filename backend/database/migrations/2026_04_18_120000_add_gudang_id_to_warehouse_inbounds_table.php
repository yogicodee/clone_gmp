<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('warehouse_inbounds') || ! Schema::hasTable('gudang')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            $gudangIdColumn = $this->getMysqlColumn('gudang', 'id');
            $targetType = strtolower((string) ($gudangIdColumn->Type ?? 'int'));

            if (Schema::hasColumn('warehouse_inbounds', 'gudang_id')) {
                $inboundGudangColumn = $this->getMysqlColumn('warehouse_inbounds', 'gudang_id');
                $currentType = strtolower((string) ($inboundGudangColumn->Type ?? ''));

                if ($currentType !== $targetType) {
                    if ($this->hasMysqlForeignKey('warehouse_inbounds', 'gudang_id')) {
                        Schema::table('warehouse_inbounds', function (Blueprint $table): void {
                            $table->dropForeign('warehouse_inbounds_gudang_id_foreign');
                        });
                    }

                    DB::statement(sprintf(
                        'ALTER TABLE `warehouse_inbounds` MODIFY `gudang_id` %s NULL',
                        strtoupper($targetType)
                    ));
                }
            } else {
                Schema::table('warehouse_inbounds', function (Blueprint $table) use ($targetType): void {
                    if (str_contains($targetType, 'bigint')) {
                        str_contains($targetType, 'unsigned')
                            ? $table->unsignedBigInteger('gudang_id')->nullable()->after('id')
                            : $table->bigInteger('gudang_id')->nullable()->after('id');

                        return;
                    }

                    str_contains($targetType, 'unsigned')
                        ? $table->unsignedInteger('gudang_id')->nullable()->after('id')
                        : $table->integer('gudang_id')->nullable()->after('id');
                });
            }

            if (! $this->hasMysqlForeignKey('warehouse_inbounds', 'gudang_id')) {
                Schema::table('warehouse_inbounds', function (Blueprint $table): void {
                    $table->foreign('gudang_id', 'warehouse_inbounds_gudang_id_foreign')
                        ->references('id')
                        ->on('gudang')
                        ->nullOnDelete();
                });
            }

            return;
        }

        Schema::table('warehouse_inbounds', function (Blueprint $table): void {
            if (! Schema::hasColumn('warehouse_inbounds', 'gudang_id')) {
                $table->foreignId('gudang_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('gudang')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('warehouse_inbounds')) {
            return;
        }

        Schema::table('warehouse_inbounds', function (Blueprint $table): void {
            if (Schema::hasColumn('warehouse_inbounds', 'gudang_id')) {
                if (DB::getDriverName() === 'mysql') {
                    if ($this->hasMysqlForeignKey('warehouse_inbounds', 'gudang_id')) {
                        $table->dropForeign('warehouse_inbounds_gudang_id_foreign');
                    }

                    $table->dropColumn('gudang_id');

                    return;
                }

                $table->dropConstrainedForeignId('gudang_id');
            }
        });
    }

    private function getMysqlColumn(string $table, string $column): ?object
    {
        $result = DB::select("SHOW COLUMNS FROM `{$table}` WHERE Field = ?", [$column]);

        return $result[0] ?? null;
    }

    private function hasMysqlForeignKey(string $table, string $column): bool
    {
        $database = DB::getDatabaseName();

        $result = DB::select(
            <<<'SQL'
                SELECT CONSTRAINT_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = ?
                    AND TABLE_NAME = ?
                    AND COLUMN_NAME = ?
                    AND REFERENCED_TABLE_NAME IS NOT NULL
                LIMIT 1
            SQL,
            [$database, $table, $column]
        );

        return $result !== [];
    }
};
