<?php

namespace Tests\Feature;

use App\Models\TransaksiPembelian\OrderPenawaran;
use App\Models\TransaksiPenjualan\Penjualan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenjualanApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_penjualan_index_returns_paginated_records(): void
    {
        Penjualan::query()->create([
            'kode_penjualan' => 'TRX-001',
            'tanggal' => '2026-04-21',
            'status' => 'selesai',
            'total_harga' => 125000,
        ]);

        Penjualan::query()->create([
            'kode_penjualan' => 'TRX-002',
            'tanggal' => '2026-04-22',
            'status' => 'draft',
            'total_harga' => 0,
        ]);

        $response = $this->getJson('/api/penjualan?search=TRX-002');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Data penjualan berhasil diambil.')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.kode_penjualan', 'TRX-002');
    }

    public function test_penjualan_can_be_created_updated_and_deleted(): void
    {
        $createResponse = $this->postJson('/api/penjualan', [
            'kode_penjualan' => 'TRX-010',
            'tanggal' => '2026-04-23',
            'status' => 'draft',
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('message', 'Data penjualan berhasil ditambahkan.')
            ->assertJsonPath('data.total_harga', '0.00');

        $id = $createResponse->json('data.id');

        $this->putJson('/api/penjualan/'.$id, [
            'kode_penjualan' => 'TRX-010-REV',
            'tanggal' => '2026-04-24',
            'status' => 'selesai',
        ])
            ->assertOk()
            ->assertJsonPath('data.kode_penjualan', 'TRX-010-REV')
            ->assertJsonPath('data.status', 'selesai');

        $this->deleteJson('/api/penjualan/'.$id)
            ->assertOk()
            ->assertJsonPath('message', 'Data penjualan berhasil dihapus.');

        $this->assertDatabaseMissing('penjualan', [
            'id' => $id,
        ]);
    }

    public function test_penjualan_index_syncs_header_from_order_penawaran_shipping_date(): void
    {
        $order = OrderPenawaran::query()->create([
            'tanggal_pesan' => '2026-04-29',
            'tanggal_dikirim' => '2026-04-30',
            'nama_pembeli' => 'Toko A',
            'keterangan' => 'dummy',
        ]);

        $order->items()->create([
            'nama_barang' => 'Indomie Ayam Bawang',
            'qty' => 50,
            'satuan' => 'PCS',
            'harga_satuan' => 3000,
            'keterangan' => null,
        ]);

        $response = $this->getJson('/api/penjualan');

        $response
            ->assertOk()
            ->assertJsonPath('data.0.order_penawaran_id', $order->id)
            ->assertJsonPath('data.0.tanggal', '2026-04-30')
            ->assertJsonPath('data.0.total_harga', '150000.00');

        $this->assertDatabaseHas('penjualan', [
            'order_penawaran_id' => $order->id,
            'tanggal' => '2026-04-30',
        ]);
    }
}
