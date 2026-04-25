<?php

namespace Tests\Feature;

use App\Models\MasterData\Wilayah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WilayahApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_wilayah_index_returns_paginated_records(): void
    {
        Wilayah::factory()->create([
            'nama' => 'Jakarta Barat',
            'alamat' => 'DKI Jakarta',
        ]);

        Wilayah::factory()->create([
            'nama' => 'Bandung Kota',
            'alamat' => 'Jawa Barat',
        ]);

        Wilayah::factory()->create([
            'nama' => 'Surabaya Timur',
            'alamat' => 'Jawa Timur',
        ]);

        $response = $this->getJson('/api/wilayah?search=barat&per_page=10');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Data wilayah berhasil diambil.')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.nama', 'Bandung Kota')
            ->assertJsonPath('data.1.nama', 'Jakarta Barat');
    }

    public function test_wilayah_can_be_created(): void
    {
        $response = $this->postJson('/api/wilayah', [
            'nama' => 'Kabupaten Gresik',
            'alamat' => 'Jawa Timur',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Wilayah berhasil ditambahkan.')
            ->assertJsonPath('data.nama', 'Kabupaten Gresik');

        $this->assertDatabaseHas('wilayah', [
            'nama' => 'Kabupaten Gresik',
            'alamat' => 'Jawa Timur',
        ]);
    }

    public function test_wilayah_detail_can_be_viewed(): void
    {
        $wilayah = Wilayah::factory()->create([
            'nama' => 'Kota Bogor',
            'alamat' => 'Jawa Barat',
        ]);

        $response = $this->getJson('/api/wilayah/'.$wilayah->id);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $wilayah->id)
            ->assertJsonPath('data.nama', 'Kota Bogor');
    }

    public function test_wilayah_can_be_updated(): void
    {
        $wilayah = Wilayah::factory()->create([
            'nama' => 'Kabupaten Malang',
            'alamat' => 'Malang Lama',
        ]);

        $response = $this->putJson('/api/wilayah/'.$wilayah->id, [
            'nama' => 'Kota Malang',
            'alamat' => 'Jawa Timur',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Wilayah berhasil diperbarui.')
            ->assertJsonPath('data.nama', 'Kota Malang');

        $this->assertDatabaseHas('wilayah', [
            'id' => $wilayah->id,
            'nama' => 'Kota Malang',
            'alamat' => 'Jawa Timur',
        ]);
    }

    public function test_wilayah_can_be_deleted(): void
    {
        $wilayah = Wilayah::factory()->create();

        $response = $this->deleteJson('/api/wilayah/'.$wilayah->id);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Wilayah berhasil dihapus.');

        $this->assertDatabaseMissing('wilayah', [
            'id' => $wilayah->id,
        ]);
    }
}
