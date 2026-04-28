<?php

namespace Tests\Feature;

use App\Models\MasterData\Gudang;
use App\Models\WarehouseSystem\WarehouseStokBasah;
use App\Models\WarehouseSystem\WarehouseStokKering;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanStokBarangApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_laporan_stok_barang_returns_combined_kering_and_basah_rows(): void
    {
        $gudangKering = Gudang::factory()->create([
            'nama_gudang' => 'Gudang Kering',
        ]);

        $gudangBasah = Gudang::factory()->create([
            'nama_gudang' => 'Gudang Basah',
        ]);

        WarehouseStokKering::query()->create([
            'gudang_id' => $gudangKering->id,
            'nama_barang' => 'Beras',
            'qty' => 10,
            'satuan_terkecil' => 'Kg',
            'harga_beli' => 12000,
        ]);

        WarehouseStokBasah::query()->create([
            'gudang_id' => $gudangBasah->id,
            'nama_barang' => 'Minyak',
            'qty' => 8,
            'satuan_terkecil' => 'Liter',
            'harga_beli' => 15000,
        ]);

        $this->getJson('/api/laporan/stok-barang')
            ->assertOk()
            ->assertJsonPath('message', 'Laporan stok barang berhasil diambil.')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.nama_barang', 'Beras')
            ->assertJsonPath('data.0.jenis_stok', 'kering')
            ->assertJsonPath('data.0.nilai_stok', 120000)
            ->assertJsonPath('data.1.nama_barang', 'Minyak')
            ->assertJsonPath('data.1.jenis_stok', 'basah');
    }

    public function test_laporan_stok_barang_can_filter_by_search_and_jenis_stok(): void
    {
        $gudang = Gudang::factory()->create([
            'nama_gudang' => 'Gudang Utama',
        ]);

        WarehouseStokKering::query()->create([
            'gudang_id' => $gudang->id,
            'nama_barang' => 'Beras Premium',
            'qty' => 10,
            'satuan_terkecil' => 'Kg',
            'harga_beli' => 12000,
        ]);

        WarehouseStokBasah::query()->create([
            'gudang_id' => $gudang->id,
            'nama_barang' => 'Minyak Goreng',
            'qty' => 8,
            'satuan_terkecil' => 'Liter',
            'harga_beli' => 15000,
        ]);

        $this->getJson('/api/laporan/stok-barang?search=minyak&jenis_stok=basah')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nama_barang', 'Minyak Goreng')
            ->assertJsonPath('data.0.jenis_stok', 'basah');
    }
}
