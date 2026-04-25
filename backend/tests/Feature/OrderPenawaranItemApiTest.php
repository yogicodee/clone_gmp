<?php

namespace Tests\Feature;

use App\Models\TransaksiPembelian\OrderPenawaran;
use App\Models\TransaksiPembelian\OrderPenawaranItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderPenawaranItemApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_penawaran_item_index_returns_paginated_records(): void
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

        $order->items()->create([
            'nama_barang' => 'Pasir',
            'qty' => 5,
            'satuan' => 'Truk',
            'harga_satuan' => 800000,
            'keterangan' => 'Proyek B',
        ]);

        $response = $this->getJson('/api/order-penawaran/'.$order->id.'/items?search=pasir');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Data item order penawaran berhasil diambil.')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nama_barang', 'Pasir');
    }

    public function test_order_penawaran_item_can_be_created(): void
    {
        $order = OrderPenawaran::query()->create([
            'tanggal_pesan' => '2026-04-01',
            'tanggal_dikirim' => '2026-04-03',
            'nama_pembeli' => 'Budi',
            'keterangan' => 'Order cepat',
        ]);

        $response = $this->postJson('/api/order-penawaran/'.$order->id.'/items', [
            'nama_barang' => 'Batu Split',
            'qty' => 10,
            'satuan' => 'Truk',
            'harga_satuan' => 750000,
            'keterangan' => 'Proyek C',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Item order penawaran berhasil ditambahkan.')
            ->assertJsonPath('data.nama_barang', 'Batu Split');

        $this->assertDatabaseHas('order_penawaran_items', [
            'order_penawaran_id' => $order->id,
            'nama_barang' => 'Batu Split',
        ]);
    }

    public function test_order_penawaran_item_detail_update_and_delete_work(): void
    {
        $order = OrderPenawaran::query()->create([
            'tanggal_pesan' => '2026-04-01',
            'tanggal_dikirim' => '2026-04-03',
            'nama_pembeli' => 'Budi',
            'keterangan' => 'Order cepat',
        ]);

        $item = $order->items()->create([
            'nama_barang' => 'Semen',
            'qty' => 100,
            'satuan' => 'Zak',
            'harga_satuan' => 65000,
            'keterangan' => 'Proyek A',
        ]);

        $this->getJson('/api/order-penawaran/'.$order->id.'/items/'.$item->id)
            ->assertOk()
            ->assertJsonPath('data.id', $item->id)
            ->assertJsonPath('data.nama_barang', 'Semen');

        $this->putJson('/api/order-penawaran/'.$order->id.'/items/'.$item->id, [
            'nama_barang' => 'Semen Curah',
            'qty' => 120,
            'satuan' => 'Zak',
            'harga_satuan' => 70000,
            'keterangan' => 'Proyek revisi',
        ])
            ->assertOk()
            ->assertJsonPath('data.nama_barang', 'Semen Curah');

        $this->deleteJson('/api/order-penawaran/'.$order->id.'/items/'.$item->id)
            ->assertOk()
            ->assertJsonPath('message', 'Item order penawaran berhasil dihapus.');

        $this->assertDatabaseMissing('order_penawaran_items', [
            'id' => $item->id,
        ]);
    }

    public function test_item_from_other_order_returns_not_found(): void
    {
        $orderA = OrderPenawaran::query()->create([
            'tanggal_pesan' => '2026-04-01',
            'tanggal_dikirim' => '2026-04-03',
            'nama_pembeli' => 'Budi',
            'keterangan' => 'Order cepat',
        ]);

        $orderB = OrderPenawaran::query()->create([
            'tanggal_pesan' => '2026-04-02',
            'tanggal_dikirim' => '2026-04-04',
            'nama_pembeli' => 'Siti',
            'keterangan' => 'Order lain',
        ]);

        $item = OrderPenawaranItem::query()->create([
            'order_penawaran_id' => $orderB->id,
            'nama_barang' => 'Pasir',
            'qty' => 5,
            'satuan' => 'Truk',
            'harga_satuan' => 800000,
            'keterangan' => 'Proyek B',
        ]);

        $this->getJson('/api/order-penawaran/'.$orderA->id.'/items/'.$item->id)
            ->assertNotFound();
    }
}
