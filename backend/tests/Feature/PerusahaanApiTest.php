<?php

namespace Tests\Feature;

use App\Models\MasterData\Perusahaan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerusahaanApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_perusahaan_index_returns_paginated_records(): void
    {
        Perusahaan::factory()->create([
            'nama_perusahaan' => 'PT Alfa',
            'alamat' => 'Surabaya',
            'nama_pic' => 'Budi',
        ]);

        Perusahaan::factory()->create([
            'nama_perusahaan' => 'PT Beta',
            'alamat' => 'Jakarta',
            'nama_pic' => 'Siti',
        ]);

        $response = $this->getJson('/api/perusahaan?search=PT&sort_field=nama_perusahaan&sort_order=asc&per_page=10');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Data perusahaan berhasil diambil.')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.nama_perusahaan', 'PT Alfa')
            ->assertJsonPath('data.1.nama_perusahaan', 'PT Beta');
    }

    public function test_perusahaan_can_be_created(): void
    {
        $response = $this->postJson('/api/perusahaan', [
            'nama_perusahaan' => 'PT Garuda Jaya',
            'alamat' => 'Jombang',
            'nama_pic' => 'Dimas',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Perusahaan berhasil ditambahkan.')
            ->assertJsonPath('data.nama_perusahaan', 'PT Garuda Jaya');

        $this->assertDatabaseHas('perusahaan', [
            'nama_perusahaan' => 'PT Garuda Jaya',
            'alamat' => 'Jombang',
            'nama_pic' => 'Dimas',
        ]);
    }

    public function test_perusahaan_detail_can_be_viewed(): void
    {
        $perusahaan = Perusahaan::factory()->create([
            'nama_perusahaan' => 'PT Nusantara',
            'alamat' => 'Bandung',
            'nama_pic' => 'Rina',
        ]);

        $response = $this->getJson('/api/perusahaan/'.$perusahaan->id);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Detail perusahaan berhasil diambil.')
            ->assertJsonPath('data.id', $perusahaan->id)
            ->assertJsonPath('data.nama_perusahaan', 'PT Nusantara');
    }

    public function test_perusahaan_can_be_updated(): void
    {
        $perusahaan = Perusahaan::factory()->create([
            'nama_perusahaan' => 'PT Lama',
            'alamat' => 'Malang',
            'nama_pic' => 'Andi',
        ]);

        $response = $this->putJson('/api/perusahaan/'.$perusahaan->id, [
            'nama_perusahaan' => 'PT Baru',
            'alamat' => 'Malang Barat',
            'nama_pic' => 'Dewi',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Perusahaan berhasil diperbarui.')
            ->assertJsonPath('data.nama_pic', 'Dewi');

        $this->assertDatabaseHas('perusahaan', [
            'id' => $perusahaan->id,
            'nama_perusahaan' => 'PT Baru',
            'alamat' => 'Malang Barat',
            'nama_pic' => 'Dewi',
        ]);
    }

    public function test_perusahaan_can_be_deleted(): void
    {
        $perusahaan = Perusahaan::factory()->create();

        $response = $this->deleteJson('/api/perusahaan/'.$perusahaan->id);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Perusahaan berhasil dihapus.');

        $this->assertDatabaseMissing('perusahaan', [
            'id' => $perusahaan->id,
        ]);
    }
}
