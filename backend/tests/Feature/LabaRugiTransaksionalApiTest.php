<?php

namespace Tests\Feature;

use App\Models\MasterData\Gudang;
use App\Models\TransaksiPenjualan\Penjualan;
use App\Models\WarehouseSystem\WarehouseInbound;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LabaRugiTransaksionalApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_laba_rugi_transaksional_returns_summary_and_rows_by_period(): void
    {
        $gudang = Gudang::factory()->create([
            'nama_gudang' => 'Gudang Laba Rugi',
        ]);

        Penjualan::query()->create([
            'kode_penjualan' => 'TRX-LR-100',
            'tanggal' => '2026-04-28',
            'status' => 'selesai',
            'total_harga' => 5000000,
        ]);

        Penjualan::query()->create([
            'kode_penjualan' => 'TRX-LR-200',
            'tanggal' => '2026-04-27',
            'status' => 'selesai',
            'total_harga' => 3000000,
        ]);

        Penjualan::query()->create([
            'kode_penjualan' => 'TRX-LR-300',
            'tanggal' => '2026-03-15',
            'status' => 'selesai',
            'total_harga' => 9000000,
        ]);

        WarehouseInbound::query()->create([
            'gudang_id' => $gudang->id,
            'nama_barang' => 'Beban 1',
            'kategori' => 'kering',
            'tanggal_masuk' => '2026-04-28',
            'qty' => 1,
            'satuan' => 'Pcs',
            'harga_satuan' => 2000000,
            'total_harga' => 2000000,
            'nama_supplier' => 'Supplier A',
        ]);

        WarehouseInbound::query()->create([
            'gudang_id' => $gudang->id,
            'nama_barang' => 'Beban 2',
            'kategori' => 'basah',
            'tanggal_masuk' => '2026-04-27',
            'qty' => 1,
            'satuan' => 'Pcs',
            'harga_satuan' => 500000,
            'total_harga' => 500000,
            'nama_supplier' => 'Supplier B',
        ]);

        $this->getJson('/api/laporan/laba-rugi-transaksional?periode=bulanan&tanggal=2026-04-28')
            ->assertOk()
            ->assertJsonPath('message', 'Laporan laba rugi transaksional berhasil diambil.')
            ->assertJsonPath('data.periode', 'bulanan')
            ->assertJsonPath('data.total_pendapatan', 8000000)
            ->assertJsonPath('data.total_beban', 2500000)
            ->assertJsonPath('data.total_laba_rugi', 5500000)
            ->assertJsonCount(2, 'data.rows')
            ->assertJsonPath('data.rows.0.tanggal', '2026-04-28')
            ->assertJsonPath('data.rows.0.pendapatan', 5000000)
            ->assertJsonPath('data.rows.0.beban', 2000000)
            ->assertJsonPath('data.rows.0.laba_rugi', 3000000);
    }
}
