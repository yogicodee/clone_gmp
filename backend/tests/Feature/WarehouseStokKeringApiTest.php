<?php

namespace Tests\Feature;

use App\Models\MasterData\Gudang;
use App\Models\WarehouseSystem\WarehouseStokKering;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WarehouseStokKeringApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_stok_kering_crud_works(): void
    {
        $gudang = Gudang::factory()->create([
            'nama_gudang' => 'Gudang Kering',
        ]);

        $createResponse = $this->postJson('/api/stok-kering', [
            'gudang_id' => $gudang->id,
            'nama_barang' => 'Beras',
            'qty' => 10,
            'satuan_terkecil' => 'Kg',
            'harga_beli' => 12000,
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('message', 'Data stok kering berhasil ditambahkan.')
            ->assertJsonPath('data.gudang.id', $gudang->id);

        $recordId = $createResponse->json('data.id');

        $this->getJson('/api/stok-kering?search=beras')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->putJson('/api/stok-kering/'.$recordId, [
            'gudang_id' => $gudang->id,
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
