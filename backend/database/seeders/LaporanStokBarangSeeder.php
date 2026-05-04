<?php

namespace Database\Seeders;

use App\Models\MasterData\Gudang;
use App\Models\WarehouseSystem\WarehouseInbound;
use App\Models\WarehouseSystem\WarehouseStokBasah;
use App\Models\WarehouseSystem\WarehouseStokKering;
use Illuminate\Database\Seeder;

class LaporanStokBarangSeeder extends Seeder
{
    public function run(): void
    {
        $gudangKering = Gudang::query()->firstOrCreate(
            ['nama_gudang' => 'Gudang Kering Laporan'],
            [
                'alamat' => 'Jl. Kering',
                'nama_pic' => 'PIC Kering',
                'no_pic' => '081234567801',
            ]
        );

        $gudangBasah = Gudang::query()->firstOrCreate(
            ['nama_gudang' => 'Gudang Basah Laporan'],
            [
                'alamat' => 'Jl. Basah',
                'nama_pic' => 'PIC Basah',
                'no_pic' => '081234567802',
            ]
        );

        $inboundBeras = WarehouseInbound::query()->updateOrCreate(
            ['nama_barang' => 'Beras Premium', 'gudang_id' => $gudangKering->id],
            [
                'kategori' => 'kering',
                'tanggal_masuk' => '2026-05-04',
                'qty' => 120,
                'satuan' => 'Kg',
                'harga_satuan' => 14000,
                'total_harga' => 1680000,
                'nama_supplier' => 'Supplier Kering',
            ]
        );

        WarehouseStokKering::query()->updateOrCreate(
            ['nama_barang' => 'Beras Premium', 'gudang_id' => $gudangKering->id],
            [
                'warehouse_inbound_id' => $inboundBeras->id,
                'qty' => 120,
                'satuan_terkecil' => 'Kg',
                'harga_beli' => 14000,
            ]
        );

        $inboundGula = WarehouseInbound::query()->updateOrCreate(
            ['nama_barang' => 'Gula Pasir', 'gudang_id' => $gudangKering->id],
            [
                'kategori' => 'kering',
                'tanggal_masuk' => '2026-05-03',
                'qty' => 80,
                'satuan' => 'Kg',
                'harga_satuan' => 15500,
                'total_harga' => 1240000,
                'nama_supplier' => 'Supplier Kering',
            ]
        );

        WarehouseStokKering::query()->updateOrCreate(
            ['nama_barang' => 'Gula Pasir', 'gudang_id' => $gudangKering->id],
            [
                'warehouse_inbound_id' => $inboundGula->id,
                'qty' => 80,
                'satuan_terkecil' => 'Kg',
                'harga_beli' => 15500,
            ]
        );

        $inboundMinyak = WarehouseInbound::query()->updateOrCreate(
            ['nama_barang' => 'Minyak Goreng', 'gudang_id' => $gudangBasah->id],
            [
                'kategori' => 'basah',
                'tanggal_masuk' => '2026-05-04',
                'qty' => 60,
                'satuan' => 'Liter',
                'harga_satuan' => 17000,
                'total_harga' => 1020000,
                'nama_supplier' => 'Supplier Basah',
            ]
        );

        WarehouseStokBasah::query()->updateOrCreate(
            ['nama_barang' => 'Minyak Goreng', 'gudang_id' => $gudangBasah->id],
            [
                'warehouse_inbound_id' => $inboundMinyak->id,
                'qty' => 60,
                'satuan_terkecil' => 'Liter',
                'harga_beli' => 17000,
            ]
        );

        $inboundSusu = WarehouseInbound::query()->updateOrCreate(
            ['nama_barang' => 'Susu Cair', 'gudang_id' => $gudangBasah->id],
            [
                'kategori' => 'basah',
                'tanggal_masuk' => '2026-05-02',
                'qty' => 40,
                'satuan' => 'Liter',
                'harga_satuan' => 18500,
                'total_harga' => 740000,
                'nama_supplier' => 'Supplier Basah',
            ]
        );

        WarehouseStokBasah::query()->updateOrCreate(
            ['nama_barang' => 'Susu Cair', 'gudang_id' => $gudangBasah->id],
            [
                'warehouse_inbound_id' => $inboundSusu->id,
                'qty' => 40,
                'satuan_terkecil' => 'Liter',
                'harga_beli' => 18500,
            ]
        );
    }
}
