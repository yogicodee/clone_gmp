<?php

namespace Tests\Feature;

use App\Models\MasterData\Armada;
use App\Models\MasterData\Karyawan;
use App\Models\MasterData\Sppg;
use App\Models\TransaksiPenjualan\SuratJalan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuratJalanApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_surat_jalan_index_returns_paginated_records(): void
    {
        $sppg = Sppg::query()->create([
            'nama_sppg' => 'SPPG Surabaya',
            'alamat' => 'Surabaya',
            'nama_yayasan' => 'Yayasan A',
            'nama_penanggungjawab' => 'Budi',
            'no_penanggungjawab' => '08123',
        ]);

        $armada = Armada::query()->create([
            'nama_unit' => 'Truck Box 01',
            'no_pol' => 'L 1234 AB',
            'jenis_kendaraan' => 'Roda 4',
        ]);

        $driver = Karyawan::query()->create([
            'nama' => 'Subandi',
            'alamat' => 'Surabaya',
            'no_hp' => '0822222222',
            'jabatan' => 'Driver',
            'tanggal_masuk' => '2026-01-02',
            'status' => 'aktif',
        ]);

        SuratJalan::query()->create([
            'nomor_surat_jalan' => 'SJ-001',
            'no_po' => 'PO-001',
            'tanggal' => '2026-04-25',
            'sppg_id' => $sppg->id,
            'armada_id' => $armada->id,
            'driver_id' => $driver->id,
            'status' => 'draft',
        ]);

        SuratJalan::query()->create([
            'nomor_surat_jalan' => 'SJ-002',
            'no_po' => 'PO-002',
            'tanggal' => '2026-04-26',
            'status' => 'selesai',
        ]);

        $response = $this->getJson('/api/surat-jalan?search=PO-001');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Data surat jalan berhasil diambil.')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nomor_surat_jalan', 'SJ-001')
            ->assertJsonPath('data.0.nama_driver', 'Subandi')
            ->assertJsonPath('data.0.armada', 'Truck Box 01')
            ->assertJsonPath('data.0.no_pol', 'L 1234 AB')
            ->assertJsonPath('data.0.nama_sppg', 'SPPG Surabaya');
    }

    public function test_surat_jalan_can_be_created_updated_and_deleted(): void
    {
        $response = $this->postJson('/api/surat-jalan', [
            'nomor_surat_jalan' => 'SJ-010',
            'no_po' => 'PO-010',
            'tanggal' => '2026-04-25',
            'status' => 'draft',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Data surat jalan berhasil ditambahkan.')
            ->assertJsonPath('data.nomor_surat_jalan', 'SJ-010');

        $id = $response->json('data.id');

        $this->putJson('/api/surat-jalan/'.$id, [
            'nomor_surat_jalan' => 'SJ-010-REV',
            'no_po' => 'PO-010-REV',
            'tanggal' => '2026-04-26',
            'status' => 'selesai',
        ])
            ->assertOk()
            ->assertJsonPath('data.nomor_surat_jalan', 'SJ-010-REV')
            ->assertJsonPath('data.status', 'selesai');

        $this->deleteJson('/api/surat-jalan/'.$id)
            ->assertOk()
            ->assertJsonPath('message', 'Data surat jalan berhasil dihapus.');

        $this->assertDatabaseMissing('surat_jalan', [
            'id' => $id,
        ]);
    }
}
