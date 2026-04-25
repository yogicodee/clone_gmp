<?php

namespace Tests\Feature;

use App\Models\MasterData\Armada;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArmadaApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_armada_index_returns_paginated_records(): void
    {
        Armada::factory()->create([
            'nama_unit' => 'Armada Motor 01',
            'no_pol' => 'D 1234 AA',
            'jenis_kendaraan' => 'Roda 2',
        ]);

        Armada::factory()->create([
            'nama_unit' => 'Armada Mobil 01',
            'no_pol' => 'B 5678 BB',
            'jenis_kendaraan' => 'Roda 4',
        ]);

        Armada::factory()->create([
            'nama_unit' => 'Armada Mobil 02',
            'no_pol' => 'L 9012 CC',
            'jenis_kendaraan' => 'Roda 4',
        ]);

        $response = $this->getJson('/api/armada?search=armada&sort_field=nama_unit&sort_order=asc&per_page=10');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Data armada berhasil diambil.')
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('data.0.nama_unit', 'Armada Mobil 01')
            ->assertJsonPath('data.1.nama_unit', 'Armada Mobil 02')
            ->assertJsonPath('data.2.nama_unit', 'Armada Motor 01');
    }

    public function test_armada_can_be_created(): void
    {
        $response = $this->postJson('/api/armada', [
            'nama_unit' => 'Armada Operasional',
            'no_pol' => 'B 1234 CD',
            'jenis_kendaraan' => 'Roda 4',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Armada berhasil ditambahkan.')
            ->assertJsonPath('data.nama_unit', 'Armada Operasional')
            ->assertJsonPath('data.jenis_kendaraan', 'Roda 4');

        $this->assertDatabaseHas('armada', [
            'nama_unit' => 'Armada Operasional',
            'no_pol' => 'B 1234 CD',
            'jenis_kendaraan' => 'Roda 4',
        ]);
    }

    public function test_armada_rejects_invalid_vehicle_type(): void
    {
        $response = $this->postJson('/api/armada', [
            'nama_unit' => 'Pickup 02',
            'no_pol' => 'D 5678 EF',
            'jenis_kendaraan' => 'Pickup',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.jenis_kendaraan.0',
                'Jenis kendaraan hanya boleh Roda 2 atau Roda 4.'
            );
    }

    public function test_armada_detail_can_be_viewed(): void
    {
        $armada = Armada::factory()->create([
            'nama_unit' => 'Armada Khusus',
            'no_pol' => 'F 1111 ZZ',
            'jenis_kendaraan' => 'Roda 2',
        ]);

        $response = $this->getJson('/api/armada/'.$armada->id);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $armada->id)
            ->assertJsonPath('data.nama_unit', 'Armada Khusus');
    }

    public function test_armada_can_be_updated(): void
    {
        $armada = Armada::factory()->create([
            'nama_unit' => 'Armada Lama',
            'no_pol' => 'B 0001 AA',
            'jenis_kendaraan' => 'Roda 2',
        ]);

        $response = $this->putJson('/api/armada/'.$armada->id, [
            'nama_unit' => 'Armada Baru',
            'no_pol' => 'B 0002 BB',
            'jenis_kendaraan' => 'Roda 4',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Armada berhasil diperbarui.')
            ->assertJsonPath('data.no_pol', 'B 0002 BB');

        $this->assertDatabaseHas('armada', [
            'id' => $armada->id,
            'nama_unit' => 'Armada Baru',
            'no_pol' => 'B 0002 BB',
            'jenis_kendaraan' => 'Roda 4',
        ]);
    }

    public function test_armada_can_be_deleted(): void
    {
        $armada = Armada::factory()->create();

        $response = $this->deleteJson('/api/armada/'.$armada->id);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Armada berhasil dihapus.');

        $this->assertDatabaseMissing('armada', [
            'id' => $armada->id,
        ]);
    }
}
