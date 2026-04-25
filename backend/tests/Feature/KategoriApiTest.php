<?php

namespace Tests\Feature;

use App\Models\MasterData\Kategori;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KategoriApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_kategori_index_returns_paginated_records(): void
    {
        Kategori::factory()->create([
            'kode' => 'BOX',
            'nama_satuan' => 'Box',
        ]);

        Kategori::factory()->create([
            'kode' => 'KG',
            'nama_satuan' => 'Kilogram',
        ]);

        Kategori::factory()->create([
            'kode' => 'PCS',
            'nama_satuan' => 'Pieces',
        ]);

        $response = $this->getJson('/api/kategori?search=K&sort_field=kode&sort_order=asc&per_page=10');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Data kategori berhasil diambil.')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.kode', 'KG');
    }

    public function test_kategori_can_be_created(): void
    {
        $response = $this->postJson('/api/kategori', [
            'kode' => 'LTR',
            'nama_satuan' => 'Liter',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Kategori berhasil ditambahkan.')
            ->assertJsonPath('data.kode', 'LTR')
            ->assertJsonPath('data.nama_satuan', 'Liter');

        $this->assertDatabaseHas('kategori', [
            'kode' => 'LTR',
            'nama_satuan' => 'Liter',
        ]);
    }

    public function test_kategori_rejects_duplicate_kode(): void
    {
        Kategori::factory()->create([
            'kode' => 'PCS',
        ]);

        $response = $this->postJson('/api/kategori', [
            'kode' => 'PCS',
            'nama_satuan' => 'Pieces Baru',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('errors.kode.0', 'Kode sudah digunakan.');
    }

    public function test_kategori_detail_can_be_viewed(): void
    {
        $kategori = Kategori::factory()->create([
            'kode' => 'BOX',
        ]);

        $response = $this->getJson('/api/kategori/'.$kategori->id);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $kategori->id)
            ->assertJsonPath('data.kode', 'BOX');
    }

    public function test_kategori_can_be_updated(): void
    {
        $kategori = Kategori::factory()->create([
            'kode' => 'PCS',
            'nama_satuan' => 'Pieces',
        ]);

        $response = $this->putJson('/api/kategori/'.$kategori->id, [
            'kode' => 'PCS-NEW',
            'nama_satuan' => 'Pieces Baru',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Kategori berhasil diperbarui.')
            ->assertJsonPath('data.kode', 'PCS-NEW');

        $this->assertDatabaseHas('kategori', [
            'id' => $kategori->id,
            'kode' => 'PCS-NEW',
            'nama_satuan' => 'Pieces Baru',
        ]);
    }

    public function test_kategori_can_be_deleted(): void
    {
        $kategori = Kategori::factory()->create();

        $response = $this->deleteJson('/api/kategori/'.$kategori->id);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Kategori berhasil dihapus.');

        $this->assertDatabaseMissing('kategori', [
            'id' => $kategori->id,
        ]);
    }
}
