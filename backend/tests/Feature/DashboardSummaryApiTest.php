<?php

namespace Tests\Feature;

use App\Models\MasterData\Gudang;
use App\Models\TransaksiPenjualan\Penjualan;
use App\Models\WarehouseSystem\WarehouseInbound;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardSummaryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_summary_returns_today_global_omset_pengeluaran_and_keuntungan(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 27, 8, 0, 0, 'Asia/Jakarta'));

        $gudang = Gudang::query()->create([
            'nama_gudang' => 'Gudang Dashboard',
            'alamat' => 'Jl. Dashboard',
            'nama_pic' => 'Budi',
            'no_pic' => '08123456789',
        ]);

        Penjualan::query()->create([
            'kode_penjualan' => 'TRX-001',
            'tanggal' => '2026-04-27',
            'status' => 'selesai',
            'total_harga' => 1500000,
        ]);

        Penjualan::query()->create([
            'kode_penjualan' => 'TRX-002',
            'tanggal' => '2026-04-27',
            'status' => 'selesai',
            'total_harga' => 2750000,
        ]);

        Penjualan::query()->create([
            'kode_penjualan' => 'TRX-003',
            'tanggal' => '2026-04-27',
            'status' => 'draft',
            'total_harga' => 999999,
        ]);

        Penjualan::query()->create([
            'kode_penjualan' => 'TRX-004',
            'tanggal' => '2026-04-26',
            'status' => 'selesai',
            'total_harga' => 5000000,
        ]);

        WarehouseInbound::query()->create([
            'gudang_id' => $gudang->id,
            'nama_barang' => 'Beban 1',
            'kategori' => 'kering',
            'tanggal_masuk' => '2026-04-27',
            'qty' => 10,
            'satuan' => 'Kg',
            'harga_satuan' => 100000,
            'total_harga' => 1000000,
            'nama_supplier' => 'Supplier A',
        ]);

        WarehouseInbound::query()->create([
            'gudang_id' => $gudang->id,
            'nama_barang' => 'Beban 2',
            'kategori' => 'basah',
            'tanggal_masuk' => '2026-04-27',
            'qty' => 5,
            'satuan' => 'Liter',
            'harga_satuan' => 150000,
            'total_harga' => 750000,
            'nama_supplier' => 'Supplier B',
        ]);

        WarehouseInbound::query()->create([
            'gudang_id' => $gudang->id,
            'nama_barang' => 'Beban Lama',
            'kategori' => 'kering',
            'tanggal_masuk' => '2026-04-26',
            'qty' => 1,
            'satuan' => 'Kg',
            'harga_satuan' => 200000,
            'total_harga' => 200000,
            'nama_supplier' => 'Supplier C',
        ]);

        $this->getJson('/api/dashboard/summary')
            ->assertOk()
            ->assertJsonPath('message', 'Ringkasan dashboard berhasil diambil.')
            ->assertJsonPath('data.tanggal', '2026-04-27')
            ->assertJsonPath('data.omset_hari_ini', 4250000)
            ->assertJsonPath('data.pengeluaran_hari_ini', 1750000)
            ->assertJsonPath('data.keuntungan_hari_ini', 2500000);

        Carbon::setTestNow();
    }
}
