<?php

namespace Tests\Feature;

use App\Models\Kategori;
use App\Models\OrderPenawaran;
use App\Models\Produk;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DaftarPembelanjaanSupplierApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_order_penawaran_with_supplier_items_only(): void
    {
        $includedOrder = OrderPenawaran::query()->create([
            'tanggal_pesan' => '2026-04-09',
            'tanggal_dikirim' => '2026-04-12',
            'nama_pembeli' => 'SPPG BB',
            'keterangan' => 'Order masuk',
        ]);

        $excludedOrder = OrderPenawaran::query()->create([
            'tanggal_pesan' => '2026-04-10',
            'tanggal_dikirim' => '2026-04-13',
            'nama_pembeli' => 'SPPG CC',
            'keterangan' => 'Tanpa supplier',
        ]);

        $produk = Produk::query()->create([
            'sku' => 'BRG-010',
            'nama' => 'Beras',
            'kategori' => 'Kering',
            'satuan' => 'KG',
        ]);

        $kategori = Kategori::query()->create([
            'kode' => 'KG',
            'nama_satuan' => 'Kilogram',
        ]);

        $supplier = Supplier::query()->create([
            'nama' => 'PT Sumber Pangan',
            'alamat' => 'Nganjuk',
            'no_telp' => '08100000011',
            'kategori' => 'Supplier',
        ]);

        $includedOrder->items()->create([
            'produk_id' => $produk->id,
            'kategori_id' => $kategori->id,
            'supplier_id' => $supplier->id,
            'nama_barang' => 'Beras',
            'qty' => 3,
            'satuan' => 'KG',
            'harga_satuan' => 9000,
            'keterangan' => 'Lunas',
        ]);

        $excludedOrder->items()->create([
            'produk_id' => $produk->id,
            'kategori_id' => $kategori->id,
            'supplier_id' => null,
            'nama_barang' => 'Beras',
            'qty' => 2,
            'satuan' => 'KG',
            'harga_satuan' => 8000,
            'keterangan' => 'Belum',
        ]);

        $this->getJson('/api/daftar-pembelanjaan-supplier?tanggal_pesan=2026-04-09')
            ->assertOk()
            ->assertJsonPath('message', 'Data daftar pembelanjaan supplier berhasil diambil.')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $includedOrder->id)
            ->assertJsonPath('data.0.supplier_count', 1)
            ->assertJsonPath('data.0.item_count', 1);
    }

    public function test_detail_groups_items_by_supplier_from_order_penawaran_items(): void
    {
        $order = OrderPenawaran::query()->create([
            'tanggal_pesan' => '2026-04-09',
            'tanggal_dikirim' => '2026-04-12',
            'nama_pembeli' => 'SPPG BB',
            'keterangan' => 'Order masuk',
        ]);

        $produkBeras = Produk::query()->create([
            'sku' => 'BRG-010',
            'nama' => 'Beras',
            'kategori' => 'Kering',
            'satuan' => 'KG',
        ]);
        $produkMinyak = Produk::query()->create([
            'sku' => 'BRG-011',
            'nama' => 'Minyak Goreng',
            'kategori' => 'Basah',
            'satuan' => 'Liter',
        ]);

        $kategoriKg = Kategori::query()->create([
            'kode' => 'KG',
            'nama_satuan' => 'Kilogram',
        ]);
        $kategoriLiter = Kategori::query()->create([
            'kode' => 'Liter',
            'nama_satuan' => 'Liter',
        ]);

        $supplierA = Supplier::query()->create([
            'nama' => 'PT Sumber Pangan',
            'alamat' => 'Nganjuk',
            'no_telp' => '08100000012',
            'kategori' => 'Supplier',
        ]);
        $supplierB = Supplier::query()->create([
            'nama' => 'CV Makmur Jaya',
            'alamat' => 'Jombang',
            'no_telp' => '08100000013',
            'kategori' => 'Supplier',
        ]);

        $order->items()->create([
            'produk_id' => $produkBeras->id,
            'kategori_id' => $kategoriKg->id,
            'supplier_id' => $supplierA->id,
            'nama_barang' => 'Beras',
            'qty' => 5,
            'satuan' => 'KG',
            'harga_satuan' => 9000,
            'keterangan' => 'Lunas',
        ]);

        $order->items()->create([
            'produk_id' => $produkMinyak->id,
            'kategori_id' => $kategoriLiter->id,
            'supplier_id' => $supplierB->id,
            'nama_barang' => 'Minyak Goreng',
            'qty' => 7,
            'satuan' => 'Liter',
            'harga_satuan' => 12000,
            'keterangan' => 'Proses',
        ]);

        $this->getJson('/api/daftar-pembelanjaan-supplier/'.$order->id)
            ->assertOk()
            ->assertJsonPath('message', 'Detail daftar pembelanjaan supplier berhasil diambil.')
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.tanggal_pesan', '2026-04-09')
            ->assertJsonCount(2, 'data.suppliers')
            ->assertJsonPath('data.suppliers.0.supplier.nama', 'PT Sumber Pangan')
            ->assertJsonPath('data.suppliers.0.items.0.nama_barang', 'Beras')
            ->assertJsonPath('data.suppliers.1.supplier.nama', 'CV Makmur Jaya')
            ->assertJsonPath('data.suppliers.1.items.0.nama_barang', 'Minyak Goreng');
    }
}
