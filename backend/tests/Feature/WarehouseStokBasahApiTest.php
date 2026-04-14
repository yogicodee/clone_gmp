<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseStokBasahApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_stok_basah_crud_works(): void
    {
        $createResponse = $this->postJson('/api/stok-basah', [
            'nama_barang' => 'Minyak Goreng',
            'qty' => 8,
            'satuan_terkecil' => 'Liter',
            'harga_beli' => 15000,
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('message', 'Data stok basah berhasil ditambahkan.');

        $recordId = $createResponse->json('data.id');

        $this->getJson('/api/stok-basah?search=minyak')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->putJson('/api/stok-basah/'.$recordId, [
            'nama_barang' => 'Minyak Goreng Premium',
            'qty' => 9,
            'satuan_terkecil' => 'Liter',
            'harga_beli' => 16000,
        ])
            ->assertOk()
            ->assertJsonPath('data.nama_barang', 'Minyak Goreng Premium');

        $this->deleteJson('/api/stok-basah/'.$recordId)
            ->assertOk()
            ->assertJsonPath('message', 'Data stok basah berhasil dihapus.');
    }
}
