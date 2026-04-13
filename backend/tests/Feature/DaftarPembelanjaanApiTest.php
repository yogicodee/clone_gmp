<?php

namespace Tests\Feature;

use App\Models\DaftarPembelanjaan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DaftarPembelanjaanApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_daftar_pembelanjaan_index_returns_paginated_records(): void
    {
        DaftarPembelanjaan::query()->create(['tanggal_pesan' => '2026-04-01']);
        DaftarPembelanjaan::query()->create(['tanggal_pesan' => '2026-04-02']);

        $response = $this->getJson('/api/daftar-pembelanjaan?tanggal_pesan=2026-04-02');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Data daftar pembelanjaan berhasil diambil.')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.tanggal_pesan', '2026-04-02');
    }

    public function test_daftar_pembelanjaan_can_be_created(): void
    {
        $response = $this->postJson('/api/daftar-pembelanjaan', [
            'tanggal_pesan' => '2026-04-13',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Daftar pembelanjaan berhasil ditambahkan.')
            ->assertJsonPath('data.tanggal_pesan', '2026-04-13');

        $this->assertDatabaseHas('daftar_pembelanjaan', [
            'tanggal_pesan' => '2026-04-13',
        ]);
    }

    public function test_daftar_pembelanjaan_detail_returns_items(): void
    {
        $record = DaftarPembelanjaan::query()->create(['tanggal_pesan' => '2026-04-01']);

        $record->items()->create([
            'nama_barang' => 'Beras',
            'qty' => 10,
            'satuan' => 'Kg',
            'stok' => 5,
            'kebutuhan' => 15,
            'nama_supplier' => 'PT Sumber Pangan',
        ]);

        $this->getJson('/api/daftar-pembelanjaan/'.$record->id)
            ->assertOk()
            ->assertJsonPath('data.id', $record->id)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.nama_barang', 'Beras');
    }

    public function test_daftar_pembelanjaan_can_be_updated_and_deleted(): void
    {
        $record = DaftarPembelanjaan::query()->create(['tanggal_pesan' => '2026-04-01']);

        $this->putJson('/api/daftar-pembelanjaan/'.$record->id, [
            'tanggal_pesan' => '2026-04-05',
        ])
            ->assertOk()
            ->assertJsonPath('data.tanggal_pesan', '2026-04-05');

        $this->deleteJson('/api/daftar-pembelanjaan/'.$record->id)
            ->assertOk()
            ->assertJsonPath('message', 'Daftar pembelanjaan berhasil dihapus.');

        $this->assertDatabaseMissing('daftar_pembelanjaan', [
            'id' => $record->id,
        ]);
    }
}
