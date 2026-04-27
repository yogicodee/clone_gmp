<?php

namespace Database\Seeders;

use App\Models\TransaksiPenjualan\Penjualan;
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
    }
}
