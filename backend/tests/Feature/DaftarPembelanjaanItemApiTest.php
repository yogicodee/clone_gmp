<?php

namespace Tests\Feature;

use App\Models\MasterData\Supplier;
use App\Models\TransaksiPembelian\DaftarPembelanjaan;
use App\Models\TransaksiPembelian\DaftarPembelanjaanItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DaftarPembelanjaanItemApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_daftar_pembelanjaan_item_index_returns_paginated_records(): void
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

        $record->items()->create([
            'nama_barang' => 'Minyak Goreng',
            'qty' => 20,
            'satuan' => 'Liter',
            'stok' => 10,
            'kebutuhan' => 25,
            'nama_supplier' => 'CV Makmur Jaya',
        ]);

        $response = $this->getJson('/api/daftar-pembelanjaan/'.$record->id.'/items?search=makmur');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Data detail pembelanjaan berhasil diambil.')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nama_barang', 'Minyak Goreng');
    }

    public function test_daftar_pembelanjaan_item_can_be_created(): void
    {
        $record = DaftarPembelanjaan::query()->create(['tanggal_pesan' => '2026-04-01']);

        $response = $this->postJson('/api/daftar-pembelanjaan/'.$record->id.'/items', [
            'nama_barang' => 'Gula Pasir',
            'qty' => 12,
            'satuan' => 'Kg',
            'stok' => 4,
            'kebutuhan' => 16,
            'nama_supplier' => 'UD Manis Jaya',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Detail pembelanjaan berhasil ditambahkan.')
            ->assertJsonPath('data.nama_barang', 'Gula Pasir');

        $this->assertDatabaseHas('daftar_pembelanjaan_items', [
            'daftar_pembelanjaan_id' => $record->id,
            'nama_barang' => 'Gula Pasir',
        ]);
    }

    public function test_daftar_pembelanjaan_item_detail_update_and_delete_work(): void
    {
        $record = DaftarPembelanjaan::query()->create(['tanggal_pesan' => '2026-04-01']);
        $supplier = Supplier::query()->create([
            'nama' => 'PT Sumber Pangan Baru',
            'alamat' => 'Nganjuk',
            'no_telp' => '08100000099',
            'kategori' => 'Supplier',
        ]);

        $item = $record->items()->create([
            'nama_barang' => 'Beras',
            'qty' => 10,
            'satuan' => 'Kg',
            'stok' => 5,
            'kebutuhan' => 15,
            'nama_supplier' => 'PT Sumber Pangan',
        ]);

        $this->getJson('/api/daftar-pembelanjaan/'.$record->id.'/items/'.$item->id)
            ->assertOk()
            ->assertJsonPath('data.nama_barang', 'Beras');

        $this->putJson('/api/daftar-pembelanjaan/'.$record->id.'/items/'.$item->id, [
            'supplier_id' => $supplier->id,
            'nama_barang' => 'Beras Premium',
            'qty' => 15,
            'satuan' => 'Kg',
            'stok' => 4,
            'kebutuhan' => 19,
        ])
            ->assertOk()
            ->assertJsonPath('data.nama_barang', 'Beras Premium')
            ->assertJsonPath('data.supplier_id', $supplier->id)
            ->assertJsonPath('data.nama_supplier', 'PT Sumber Pangan Baru');

        $this->deleteJson('/api/daftar-pembelanjaan/'.$record->id.'/items/'.$item->id)
            ->assertOk()
            ->assertJsonPath('message', 'Detail pembelanjaan berhasil dihapus.');

        $this->assertDatabaseMissing('daftar_pembelanjaan_items', [
            'id' => $item->id,
        ]);
    }

    public function test_item_from_other_record_returns_not_found(): void
    {
        $recordA = DaftarPembelanjaan::query()->create(['tanggal_pesan' => '2026-04-01']);
        $recordB = DaftarPembelanjaan::query()->create(['tanggal_pesan' => '2026-04-02']);

        $item = DaftarPembelanjaanItem::query()->create([
            'daftar_pembelanjaan_id' => $recordB->id,
            'nama_barang' => 'Beras',
            'qty' => 10,
            'satuan' => 'Kg',
            'stok' => 5,
            'kebutuhan' => 15,
            'nama_supplier' => 'PT Sumber Pangan',
        ]);

        $this->getJson('/api/daftar-pembelanjaan/'.$recordA->id.'/items/'.$item->id)
            ->assertNotFound();
    }
}
