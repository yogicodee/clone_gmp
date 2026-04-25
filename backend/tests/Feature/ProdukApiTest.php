<?php

namespace Tests\Feature;

use App\Models\MasterData\Produk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProdukApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_produk_index_returns_paginated_records(): void
    {
        Produk::factory()->create([
            'sku' => 'BRG-001',
            'nama' => 'Beras',
            'kategori' => 'Kering',
            'satuan' => 'KG',
        ]);

        Produk::factory()->create([
            'sku' => 'BRG-002',
            'nama' => 'Minyak Goreng',
            'kategori' => 'Basah',
            'satuan' => 'LITER',
        ]);

        Produk::factory()->create([
            'sku' => 'BRG-003',
            'nama' => 'Telur',
            'kategori' => 'Kering',
            'satuan' => 'PCS',
        ]);

        $response = $this->getJson('/api/produk?search=BRG&sort_field=sku&sort_order=asc&per_page=10');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Data produk berhasil diambil.')
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('data.0.sku', 'BRG-001')
            ->assertJsonPath('data.1.sku', 'BRG-002')
            ->assertJsonPath('data.2.sku', 'BRG-003');
    }

    public function test_produk_can_be_created(): void
    {
        $response = $this->postJson('/api/produk', [
            'sku' => 'BRG-010',
            'nama' => 'Gula Pasir',
            'kategori' => 'Kering',
            'satuan' => 'KG',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Produk berhasil ditambahkan.')
            ->assertJsonPath('data.sku', 'BRG-010')
            ->assertJsonPath('data.nama', 'Gula Pasir');

        $this->assertDatabaseHas('produk', [
            'sku' => 'BRG-010',
            'nama' => 'Gula Pasir',
            'kategori' => 'Kering',
            'satuan' => 'KG',
        ]);
    }

    public function test_produk_rejects_duplicate_sku(): void
    {
        Produk::factory()->create([
            'sku' => 'BRG-001',
        ]);

        $response = $this->postJson('/api/produk', [
            'sku' => 'BRG-001',
            'nama' => 'Beras Premium',
            'kategori' => 'Kering',
            'satuan' => 'KG',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('errors.sku.0', 'SKU sudah digunakan.');
    }

    public function test_produk_rejects_invalid_sku_format(): void
    {
        $response = $this->postJson('/api/produk', [
            'sku' => 'brg 001',
            'nama' => 'Beras',
            'kategori' => 'Kering',
            'satuan' => 'KG',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.sku.0',
                'SKU hanya boleh berisi huruf kapital, angka, dan tanda minus (-).'
            );
    }

    public function test_produk_detail_can_be_viewed(): void
    {
        $produk = Produk::factory()->create([
            'sku' => 'BRG-020',
            'nama' => 'Tepung',
        ]);

        $response = $this->getJson('/api/produk/'.$produk->id);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $produk->id)
            ->assertJsonPath('data.sku', 'BRG-020');
    }

    public function test_produk_can_be_updated(): void
    {
        $produk = Produk::factory()->create([
            'sku' => 'BRG-030',
            'nama' => 'Minyak',
            'kategori' => 'Basah',
            'satuan' => 'LITER',
        ]);

        $response = $this->putJson('/api/produk/'.$produk->id, [
            'sku' => 'BRG-031',
            'nama' => 'Minyak Jagung',
            'kategori' => 'Basah',
            'satuan' => 'LITER',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Produk berhasil diperbarui.')
            ->assertJsonPath('data.sku', 'BRG-031')
            ->assertJsonPath('data.nama', 'Minyak Jagung');

        $this->assertDatabaseHas('produk', [
            'id' => $produk->id,
            'sku' => 'BRG-031',
            'nama' => 'Minyak Jagung',
        ]);
    }

    public function test_produk_can_be_deleted(): void
    {
        $produk = Produk::factory()->create();

        $response = $this->deleteJson('/api/produk/'.$produk->id);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Produk berhasil dihapus.');

        $this->assertDatabaseMissing('produk', [
            'id' => $produk->id,
        ]);
    }
}
