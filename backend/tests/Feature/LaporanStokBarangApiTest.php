<?php

namespace Tests\Feature;

use App\Models\MasterData\Gudang;
use App\Models\WarehouseSystem\WarehouseInbound;
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

        $inboundKering = WarehouseInbound::query()->create([
            'gudang_id' => $gudangKering->id,
            'nama_barang' => 'Beras',
            'kategori' => 'kering',
            'tanggal_masuk' => '2026-05-04',
            'qty' => 10,
            'satuan' => 'Kg',
            'harga_satuan' => 12000,
            'total_harga' => 120000,
            'nama_supplier' => 'Supplier A',
        ]);

        $inboundBasah = WarehouseInbound::query()->create([
            'gudang_id' => $gudangBasah->id,
            'nama_barang' => 'Minyak',
            'kategori' => 'basah',
            'tanggal_masuk' => '2026-05-04',
            'qty' => 8,
            'satuan' => 'Liter',
            'harga_satuan' => 15000,
            'total_harga' => 120000,
            'nama_supplier' => 'Supplier B',
        ]);

        WarehouseStokKering::query()->create([
            'warehouse_inbound_id' => $inboundKering->id,
            'gudang_id' => $gudangKering->id,
            'nama_barang' => 'Beras',
            'qty' => 10,
            'satuan_terkecil' => 'Kg',
            'harga_beli' => 12000,
        ]);

        WarehouseStokBasah::query()->create([
            'warehouse_inbound_id' => $inboundBasah->id,
            'gudang_id' => $gudangBasah->id,
            'nama_barang' => 'Minyak',
            'qty' => 8,
            'satuan_terkecil' => 'Liter',
            'harga_beli' => 15000,
        ]);

        $this->getJson('/api/laporan/stok-barang?periode=harian&tanggal=2026-05-04')
            ->assertOk()
            ->assertJsonPath('message', 'Laporan stok barang berhasil diambil.')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.nama_barang', 'Beras')
            ->assertJsonPath('data.0.jenis_stok', 'kering')
            ->assertJsonPath('data.0.nilai_stok', 120000)
            ->assertJsonPath('data.1.nama_barang', 'Minyak')
            ->assertJsonPath('data.1.jenis_stok', 'basah')
            ->assertJsonFragment(['nama_gudang' => 'Gudang Basah'])
            ->assertJsonFragment(['nama_gudang' => 'Gudang Kering']);
    }

    public function test_laporan_stok_barang_can_filter_by_search_jenis_stok_and_gudang(): void
    {
        $gudang = Gudang::factory()->create([
            'nama_gudang' => 'Gudang Utama',
        ]);

        $inboundKering = WarehouseInbound::query()->create([
            'gudang_id' => $gudang->id,
            'nama_barang' => 'Beras Premium',
            'kategori' => 'kering',
            'tanggal_masuk' => '2026-05-04',
            'qty' => 10,
            'satuan' => 'Kg',
            'harga_satuan' => 12000,
            'total_harga' => 120000,
            'nama_supplier' => 'Supplier A',
        ]);

        $inboundBasah = WarehouseInbound::query()->create([
            'gudang_id' => $gudang->id,
            'nama_barang' => 'Minyak Goreng',
            'kategori' => 'basah',
            'tanggal_masuk' => '2026-05-04',
            'qty' => 8,
            'satuan' => 'Liter',
            'harga_satuan' => 15000,
            'total_harga' => 120000,
            'nama_supplier' => 'Supplier B',
        ]);

        WarehouseStokKering::query()->create([
            'warehouse_inbound_id' => $inboundKering->id,
            'gudang_id' => $gudang->id,
            'nama_barang' => 'Beras Premium',
            'qty' => 10,
            'satuan_terkecil' => 'Kg',
            'harga_beli' => 12000,
        ]);

        WarehouseStokBasah::query()->create([
            'warehouse_inbound_id' => $inboundBasah->id,
            'gudang_id' => $gudang->id,
            'nama_barang' => 'Minyak Goreng',
            'qty' => 8,
            'satuan_terkecil' => 'Liter',
            'harga_beli' => 15000,
        ]);

        $this->getJson('/api/laporan/stok-barang?search=minyak&jenis_stok=basah&gudang_id='.$gudang->id.'&periode=harian&tanggal=2026-05-04')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nama_barang', 'Minyak Goreng')
            ->assertJsonPath('data.0.jenis_stok', 'basah')
            ->assertJsonPath('meta.periode', 'harian')
            ->assertJsonPath('summary.per_gudang.0.nama_gudang', 'Gudang Utama');
    }

    public function test_laporan_stok_barang_can_filter_by_weekly_period(): void
    {
        $gudang = Gudang::factory()->create([
            'nama_gudang' => 'Gudang Mingguan',
        ]);

        $inboundMasukMingguIni = WarehouseInbound::query()->create([
            'gudang_id' => $gudang->id,
            'nama_barang' => 'Telur',
            'kategori' => 'basah',
            'tanggal_masuk' => '2026-05-04',
            'qty' => 20,
            'satuan' => 'Tray',
            'harga_satuan' => 50000,
            'total_harga' => 1000000,
            'nama_supplier' => 'Supplier C',
        ]);

        $inboundMingguLalu = WarehouseInbound::query()->create([
            'gudang_id' => $gudang->id,
            'nama_barang' => 'Beras Lama',
            'kategori' => 'kering',
            'tanggal_masuk' => '2026-04-20',
            'qty' => 30,
            'satuan' => 'Kg',
            'harga_satuan' => 10000,
            'total_harga' => 300000,
            'nama_supplier' => 'Supplier D',
        ]);

        WarehouseStokBasah::query()->create([
            'warehouse_inbound_id' => $inboundMasukMingguIni->id,
            'gudang_id' => $gudang->id,
            'nama_barang' => 'Telur',
            'qty' => 20,
            'satuan_terkecil' => 'Tray',
            'harga_beli' => 50000,
        ]);

        WarehouseStokKering::query()->create([
            'warehouse_inbound_id' => $inboundMingguLalu->id,
            'gudang_id' => $gudang->id,
            'nama_barang' => 'Beras Lama',
            'qty' => 30,
            'satuan_terkecil' => 'Kg',
            'harga_beli' => 10000,
        ]);

        $this->getJson('/api/laporan/stok-barang?periode=mingguan&tanggal=2026-05-04')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nama_barang', 'Telur')
            ->assertJsonPath('meta.periode', 'mingguan');
    }
}
