<?php

namespace Tests\Feature;

use App\Models\MasterData\Karyawan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KaryawanApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_karyawan_index_returns_paginated_records(): void
    {
        Karyawan::factory()->create([
            'nama' => 'Budi',
            'alamat' => 'Bandung',
            'no_hp' => '08123456789',
            'jabatan' => 'Admin',
            'tanggal_masuk' => '2023-01-10',
            'status' => 'aktif',
        ]);

        Karyawan::factory()->create([
            'nama' => 'Andi',
            'alamat' => 'Surabaya',
            'no_hp' => '08345678901',
            'jabatan' => 'Gudang',
            'tanggal_masuk' => '2022-06-15',
            'status' => 'nonaktif',
        ]);

        Karyawan::factory()->create([
            'nama' => 'Siti',
            'alamat' => 'Jakarta',
            'no_hp' => '08234567890',
            'jabatan' => 'Gudang',
            'tanggal_masuk' => '2022-06-15',
            'status' => 'aktif',
        ]);

        $response = $this->getJson('/api/karyawan?search=gudang&sort_field=nama&sort_order=asc&per_page=10');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Data karyawan berhasil diambil.')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.nama', 'Andi')
            ->assertJsonPath('data.1.nama', 'Siti');
    }

    public function test_karyawan_can_be_created(): void
    {
        $response = $this->postJson('/api/karyawan', [
            'nama' => 'Rina',
            'alamat' => 'Bogor',
            'no_hp' => '+62 (812) 3456-789',
            'jabatan' => 'Admin',
            'tanggal_masuk' => '2024-01-01',
            'status' => 'non aktif',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Karyawan berhasil ditambahkan.')
            ->assertJsonPath('data.nama', 'Rina')
            ->assertJsonPath('data.status', 'non aktif');

        $this->assertDatabaseHas('karyawan', [
            'nama' => 'Rina',
            'alamat' => 'Bogor',
            'no_hp' => '+62 (812) 3456-789',
            'jabatan' => 'Admin',
            'tanggal_masuk' => '2024-01-01',
            'status' => 'nonaktif',
        ]);
    }

    public function test_karyawan_phone_rejects_invalid_characters(): void
    {
        $response = $this->postJson('/api/karyawan', [
            'nama' => 'Doni',
            'alamat' => 'Bandung',
            'no_hp' => '08abc12345',
            'jabatan' => 'Gudang',
            'tanggal_masuk' => '2024-01-01',
            'status' => 'aktif',
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath(
                'errors.no_hp.0',
                'No HP hanya boleh berisi angka dan karakter khusus tertentu.'
            );
    }

    public function test_karyawan_detail_can_be_viewed(): void
    {
        $karyawan = Karyawan::factory()->create([
            'nama' => 'Farah',
        ]);

        $response = $this->getJson('/api/karyawan/'.$karyawan->id);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $karyawan->id)
            ->assertJsonPath('data.nama', 'Farah');
    }

    public function test_karyawan_can_be_updated(): void
    {
        $karyawan = Karyawan::factory()->create([
            'nama' => 'Tono',
            'status' => 'aktif',
        ]);

        $response = $this->putJson('/api/karyawan/'.$karyawan->id, [
            'nama' => 'Tina',
            'alamat' => 'Semarang',
            'no_hp' => '081200009999',
            'jabatan' => 'Logistik',
            'tanggal_masuk' => '2024-02-02',
            'status' => 'non aktif',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Karyawan berhasil diperbarui.')
            ->assertJsonPath('data.nama', 'Tina')
            ->assertJsonPath('data.status', 'non aktif');

        $this->assertDatabaseHas('karyawan', [
            'id' => $karyawan->id,
            'nama' => 'Tina',
            'status' => 'nonaktif',
        ]);
    }

    public function test_karyawan_can_be_deleted(): void
    {
        $karyawan = Karyawan::factory()->create();

        $response = $this->deleteJson('/api/karyawan/'.$karyawan->id);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Karyawan berhasil dihapus.');

        $this->assertDatabaseMissing('karyawan', [
            'id' => $karyawan->id,
        ]);
    }
}
