<?php

namespace Tests\Feature;

use App\Models\MasterData\Gudang;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GudangApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_gudang_index_returns_paginated_records(): void
    {
        Gudang::factory()->create([
            'nama_gudang' => 'Gudang Pusat',
            'alamat' => 'Bandung',
            'nama_pic' => 'Budi',
            'no_pic' => '08123456789',
        ]);

        Gudang::factory()->create([
            'nama_gudang' => 'Gudang Barat',
            'alamat' => 'Jakarta',
            'nama_pic' => 'Siti',
            'no_pic' => '08234567890',
        ]);

        Gudang::factory()->create([
            'nama_gudang' => 'Gudang Timur',
            'alamat' => 'Surabaya',
            'nama_pic' => 'Andi',
            'no_pic' => '08345678901',
        ]);

        $response = $this->getJson('/api/gudang?search=gudang&sort_field=nama_gudang&sort_order=asc&per_page=10');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Data gudang berhasil diambil.')
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('data.0.nama_gudang', 'Gudang Barat')
            ->assertJsonPath('data.1.nama_gudang', 'Gudang Pusat')
            ->assertJsonPath('data.2.nama_gudang', 'Gudang Timur');
    }

    public function test_gudang_can_be_created(): void
    {
        $response = $this->postJson('/api/gudang', [
            'nama_gudang' => 'Gudang Selatan',
            'alamat' => 'Bogor',
            'nama_pic' => 'Rina',
            'no_pic' => '081298765432',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Gudang berhasil ditambahkan.')
            ->assertJsonPath('data.nama_gudang', 'Gudang Selatan')
            ->assertJsonPath('data.no_pic', '081298765432');

        $this->assertDatabaseHas('gudang', [
            'nama_gudang' => 'Gudang Selatan',
            'alamat' => 'Bogor',
            'nama_pic' => 'Rina',
            'no_pic' => '081298765432',
        ]);
    }

    public function test_gudang_phone_can_use_plus_dash_spaces_and_parentheses(): void
    {
        $response = $this->postJson('/api/gudang', [
            'nama_gudang' => 'Gudang Format',
            'alamat' => 'Depok',
            'nama_pic' => 'Dewi',
            'no_pic' => '+62 (812) 3456-789',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.no_pic', '+62 (812) 3456-789');
    }

    public function test_gudang_phone_rejects_invalid_characters(): void
    {
        $response = $this->postJson('/api/gudang', [
            'nama_gudang' => 'Gudang Invalid',
            'alamat' => 'Bandung',
            'nama_pic' => 'Doni',
            'no_pic' => '08abc12345',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.no_pic.0',
                'No PIC hanya boleh berisi angka dan karakter khusus tertentu.'
            );
    }

    public function test_gudang_detail_can_be_viewed(): void
    {
        $gudang = Gudang::factory()->create([
            'nama_gudang' => 'Gudang Khusus',
            'alamat' => 'Depok',
            'nama_pic' => 'Farah',
            'no_pic' => '081311112222',
        ]);

        $response = $this->getJson('/api/gudang/'.$gudang->id);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $gudang->id)
            ->assertJsonPath('data.nama_gudang', 'Gudang Khusus');
    }

    public function test_gudang_can_be_updated(): void
    {
        $gudang = Gudang::factory()->create([
            'nama_gudang' => 'Gudang Lama',
            'alamat' => 'Semarang',
            'nama_pic' => 'Tono',
            'no_pic' => '081200001111',
        ]);

        $response = $this->putJson('/api/gudang/'.$gudang->id, [
            'nama_gudang' => 'Gudang Baru Banget',
            'alamat' => 'Semarang Barat',
            'nama_pic' => 'Tina',
            'no_pic' => '081200009999',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Gudang berhasil diperbarui.')
            ->assertJsonPath('data.nama_pic', 'Tina');

        $this->assertDatabaseHas('gudang', [
            'id' => $gudang->id,
            'nama_gudang' => 'Gudang Baru Banget',
            'alamat' => 'Semarang Barat',
            'nama_pic' => 'Tina',
            'no_pic' => '081200009999',
        ]);
    }

    public function test_gudang_can_be_deleted(): void
    {
        $gudang = Gudang::factory()->create();

        $response = $this->deleteJson('/api/gudang/'.$gudang->id);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Gudang berhasil dihapus.');

        $this->assertDatabaseMissing('gudang', [
            'id' => $gudang->id,
        ]);
    }
}
