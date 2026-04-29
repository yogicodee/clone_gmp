<?php

namespace Database\Seeders;

use App\Models\MasterData\Gudang;
use App\Models\TransaksiPenjualan\Penjualan;
use App\Models\WarehouseSystem\WarehouseInbound;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DashboardSummarySeeder extends Seeder
{
    public function run(): void
    {
        $today = Carbon::today('Asia/Jakarta')->toDateString();

        Penjualan::query()->updateOrCreate(
            ['kode_penjualan' => 'TRX-DASH-001'],
            [
                'tanggal' => $today,
                'status' => 'selesai',
                'total_harga' => 1500000,
            ]
        );

        Penjualan::query()->updateOrCreate(
            ['kode_penjualan' => 'TRX-DASH-002'],
            [
                'tanggal' => $today,
                'status' => 'selesai',
                'total_harga' => 2750000,
            ]
        );

        Penjualan::query()->updateOrCreate(
            ['kode_penjualan' => 'TRX-DASH-003'],
            [
                'tanggal' => $today,
                'status' => 'draft',
                'total_harga' => 999999,
            ]
        );

        $gudang = Gudang::query()->firstOrCreate(
            ['nama_gudang' => 'Gudang Dashboard Summary'],
            [
                'alamat' => 'Jl. Dashboard Summary',
                'nama_pic' => 'PIC Dashboard',
                'no_pic' => '081234567804',
            ]
        );

        WarehouseInbound::query()->updateOrCreate(
            ['nama_barang' => 'Beban Dashboard 1', 'tanggal_masuk' => $today],
            [
                'gudang_id' => $gudang->id,
                'kategori' => 'kering',
                'qty' => 10,
                'satuan' => 'Kg',
                'harga_satuan' => 100000,
                'total_harga' => 1000000,
                'nama_supplier' => 'Supplier Dashboard',
            ]
        );

        WarehouseInbound::query()->updateOrCreate(
            ['nama_barang' => 'Beban Dashboard 2', 'tanggal_masuk' => $today],
            [
                'gudang_id' => $gudang->id,
                'kategori' => 'basah',
                'qty' => 5,
                'satuan' => 'Liter',
                'harga_satuan' => 150000,
                'total_harga' => 750000,
                'nama_supplier' => 'Supplier Dashboard',
            ]
        );
    }
}
