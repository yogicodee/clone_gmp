<?php

namespace Database\Seeders;

use App\Models\MasterData\Gudang;
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

        WarehouseStokKering::query()->updateOrCreate(
            ['nama_barang' => 'Beras Premium', 'gudang_id' => $gudangKering->id],
            [
                'qty' => 120,
                'satuan_terkecil' => 'Kg',
                'harga_beli' => 14000,
            ]
        );

        WarehouseStokKering::query()->updateOrCreate(
            ['nama_barang' => 'Gula Pasir', 'gudang_id' => $gudangKering->id],
            [
                'qty' => 80,
                'satuan_terkecil' => 'Kg',
                'harga_beli' => 15500,
            ]
        );

        WarehouseStokBasah::query()->updateOrCreate(
            ['nama_barang' => 'Minyak Goreng', 'gudang_id' => $gudangBasah->id],
            [
                'qty' => 60,
                'satuan_terkecil' => 'Liter',
                'harga_beli' => 17000,
            ]
        );

        WarehouseStokBasah::query()->updateOrCreate(
            ['nama_barang' => 'Susu Cair', 'gudang_id' => $gudangBasah->id],
            [
                'qty' => 40,
                'satuan_terkecil' => 'Liter',
                'harga_beli' => 18500,
            ]
        );
    }
}
