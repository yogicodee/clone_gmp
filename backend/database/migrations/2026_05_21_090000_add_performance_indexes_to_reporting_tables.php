<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfMissing('penjualan', 'penjualan_tanggal_status_index', ['tanggal', 'status']);
        $this->addIndexIfMissing('penjualan_items', 'penjualan_items_penjualan_gudang_barang_index', ['penjualan_id', 'gudang_id', 'nama_barang']);

        $this->addIndexIfMissing('invoice_penjualan', 'invoice_penjualan_tanggal_status_index', ['tanggal_invoice', 'status_pembayaran']);
        $this->addIndexIfMissing('invoice_penjualan', 'invoice_penjualan_sppg_tanggal_index', ['sppg_id', 'tanggal_invoice']);

        $this->addIndexIfMissing('surat_jalan', 'surat_jalan_tanggal_sppg_status_index', ['tanggal', 'sppg_id', 'status']);
        $this->addIndexIfMissing('surat_jalan_items', 'surat_jalan_items_surat_penjualan_index', ['surat_jalan_id', 'penjualan_item_id']);

        $this->addIndexIfMissing('tanda_terima', 'tanda_terima_tanggal_sppg_index', ['tanggal', 'sppg_id']);
        $this->addIndexIfMissing('tanda_terima_items', 'tanda_terima_items_tanda_penjualan_index', ['tanda_terima_id', 'penjualan_item_id']);

        $this->addIndexIfMissing('pemasukan', 'pemasukan_tanggal_index', ['tanggal']);
        $this->addIndexIfMissing('pengeluaran', 'pengeluaran_tanggal_keluar_index', ['tanggal_keluar']);

        $this->addIndexIfMissing('warehouse_inbounds', 'warehouse_inbounds_gudang_tanggal_index', ['gudang_id', 'tanggal_masuk']);
        $this->addIndexIfMissing('warehouse_inbounds', 'warehouse_inbounds_nama_barang_index', ['nama_barang']);

        $this->addIndexIfMissing('warehouse_stok_kering', 'warehouse_stok_kering_gudang_barang_index', ['gudang_id', 'nama_barang']);
        $this->addIndexIfMissing('warehouse_stok_basah', 'warehouse_stok_basah_gudang_barang_index', ['gudang_id', 'nama_barang']);
        $this->addIndexIfMissing('warehouse_retur', 'warehouse_retur_gudang_jenis_barang_index', ['gudang_id', 'jenis_stok', 'nama_barang']);
    }

    public function down(): void
    {
        $this->dropIndexIfExists('penjualan', 'penjualan_tanggal_status_index');
        $this->dropIndexIfExists('penjualan_items', 'penjualan_items_penjualan_gudang_barang_index');

        $this->dropIndexIfExists('invoice_penjualan', 'invoice_penjualan_tanggal_status_index');
        $this->dropIndexIfExists('invoice_penjualan', 'invoice_penjualan_sppg_tanggal_index');

        $this->dropIndexIfExists('surat_jalan', 'surat_jalan_tanggal_sppg_status_index');
        $this->dropIndexIfExists('surat_jalan_items', 'surat_jalan_items_surat_penjualan_index');

        $this->dropIndexIfExists('tanda_terima', 'tanda_terima_tanggal_sppg_index');
        $this->dropIndexIfExists('tanda_terima_items', 'tanda_terima_items_tanda_penjualan_index');

        $this->dropIndexIfExists('pemasukan', 'pemasukan_tanggal_index');
        $this->dropIndexIfExists('pengeluaran', 'pengeluaran_tanggal_keluar_index');

        $this->dropIndexIfExists('warehouse_inbounds', 'warehouse_inbounds_gudang_tanggal_index');
        $this->dropIndexIfExists('warehouse_inbounds', 'warehouse_inbounds_nama_barang_index');

        $this->dropIndexIfExists('warehouse_stok_kering', 'warehouse_stok_kering_gudang_barang_index');
        $this->dropIndexIfExists('warehouse_stok_basah', 'warehouse_stok_basah_gudang_barang_index');
        $this->dropIndexIfExists('warehouse_retur', 'warehouse_retur_gudang_jenis_barang_index');
    }

    private function addIndexIfMissing(string $table, string $indexName, array $columns): void
    {
        if (! Schema::hasTable($table) || $this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $indexName): void {
            $blueprint->index($columns, $indexName);
        });
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        if (! Schema::hasTable($table) || ! $this->indexExists($table, $indexName)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($indexName): void {
            $blueprint->dropIndex($indexName);
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");

            foreach ($indexes as $index) {
                if (($index->name ?? null) === $indexName) {
                    return true;
                }
            }

            return false;
        }

        if ($driver === 'mysql') {
            return DB::table('information_schema.STATISTICS')
                ->where('TABLE_SCHEMA', DB::getDatabaseName())
                ->where('TABLE_NAME', $table)
                ->where('INDEX_NAME', $indexName)
                ->exists();
        }

        return false;
    }
};
