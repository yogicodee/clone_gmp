<?php

namespace Database\Seeders;

use App\Models\MasterData\Gudang;
use App\Models\TransaksiPenjualan\Penjualan;
use App\Models\WarehouseSystem\WarehouseInbound;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class LabaRugiTransaksionalSeeder extends Seeder
{
    public function run(): void
    {
        $today = Carbon::today('Asia/Jakarta')->toDateString();
        $yesterday = Carbon::today('Asia/Jakarta')->subDay()->toDateString();

        Penjualan::query()->updateOrCreate(
            ['kode_penjualan' => 'TRX-LR-001'],
            [
                'tanggal' => $today,
                'status' => 'selesai',
                'total_harga' => 4250000,
            ]
        );

        Penjualan::query()->updateOrCreate(
            ['kode_penjualan' => 'TRX-LR-002'],
            [
                'tanggal' => $yesterday,
                'status' => 'selesai',
                'total_harga' => 2750000,
            ]
        );

        $gudang = Gudang::query()->firstOrCreate(
            ['nama_gudang' => 'Gudang Laba Rugi'],
            [
                'alamat' => 'Jl. Laba Rugi',
                'nama_pic' => 'PIC LR',
                'no_pic' => '081234567803',
            ]
        );

        WarehouseInbound::query()->updateOrCreate(
            ['nama_barang' => 'Beban Hari Ini', 'tanggal_masuk' => $today],
            [
                'gudang_id' => $gudang->id,
                'kategori' => 'kering',
                'qty' => 10,
                'satuan' => 'Kg',
                'harga_satuan' => 150000,
                'total_harga' => 1500000,
                'nama_supplier' => 'Supplier LR',
            ]
        );

        WarehouseInbound::query()->updateOrCreate(
            ['nama_barang' => 'Beban Kemarin', 'tanggal_masuk' => $yesterday],
            [
                'gudang_id' => $gudang->id,
                'kategori' => 'basah',
                'qty' => 5,
                'satuan' => 'Liter',
                'harga_satuan' => 100000,
                'total_harga' => 500000,
                'nama_supplier' => 'Supplier LR',
            ]
        );
    }
}
