<?php

namespace Tests\Feature;

use App\Models\WarehouseInbound;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseInboundApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_inbound_index_returns_paginated_records(): void
    {
        WarehouseInbound::query()->create([
            'nama_barang' => 'Beras',
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
            ->assertJsonPath('data.0.nama_barang', 'Beras');
    }

    public function test_inbound_can_be_created_and_updated(): void
    {
        $createResponse = $this->postJson('/api/inbound', [
            'nama_barang' => 'Minyak Goreng',
            'tanggal_masuk' => '2026-04-02',
            'qty' => 5,
            'satuan' => 'Liter',
            'harga_satuan' => 15000,
            'nama_supplier' => 'CV Makmur Jaya',
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.total_harga', '75000.00');

        $recordId = $createResponse->json('data.id');

        $this->putJson('/api/inbound/'.$recordId, [
            'nama_barang' => 'Minyak Goreng Premium',
            'tanggal_masuk' => '2026-04-03',
            'qty' => 6,
            'satuan' => 'Liter',
            'harga_satuan' => 16000,
            'nama_supplier' => 'CV Makmur Jaya',
        ])
            ->assertOk()
            ->assertJsonPath('data.total_harga', '96000.00');
    }

    public function test_inbound_can_be_deleted(): void
    {
        $record = WarehouseInbound::query()->create([
            'nama_barang' => 'Telur',
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
    }
}
