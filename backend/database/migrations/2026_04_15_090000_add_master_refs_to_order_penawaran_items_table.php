<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('produk') || ! Schema::hasTable('kategori')) {
            return;
        }

        [$produkType, $produkUnsigned] = $this->resolveColumnDefinition('produk', 'id');
        [$kategoriType, $kategoriUnsigned] = $this->resolveColumnDefinition('kategori', 'id');

        Schema::table('order_penawaran_items', function (Blueprint $table) use ($produkType, $produkUnsigned, $kategoriType, $kategoriUnsigned): void {
            if (! Schema::hasColumn('order_penawaran_items', 'produk_id')) {
                $this->addMatchingIntegerColumn($table, 'produk_id', $produkType, $produkUnsigned);
            }

            if (! Schema::hasColumn('order_penawaran_items', 'kategori_id')) {
                $this->addMatchingIntegerColumn($table, 'kategori_id', $kategoriType, $kategoriUnsigned);
            }
        });

        Schema::table('order_penawaran_items', function (Blueprint $table): void {
            if (! $this->hasForeignKey('order_penawaran_items', 'order_penawaran_items_produk_id_foreign')) {
                $table->foreign('produk_id', 'order_penawaran_items_produk_id_foreign')
                    ->references('id')
                    ->on('produk')
                    ->nullOnDelete();
            }

            if (! $this->hasForeignKey('order_penawaran_items', 'order_penawaran_items_kategori_id_foreign')) {
                $table->foreign('kategori_id', 'order_penawaran_items_kategori_id_foreign')
                    ->references('id')
                    ->on('kategori')
                    ->nullOnDelete();
            }
        });

        $items = DB::table('order_penawaran_items')->get(['id', 'nama_barang', 'satuan']);

        foreach ($items as $item) {
            $produkId = DB::table('produk')->where('nama', $item->nama_barang)->value('id');
            $kategoriId = DB::table('kategori')
                ->where('kode', $item->satuan)
                ->orWhere('nama_satuan', $item->satuan)
                ->value('id');

            DB::table('order_penawaran_items')
                ->where('id', $item->id)
                ->update([
                    'produk_id' => $produkId,
                    'kategori_id' => $kategoriId,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('order_penawaran_items', function (Blueprint $table): void {
            if (Schema::hasColumn('order_penawaran_items', 'produk_id')) {
                if ($this->hasForeignKey('order_penawaran_items', 'order_penawaran_items_produk_id_foreign')) {
                    $table->dropForeign('order_penawaran_items_produk_id_foreign');
                }
                $table->dropColumn('produk_id');
            }

            if (Schema::hasColumn('order_penawaran_items', 'kategori_id')) {
                if ($this->hasForeignKey('order_penawaran_items', 'order_penawaran_items_kategori_id_foreign')) {
                    $table->dropForeign('order_penawaran_items_kategori_id_foreign');
                }
                $table->dropColumn('kategori_id');
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
