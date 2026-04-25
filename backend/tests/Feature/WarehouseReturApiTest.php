<?php

namespace Tests\Feature;

use App\Models\MasterData\Gudang;
use App\Models\WarehouseSystem\WarehouseStokBasah;
use App\Models\WarehouseSystem\WarehouseStokKering;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseReturApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_retur_crud_works(): void
    {
        $gudang = Gudang::factory()->create();
        $stokKering = WarehouseStokKering::query()->create([
            'gudang_id' => $gudang->id,
            'nama_barang' => 'Beras',
            'qty' => 10,
            'satuan_terkecil' => 'Kg',
            'harga_beli' => 12000,
        ]);

        $createResponse = $this->postJson('/api/retur-rusak', [
            'gudang_id' => $gudang->id,
            'jenis_stok' => 'kering',
            'nama_barang' => 'Beras',
            'qty_retur' => 2,
            'satuan_terkecil' => 'Kg',
            'harga_beli' => 12000,
            'alasan' => 'Barang rusak',
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('message', 'Data retur/rusak berhasil ditambahkan.')
            ->assertJsonPath('data.gudang.id', $gudang->id);

        $this->assertDatabaseHas('warehouse_stok_kering', [
            'id' => $stokKering->id,
            'qty' => '8.00',
        ]);

        $recordId = $createResponse->json('data.id');

        $this->getJson('/api/retur-rusak?search=beras')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->putJson('/api/retur-rusak/'.$recordId, [
            'gudang_id' => $gudang->id,
            'jenis_stok' => 'kering',
            'nama_barang' => 'Beras',
            'qty_retur' => 3,
            'satuan_terkecil' => 'Kg',
            'harga_beli' => 12000,
            'alasan' => 'Kemasan rusak',
        ])
            ->assertOk()
            ->assertJsonPath('data.alasan', 'Kemasan rusak');

        $this->assertDatabaseHas('warehouse_stok_kering', [
            'id' => $stokKering->id,
            'qty' => '7.00',
        ]);

        $this->deleteJson('/api/retur-rusak/'.$recordId)
            ->assertOk()
            ->assertJsonPath('message', 'Data retur/rusak berhasil dihapus.');

        $this->assertDatabaseHas('warehouse_stok_kering', [
            'id' => $stokKering->id,
            'qty' => '10.00',
        ]);
    }

    public function test_retur_cannot_make_stock_negative(): void
    {
        $gudang = Gudang::factory()->create();
        WarehouseStokBasah::query()->create([
            'gudang_id' => $gudang->id,
            'nama_barang' => 'Minyak',
            'qty' => 2,
            'satuan_terkecil' => 'Liter',
            'harga_beli' => 15000,
        ]);

        $this->postJson('/api/retur-rusak', [
            'gudang_id' => $gudang->id,
            'jenis_stok' => 'basah',
            'nama_barang' => 'Minyak',
            'qty_retur' => 3,
            'satuan_terkecil' => 'Liter',
            'harga_beli' => 15000,
            'alasan' => 'Bocor',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['qty_retur']);
    }
}
