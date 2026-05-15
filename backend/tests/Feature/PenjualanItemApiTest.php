<?php

namespace Tests\Feature;

use App\Models\MasterData\Gudang;
use App\Models\TransaksiPembelian\OrderPenawaran;
use App\Models\TransaksiPenjualan\Penjualan;
use App\Models\User;
use App\Models\WarehouseSystem\WarehouseStokBasah;
use App\Models\WarehouseSystem\WarehouseStokKering;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenjualanItemApiTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string, string> */
    private array $headers;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::query()->create([
            'nama' => 'Super Admin Demo',
            'name' => 'Super Admin Demo',
            'email' => 'superadmin.demo@gmp.local',
            'password' => 'rahasia123',
            'role' => 'super_admin',
        ]);

        $this->headers = [
            'Authorization' => 'Bearer '.$user->issueApiToken(),
            'Accept' => 'application/json',
        ];
    }

    public function test_opsi_barang_only_returns_items_with_same_shipping_date(): void
    {
        $penjualan = Penjualan::query()->create([
            'order_penawaran_id' => null,
            'kode_penjualan' => 'TRX-001',
            'tanggal' => '2026-04-25',
            'status' => 'draft',
            'total_harga' => 0,
        ]);

        $orderMatch = OrderPenawaran::query()->create([
            'tanggal_pesan' => '2026-04-20',
            'tanggal_dikirim' => '2026-04-25',
            'nama_pembeli' => 'SPPG A',
            'keterangan' => 'Sama tanggal kirim',
        ]);

        $orderOther = OrderPenawaran::query()->create([
            'tanggal_pesan' => '2026-04-21',
            'tanggal_dikirim' => '2026-04-26',
            'nama_pembeli' => 'SPPG B',
            'keterangan' => 'Tanggal beda',
        ]);

        $orderMatch->items()->create([
            'nama_barang' => 'Pasir',
            'qty' => 5,
            'satuan' => 'Kg',
            'harga_satuan' => 12000,
            'keterangan' => 'Masuk opsi',
        ]);

        $orderOther->items()->create([
            'nama_barang' => 'Semen',
            'qty' => 10,
            'satuan' => 'Zak',
            'harga_satuan' => 65000,
            'keterangan' => 'Tidak masuk opsi',
        ]);

        $response = $this->getJson('/api/penjualan/'.$penjualan->id.'/opsi-barang', $this->headers);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Opsi barang penjualan berhasil diambil.')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nama_barang', 'Pasir');
    }

    public function test_penjualan_item_uses_order_penawaran_item_price_and_recalculates_total(): void
    {
        $gudang = Gudang::query()->create([
            'nama_gudang' => 'Gudang Penjualan',
            'alamat' => 'Jl. Melati',
            'nama_pic' => 'Budi',
            'no_pic' => '08123456789',
        ]);

        $order = OrderPenawaran::query()->create([
            'tanggal_pesan' => '2026-04-20',
            'tanggal_dikirim' => '2026-04-25',
            'nama_pembeli' => 'SPPG A',
            'keterangan' => 'Bisa dijual',
        ]);

        $penjualan = Penjualan::query()->create([
            'order_penawaran_id' => $order->id,
            'kode_penjualan' => 'TRX-002',
            'tanggal' => '2026-04-25',
            'status' => 'draft',
            'total_harga' => 0,
        ]);

        $sourceItem = $order->items()->create([
            'nama_barang' => 'Pasir',
            'qty' => 5,
            'satuan' => 'Kg',
            'harga_satuan' => 12000,
            'keterangan' => 'Harga sumber',
        ]);

        $createResponse = $this->postJson('/api/penjualan/'.$penjualan->id.'/items', [
            'order_penawaran_item_id' => $sourceItem->id,
            'gudang_id' => $gudang->id,
            'qty' => 3,
        ], $this->headers);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.nama_barang', 'Pasir')
            ->assertJsonPath('data.harga_satuan', '12000.00')
            ->assertJsonPath('data.total_harga', '36000.00')
            ->assertJsonPath('data.gudang.nama_gudang', 'Gudang Penjualan');

        $itemId = $createResponse->json('data.id');

        $this->assertDatabaseHas('penjualan', [
            'id' => $penjualan->id,
            'total_harga' => '36000.00',
        ]);

        $this->putJson('/api/penjualan/'.$penjualan->id.'/items/'.$itemId, [
            'order_penawaran_item_id' => $sourceItem->id,
            'gudang_id' => $gudang->id,
            'qty' => 4,
        ], $this->headers)
            ->assertOk()
            ->assertJsonPath('data.total_harga', '48000.00');

        $this->assertDatabaseHas('penjualan', [
            'id' => $penjualan->id,
            'total_harga' => '48000.00',
        ]);

        $this->deleteJson('/api/penjualan/'.$penjualan->id.'/items/'.$itemId, [], $this->headers)
            ->assertOk()
            ->assertJsonPath('message', 'Item penjualan berhasil dihapus.');

        $this->assertDatabaseHas('penjualan', [
            'id' => $penjualan->id,
            'total_harga' => '0.00',
        ]);
    }

    public function test_penjualan_item_index_falls_back_to_order_penawaran_detail_when_manual_items_empty(): void
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

        $order->items()->create([
            'nama_barang' => 'Indomie Ayam Bawang',
            'qty' => 5,
            'satuan' => 'PCS',
            'harga_satuan' => 3000,
            'keterangan' => null,
        ]);

        $penjualan = Penjualan::query()->create([
            'order_penawaran_id' => $order->id,
            'kode_penjualan' => 'TRX-OP-0001',
            'tanggal' => '2026-04-30',
            'status' => 'draft',
            'total_harga' => 165000,
        ]);

        $response = $this->getJson('/api/penjualan/'.$penjualan->id.'/items', $this->headers);

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.nama_barang', 'Indomie Ayam Bawang')
            ->assertJsonPath('data.0.qty', '50.00')
            ->assertJsonPath('data.0.harga_satuan', '3000.00')
            ->assertJsonPath('data.0.total_harga', '150000.00');
    }

    public function test_penjualan_item_index_includes_stock_and_status_from_warehouse_stock(): void
    {
        $gudang = Gudang::query()->create([
            'nama_gudang' => 'Gudang Penjualan',
            'alamat' => 'Jl. Melati',
            'nama_pic' => 'Budi',
            'no_pic' => '08123456789',
        ]);

        $order = OrderPenawaran::query()->create([
            'tanggal_pesan' => '2026-04-20',
            'tanggal_dikirim' => '2026-04-25',
            'nama_pembeli' => 'SPPG A',
            'keterangan' => 'Bisa dijual',
        ]);

        $sourceItem = $order->items()->create([
            'nama_barang' => 'Kentang',
            'qty' => 5,
            'satuan' => 'Kg',
            'harga_satuan' => 12000,
            'keterangan' => null,
        ]);

        $penjualan = Penjualan::query()->create([
            'order_penawaran_id' => $order->id,
            'kode_penjualan' => 'TRX-002',
            'tanggal' => '2026-04-25',
            'status' => 'draft',
            'total_harga' => 0,
        ]);

        $penjualan->items()->create([
            'order_penawaran_item_id' => $sourceItem->id,
            'produk_id' => $sourceItem->produk_id,
            'gudang_id' => $gudang->id,
            'nama_barang' => 'Kentang',
            'qty' => 7,
            'satuan' => 'Kg',
            'harga_satuan' => 12000,
            'total_harga' => 84000,
        ]);

        WarehouseStokBasah::query()->create([
            'warehouse_inbound_id' => null,
            'gudang_id' => $gudang->id,
            'nama_barang' => 'Kentang',
            'qty' => 4,
            'satuan_terkecil' => 'Kg',
            'harga_beli' => 10000,
        ]);

        WarehouseStokKering::query()->create([
            'warehouse_inbound_id' => null,
            'gudang_id' => $gudang->id,
            'nama_barang' => 'Kentang',
            'qty' => 2,
            'satuan_terkecil' => 'Kg',
            'harga_beli' => 11000,
        ]);

        $response = $this->getJson('/api/penjualan/'.$penjualan->id.'/items', $this->headers);

        $response
            ->assertOk()
            ->assertJsonPath('data.0.stok_tersedia', '6.00')
            ->assertJsonPath('data.0.status_stok', 'pending');
    }
}
