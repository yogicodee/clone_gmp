<?php

namespace Tests\Feature;

use App\Models\MasterData\Sppg;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SppgApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_sppg_index_returns_paginated_records(): void
    {
        Sppg::factory()->create([
            'nama_sppg' => 'SPPG Candi',
            'alamat' => 'Jakarta',
            'nama_yayasan' => 'Yayasan Sejahtera',
            'nama_penanggungjawab' => 'Siti',
            'no_penanggungjawab' => '08234567890',
        ]);

        Sppg::factory()->create([
            'nama_sppg' => 'SPPG Ceweng',
            'alamat' => 'Bandung',
            'nama_yayasan' => 'Yayasan Harapan',
            'nama_penanggungjawab' => 'Budi',
            'no_penanggungjawab' => '08123456789',
        ]);

        Sppg::factory()->create([
            'nama_sppg' => 'SPPG Jonggol',
            'alamat' => 'Jakarta',
            'nama_yayasan' => 'Yayasan Sejahtera',
            'nama_penanggungjawab' => 'Siti',
            'no_penanggungjawab' => '08234567890',
        ]);

        $response = $this->getJson('/api/sppg?search=sppg&sort_field=nama_sppg&sort_order=asc&per_page=10');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Data SPPG berhasil diambil.')
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('data.0.nama_sppg', 'SPPG Candi')
            ->assertJsonPath('data.1.nama_sppg', 'SPPG Ceweng')
            ->assertJsonPath('data.2.nama_sppg', 'SPPG Jonggol');
    }

    public function test_sppg_can_be_created(): void
    {
        $response = $this->postJson('/api/sppg', [
            'nama_sppg' => 'SPPG Baru',
            'alamat' => 'Bogor',
            'nama_yayasan' => 'Yayasan Maju',
            'nama_penanggungjawab' => 'Rina',
            'no_penanggungjawab' => '081298765432',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'SPPG berhasil ditambahkan.')
            ->assertJsonPath('data.nama_sppg', 'SPPG Baru')
            ->assertJsonPath('data.no_penanggungjawab', '081298765432');

        $this->assertDatabaseHas('sppg', [
            'nama_sppg' => 'SPPG Baru',
            'alamat' => 'Bogor',
            'nama_yayasan' => 'Yayasan Maju',
            'nama_penanggungjawab' => 'Rina',
            'no_penanggungjawab' => '081298765432',
        ]);
    }

    public function test_sppg_phone_can_use_plus_dash_spaces_and_parentheses(): void
    {
        $response = $this->postJson('/api/sppg', [
            'nama_sppg' => 'SPPG Format',
            'alamat' => 'Depok',
            'nama_yayasan' => 'Yayasan Format',
            'nama_penanggungjawab' => 'Dewi',
            'no_penanggungjawab' => '+62 (812) 3456-789',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.no_penanggungjawab', '+62 (812) 3456-789');
    }

    public function test_sppg_phone_rejects_invalid_characters(): void
    {
        $response = $this->postJson('/api/sppg', [
            'nama_sppg' => 'SPPG Invalid',
            'alamat' => 'Bandung',
            'nama_yayasan' => 'Yayasan Invalid',
            'nama_penanggungjawab' => 'Doni',
            'no_penanggungjawab' => '08abc12345',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.no_penanggungjawab.0',
                'No HP hanya boleh berisi angka dan karakter khusus tertentu.'
            );
    }

    public function test_sppg_detail_can_be_viewed(): void
    {
        $sppg = Sppg::factory()->create([
            'nama_sppg' => 'SPPG Cerdas',
            'alamat' => 'Depok',
            'nama_yayasan' => 'Yayasan Cerdas',
            'nama_penanggungjawab' => 'Farah',
            'no_penanggungjawab' => '081311112222',
        ]);

        $response = $this->getJson('/api/sppg/'.$sppg->id);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $sppg->id)
            ->assertJsonPath('data.nama_sppg', 'SPPG Cerdas');
    }

    public function test_sppg_can_be_updated(): void
    {
        $sppg = Sppg::factory()->create([
            'nama_sppg' => 'SPPG Lama',
            'alamat' => 'Semarang',
            'nama_yayasan' => 'Yayasan Lama',
            'nama_penanggungjawab' => 'Tono',
            'no_penanggungjawab' => '081200001111',
        ]);

        $response = $this->putJson('/api/sppg/'.$sppg->id, [
            'nama_sppg' => 'SPPG Baru Banget',
            'alamat' => 'Semarang Barat',
            'nama_yayasan' => 'Yayasan Baru',
            'nama_penanggungjawab' => 'Tina',
            'no_penanggungjawab' => '081200009999',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'SPPG berhasil diperbarui.')
            ->assertJsonPath('data.nama_penanggungjawab', 'Tina');

        $this->assertDatabaseHas('sppg', [
            'id' => $sppg->id,
            'nama_sppg' => 'SPPG Baru Banget',
            'alamat' => 'Semarang Barat',
            'nama_yayasan' => 'Yayasan Baru',
            'nama_penanggungjawab' => 'Tina',
            'no_penanggungjawab' => '081200009999',
        ]);
    }

    public function test_sppg_can_be_deleted(): void
    {
        $sppg = Sppg::factory()->create();

        $response = $this->deleteJson('/api/sppg/'.$sppg->id);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'SPPG berhasil dihapus.');

        $this->assertDatabaseMissing('sppg', [
            'id' => $sppg->id,
        ]);
    }
}
