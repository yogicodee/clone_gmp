<?php

namespace Tests\Feature;

use App\Models\MasterData\Mitra;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MitraApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_mitra_index_returns_paginated_records(): void
    {
        Mitra::factory()->create([
            'nama_yayasan' => 'Yayasan Harapan',
            'alamat' => 'Bandung',
            'nama_pic' => 'Budi',
            'no_pic' => '08123456789',
        ]);

        Mitra::factory()->create([
            'nama_yayasan' => 'Yayasan Maju',
            'alamat' => 'Surabaya',
            'nama_pic' => 'Andi',
            'no_pic' => '08345678901',
        ]);

        Mitra::factory()->create([
            'nama_yayasan' => 'Yayasan Sejahtera',
            'alamat' => 'Jakarta',
            'nama_pic' => 'Siti',
            'no_pic' => '08234567890',
        ]);

        $response = $this->getJson('/api/mitra?search=yayasan&sort_field=nama_yayasan&sort_order=asc&per_page=10');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Data mitra berhasil diambil.')
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('data.0.nama_yayasan', 'Yayasan Harapan')
            ->assertJsonPath('data.1.nama_yayasan', 'Yayasan Maju')
            ->assertJsonPath('data.2.nama_yayasan', 'Yayasan Sejahtera');
    }

    public function test_mitra_can_be_created(): void
    {
        $response = $this->postJson('/api/mitra', [
            'nama_yayasan' => 'Yayasan Baru',
            'alamat' => 'Bogor',
            'nama_pic' => 'Rina',
            'no_pic' => '081298765432',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Mitra berhasil ditambahkan.')
            ->assertJsonPath('data.nama_yayasan', 'Yayasan Baru')
            ->assertJsonPath('data.no_pic', '081298765432');

        $this->assertDatabaseHas('mitra', [
            'nama_yayasan' => 'Yayasan Baru',
            'alamat' => 'Bogor',
            'nama_pic' => 'Rina',
            'no_pic' => '081298765432',
        ]);
    }

    public function test_mitra_phone_can_use_plus_dash_spaces_and_parentheses(): void
    {
        $response = $this->postJson('/api/mitra', [
            'nama_yayasan' => 'Yayasan Format',
            'alamat' => 'Jakarta',
            'nama_pic' => 'Dewi',
            'no_pic' => '+62 (812) 3456-789',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.no_pic', '+62 (812) 3456-789');
    }

    public function test_mitra_phone_rejects_invalid_characters(): void
    {
        $response = $this->postJson('/api/mitra', [
            'nama_yayasan' => 'Yayasan Invalid',
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

    public function test_mitra_detail_can_be_viewed(): void
    {
        $mitra = Mitra::factory()->create([
            'nama_yayasan' => 'Yayasan Cerdas',
            'alamat' => 'Depok',
            'nama_pic' => 'Farah',
            'no_pic' => '081311112222',
        ]);

        $response = $this->getJson('/api/mitra/'.$mitra->id);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $mitra->id)
            ->assertJsonPath('data.nama_yayasan', 'Yayasan Cerdas');
    }

    public function test_mitra_can_be_updated(): void
    {
        $mitra = Mitra::factory()->create([
            'nama_yayasan' => 'Yayasan Lama',
            'alamat' => 'Semarang',
            'nama_pic' => 'Tono',
            'no_pic' => '081200001111',
        ]);

        $response = $this->putJson('/api/mitra/'.$mitra->id, [
            'nama_yayasan' => 'Yayasan Baru Banget',
            'alamat' => 'Semarang Barat',
            'nama_pic' => 'Tina',
            'no_pic' => '081200009999',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Mitra berhasil diperbarui.')
            ->assertJsonPath('data.nama_pic', 'Tina');

        $this->assertDatabaseHas('mitra', [
            'id' => $mitra->id,
            'nama_yayasan' => 'Yayasan Baru Banget',
            'alamat' => 'Semarang Barat',
            'nama_pic' => 'Tina',
            'no_pic' => '081200009999',
        ]);
    }

    public function test_mitra_can_be_deleted(): void
    {
        $mitra = Mitra::factory()->create();

        $response = $this->deleteJson('/api/mitra/'.$mitra->id);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Mitra berhasil dihapus.');

        $this->assertDatabaseMissing('mitra', [
            'id' => $mitra->id,
        ]);
    }
}
