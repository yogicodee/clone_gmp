<?php

namespace Tests\Feature;

use App\Models\MasterData\Gudang;
use App\Models\WarehouseSystem\WarehouseInbound;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseInboundApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_inbound_index_returns_paginated_records(): void
    {
        $gudang = Gudang::factory()->create([
            'nama_gudang' => 'Gudang Kering Utama',
        ]);

        WarehouseInbound::query()->create([
            'gudang_id' => $gudang->id,
            'nama_barang' => 'Beras',
            'kategori' => 'kering',
            'tanggal_masuk' => '2026-04-01',
            'qty' => 10,
            'satuan' => 'Kg',
            'harga_satuan' => 12000,
            'total_harga' => 120000,
            'nama_supplier' => 'PT Sumber Pangan',
        ]);

        $response = $this->getJson('/api/inbound?search=beras');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Data inbound berhasil diambil.')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nama_barang', 'Beras')
            ->assertJsonPath('data.0.gudang.id', $gudang->id)
            ->assertJsonPath('data.0.gudang.nama_gudang', 'Gudang Kering Utama');
    }

    public function test_inbound_can_be_created_and_updated(): void
    {
        $gudangAwal = Gudang::factory()->create([
            'nama_gudang' => 'Gudang Basah',
        ]);
        $gudangUpdate = Gudang::factory()->create([
            'nama_gudang' => 'Gudang Kering',
        ]);

        $createResponse = $this->postJson('/api/inbound', [
            'gudang_id' => $gudangAwal->id,
            'nama_barang' => 'Minyak Goreng',
            'kategori' => 'basah',
            'tanggal_masuk' => '2026-04-02',
            'qty' => 5,
            'satuan' => 'Liter',
            'harga_satuan' => 15000,
            'nama_supplier' => 'CV Makmur Jaya',
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.total_harga', '75000.00')
            ->assertJsonPath('data.gudang.id', $gudangAwal->id)
            ->assertJsonPath('data.kategori', 'basah');

        $recordId = $createResponse->json('data.id');

        $this->assertDatabaseHas('warehouse_stok_basah', [
            'warehouse_inbound_id' => $recordId,
            'gudang_id' => $gudangAwal->id,
            'nama_barang' => 'Minyak Goreng',
        ]);

        $this->putJson('/api/inbound/'.$recordId, [
            'gudang_id' => $gudangUpdate->id,
            'nama_barang' => 'Minyak Goreng Premium',
            'kategori' => 'kering',
            'tanggal_masuk' => '2026-04-03',
            'qty' => 6,
            'satuan' => 'Liter',
            'harga_satuan' => 16000,
            'nama_supplier' => 'CV Makmur Jaya',
        ])
            ->assertOk()
            ->assertJsonPath('data.total_harga', '96000.00')
            ->assertJsonPath('data.gudang.id', $gudangUpdate->id)
            ->assertJsonPath('data.kategori', 'kering');

        $this->assertDatabaseMissing('warehouse_stok_basah', [
            'warehouse_inbound_id' => $recordId,
        ]);

        $this->assertDatabaseHas('warehouse_stok_kering', [
            'warehouse_inbound_id' => $recordId,
            'gudang_id' => $gudangUpdate->id,
            'nama_barang' => 'Minyak Goreng Premium',
        ]);
    }

    public function test_inbound_can_be_deleted(): void
    {
        $gudang = Gudang::factory()->create();

        $record = WarehouseInbound::query()->create([
            'gudang_id' => $gudang->id,
            'nama_barang' => 'Telur',
            'kategori' => 'basah',
            'tanggal_masuk' => '2026-04-05',
            'qty' => 20,
            'satuan' => 'Butir',
            'harga_satuan' => 2500,
            'total_harga' => 50000,
            'nama_supplier' => 'UD Segar',
        ]);

        $this->deleteJson('/api/inbound/'.$record->id)
            ->assertOk()
            ->assertJsonPath('message', 'Data inbound berhasil dihapus.');

        $this->assertDatabaseMissing('warehouse_inbounds', [
            'id' => $record->id,
        ]);

        $this->assertDatabaseMissing('warehouse_stok_basah', [
            'warehouse_inbound_id' => $record->id,
        ]);
    }
}
