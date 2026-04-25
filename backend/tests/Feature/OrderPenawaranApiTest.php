<?php

namespace Tests\Feature;

use App\Models\TransaksiPembelian\OrderPenawaran;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderPenawaranApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_penawaran_index_returns_paginated_records(): void
    {
        OrderPenawaran::query()->create([
            'tanggal_pesan' => '2026-04-01',
            'tanggal_dikirim' => '2026-04-03',
            'nama_pembeli' => 'Budi',
            'keterangan' => 'Order cepat',
        ]);

        OrderPenawaran::query()->create([
            'tanggal_pesan' => '2026-04-02',
            'tanggal_dikirim' => '2026-04-05',
            'nama_pembeli' => 'Siti',
            'keterangan' => 'Prioritas tinggi',
        ]);

        $response = $this->getJson('/api/order-penawaran?search=prioritas&per_page=10');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Data order penawaran berhasil diambil.')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nama_pembeli', 'Siti');
    }

    public function test_order_penawaran_can_be_created(): void
    {
        $response = $this->postJson('/api/order-penawaran', [
            'tanggal_pesan' => '2026-04-11',
            'tanggal_dikirim' => '2026-04-13',
            'nama_pembeli' => 'Rina',
            'keterangan' => 'Pengadaan mingguan',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Order penawaran berhasil ditambahkan.')
            ->assertJsonPath('data.nama_pembeli', 'Rina');

        $this->assertDatabaseHas('order_penawaran', [
            'nama_pembeli' => 'Rina',
            'keterangan' => 'Pengadaan mingguan',
        ]);
    }

    public function test_order_penawaran_detail_returns_items(): void
    {
        $order = OrderPenawaran::query()->create([
            'tanggal_pesan' => '2026-04-01',
            'tanggal_dikirim' => '2026-04-03',
            'nama_pembeli' => 'Budi',
            'keterangan' => 'Order cepat',
        ]);

        $order->items()->create([
            'nama_barang' => 'Semen',
            'qty' => 100,
            'satuan' => 'Zak',
            'harga_satuan' => 65000,
            'keterangan' => 'Proyek A',
        ]);

        $response = $this->getJson('/api/order-penawaran/'.$order->id);

        $response
            ->assertOk()
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.nama_barang', 'Semen');
    }

    public function test_order_penawaran_can_be_updated_and_deleted(): void
    {
        $order = OrderPenawaran::query()->create([
            'tanggal_pesan' => '2026-04-01',
            'tanggal_dikirim' => '2026-04-03',
            'nama_pembeli' => 'Budi',
            'keterangan' => 'Order cepat',
        ]);

        $this->putJson('/api/order-penawaran/'.$order->id, [
            'tanggal_pesan' => '2026-04-02',
            'tanggal_dikirim' => '2026-04-04',
            'nama_pembeli' => 'Budi Santoso',
            'keterangan' => 'Revisi order',
        ])
            ->assertOk()
            ->assertJsonPath('data.nama_pembeli', 'Budi Santoso');

        $this->deleteJson('/api/order-penawaran/'.$order->id)
            ->assertOk()
            ->assertJsonPath('message', 'Order penawaran berhasil dihapus.');

        $this->assertDatabaseMissing('order_penawaran', [
            'id' => $order->id,
        ]);
    }
}
