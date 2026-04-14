<?php

namespace Tests\Feature;

use App\Models\WarehouseStokKering;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseStokKeringApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_stok_kering_crud_works(): void
    {
        $createResponse = $this->postJson('/api/stok-kering', [
            'nama_barang' => 'Beras',
            'qty' => 10,
            'satuan_terkecil' => 'Kg',
            'harga_beli' => 12000,
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('message', 'Data stok kering berhasil ditambahkan.');

        $recordId = $createResponse->json('data.id');

        $this->getJson('/api/stok-kering?search=beras')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->putJson('/api/stok-kering/'.$recordId, [
            'nama_barang' => 'Beras Premium',
            'qty' => 12,
            'satuan_terkecil' => 'Kg',
            'harga_beli' => 13000,
        ])
            ->assertOk()
            ->assertJsonPath('data.nama_barang', 'Beras Premium');

        $this->deleteJson('/api/stok-kering/'.$recordId)
            ->assertOk()
            ->assertJsonPath('message', 'Data stok kering berhasil dihapus.');
    }
}
