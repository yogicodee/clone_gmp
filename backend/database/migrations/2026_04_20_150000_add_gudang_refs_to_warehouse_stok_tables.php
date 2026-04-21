<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addColumns('warehouse_stok_kering');
        $this->addColumns('warehouse_stok_basah');

        $this->backfillFromInbound('warehouse_stok_kering', 'kering');
        $this->backfillFromInbound('warehouse_stok_basah', 'basah');
    }

    public function down(): void
    {
        $this->dropColumns('warehouse_stok_kering');
        $this->dropColumns('warehouse_stok_basah');
    }

    private function addColumns(string $table): void
    {
        Schema::table($table, function (Blueprint $blueprint) use ($table): void {
            if (! Schema::hasColumn($table, 'warehouse_inbound_id')) {
                $blueprint->unsignedBigInteger('warehouse_inbound_id')->nullable()->after('id');
            }

            if (! Schema::hasColumn($table, 'gudang_id')) {
                $blueprint->unsignedInteger('gudang_id')->nullable()->after('warehouse_inbound_id');
            }
        });
    }

    private function dropColumns(string $table): void
    {
        Schema::table($table, function (Blueprint $blueprint) use ($table): void {
            $columns = [];

            if (Schema::hasColumn($table, 'gudang_id')) {
                $columns[] = 'gudang_id';
            }

            if (Schema::hasColumn($table, 'warehouse_inbound_id')) {
                $columns[] = 'warehouse_inbound_id';
            }

            if ($columns !== []) {
                $blueprint->dropColumn($columns);
            }
        });
    }

    private function backfillFromInbound(string $stockTable, string $kategori): void
    {
        $rows = DB::table($stockTable)->get();

        foreach ($rows as $row) {
            $match = DB::table('warehouse_inbounds')
                ->where('kategori', $kategori)
                ->where('nama_barang', $row->nama_barang)
                ->whereRaw('ROUND(qty, 2) = ?', [(float) $row->qty])
                ->whereRaw('UPPER(satuan) = ?', [strtoupper((string) $row->satuan_terkecil)])
                ->whereRaw('ROUND(harga_satuan, 2) = ?', [(float) $row->harga_beli])
                ->orderByDesc('id')
                ->first();

            if ($match === null) {
                continue;
            }

            DB::table($stockTable)
                ->where('id', $row->id)
                ->update([
                    'warehouse_inbound_id' => $match->id,
                    'gudang_id' => $match->gudang_id,
                ]);
        }
    }
};
