<?php

namespace Tests\Feature;

use App\Models\MasterData\Armada;
use App\Models\MasterData\Karyawan;
use App\Models\MasterData\Sppg;
use App\Models\TransaksiPenjualan\SuratJalan;
use App\Models\TransaksiPenjualan\TandaTerima;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TandaTerimaApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_tanda_terima_index_returns_paginated_records(): void
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

        $akuntan = Karyawan::query()->create([
            'nama' => 'Tanti Dwi',
            'alamat' => 'Surabaya',
            'no_hp' => '0811111111',
            'jabatan' => 'Akuntan',
            'tanggal_masuk' => '2026-01-01',
            'status' => 'aktif',
        ]);

        $driver = Karyawan::query()->create([
            'nama' => 'Subandi',
            'alamat' => 'Surabaya',
            'no_hp' => '0822222222',
            'jabatan' => 'Driver',
            'tanggal_masuk' => '2026-01-02',
            'status' => 'aktif',
        ]);

        TandaTerima::query()->create([
            'nomor_tanda_terima' => 'TT-001',
            'nomor_surat_jalan' => 'SJ-001',
            'no_po' => 'PO-001',
            'tanggal' => '2026-04-25',
            'sppg_id' => $sppg->id,
            'armada_id' => $armada->id,
            'akuntan_id' => $akuntan->id,
            'driver_id' => $driver->id,
            'status' => 'draft',
        ]);

        TandaTerima::query()->create([
            'nomor_tanda_terima' => 'TT-002',
            'nomor_surat_jalan' => 'SJ-002',
            'no_po' => 'PO-002',
            'tanggal' => '2026-04-26',
            'status' => 'selesai',
        ]);

        $response = $this->getJson('/api/tanda-terima?search=PO-001');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Data tanda terima berhasil diambil.')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nomor_tanda_terima', 'TT-001')
            ->assertJsonPath('data.0.nama_akuntan', 'Tanti Dwi')
            ->assertJsonPath('data.0.nama_driver', 'Subandi')
            ->assertJsonPath('data.0.armada', 'Truck Box 01')
            ->assertJsonPath('data.0.no_pol', 'L 1234 AB')
            ->assertJsonPath('data.0.nama_sppg', 'SPPG Surabaya');
    }

    public function test_tanda_terima_can_be_created_updated_and_deleted(): void
    {
        $suratJalan = SuratJalan::query()->create([
            'nomor_surat_jalan' => 'SJ-010',
            'no_po' => 'PO-010',
            'tanggal' => '2026-04-25',
            'status' => 'draft',
        ]);

        $response = $this->postJson('/api/tanda-terima', [
            'nomor_tanda_terima' => 'TT-010',
            'nomor_surat_jalan' => $suratJalan->nomor_surat_jalan,
            'tanggal' => '2026-04-25',
            'status' => 'draft',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Data tanda terima berhasil ditambahkan.')
            ->assertJsonPath('data.nomor_surat_jalan', 'SJ-010')
            ->assertJsonPath('data.no_po', 'PO-010');

        $id = $response->json('data.id');

        $this->putJson('/api/tanda-terima/'.$id, [
            'nomor_tanda_terima' => 'TT-010-REV',
            'nomor_surat_jalan' => 'SJ-010',
            'no_po' => 'PO-010-REV',
            'tanggal' => '2026-04-25',
            'status' => 'selesai',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['no_po']);

        $this->putJson('/api/tanda-terima/'.$id, [
            'nomor_tanda_terima' => 'TT-010-REV',
            'nomor_surat_jalan' => 'SJ-010',
            'no_po' => 'PO-010',
            'tanggal' => '2026-04-25',
            'status' => 'selesai',
        ])
            ->assertOk()
            ->assertJsonPath('data.nomor_tanda_terima', 'TT-010-REV')
            ->assertJsonPath('data.status', 'selesai');

        $this->deleteJson('/api/tanda-terima/'.$id)
            ->assertOk()
            ->assertJsonPath('message', 'Data tanda terima berhasil dihapus.');

        $this->assertDatabaseMissing('tanda_terima', [
            'id' => $id,
        ]);
    }

    public function test_tanda_terima_rejects_unknown_or_inconsistent_surat_jalan(): void
    {
        $this->postJson('/api/tanda-terima', [
            'nomor_tanda_terima' => 'TT-404',
            'nomor_surat_jalan' => 'SJ-404',
            'tanggal' => '2026-04-25',
            'status' => 'draft',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['nomor_surat_jalan']);

        SuratJalan::query()->create([
            'nomor_surat_jalan' => 'SJ-050',
            'no_po' => 'PO-050',
            'tanggal' => '2026-04-25',
            'status' => 'draft',
        ]);

        $this->postJson('/api/tanda-terima', [
            'nomor_tanda_terima' => 'TT-050',
            'nomor_surat_jalan' => 'SJ-050',
            'tanggal' => '2026-04-26',
            'status' => 'draft',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['tanggal']);
    }
}
