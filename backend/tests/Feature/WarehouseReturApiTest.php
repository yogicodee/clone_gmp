<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseReturApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_retur_crud_works(): void
    {
        $createResponse = $this->postJson('/api/retur-rusak', [
            'nama_barang' => 'Beras',
            'qty_retur' => 2,
            'satuan_terkecil' => 'Kg',
            'harga_beli' => 12000,
            'alasan' => 'Barang rusak',
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('message', 'Data retur/rusak berhasil ditambahkan.');

        $recordId = $createResponse->json('data.id');

        $this->getJson('/api/retur-rusak?search=beras')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->putJson('/api/retur-rusak/'.$recordId, [
            'nama_barang' => 'Beras',
            'qty_retur' => 3,
            'satuan_terkecil' => 'Kg',
            'harga_beli' => 12000,
            'alasan' => 'Kemasan rusak',
        ])
            ->assertOk()
            ->assertJsonPath('data.alasan', 'Kemasan rusak');

        $this->deleteJson('/api/retur-rusak/'.$recordId)
            ->assertOk()
            ->assertJsonPath('message', 'Data retur/rusak berhasil dihapus.');
    }
}
