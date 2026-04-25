<?php

namespace Tests\Feature;

use App\Models\MasterData\Kategori;
use App\Models\MasterData\Produk;
use App\Models\MasterData\Supplier;
use App\Models\TransaksiPembelian\DaftarPembelanjaan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DaftarPembelanjaanSupplierApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_daftar_pembelanjaan_with_selected_supplier_items_only(): void
    {
        $includedRecord = DaftarPembelanjaan::query()->create([
            'tanggal_pesan' => '2026-04-09',
        ]);

        $excludedRecord = DaftarPembelanjaan::query()->create([
            'tanggal_pesan' => '2026-04-10',
        ]);

        $supplier = Supplier::query()->create([
            'nama' => 'PT Sumber Pangan',
            'alamat' => 'Nganjuk',
            'no_telp' => '08100000011',
            'kategori' => 'Supplier',
        ]);

        $includedRecord->items()->create([
            'supplier_id' => $supplier->id,
            'nama_barang' => 'Beras',
            'qty' => 3,
            'satuan' => 'KG',
            'stok' => 1,
            'kebutuhan' => 3,
            'nama_supplier' => 'PT Sumber Pangan',
        ]);

        $excludedRecord->items()->create([
            'supplier_id' => null,
            'nama_barang' => 'Beras',
            'qty' => 2,
            'satuan' => 'KG',
            'stok' => 1,
            'kebutuhan' => 2,
            'nama_supplier' => '',
        ]);

        $this->getJson('/api/daftar-pembelanjaan-supplier?tanggal_pesan=2026-04-09')
            ->assertOk()
            ->assertJsonPath('message', 'Data daftar pembelanjaan supplier berhasil diambil.')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $includedRecord->id)
            ->assertJsonPath('data.0.supplier_count', 1)
            ->assertJsonPath('data.0.item_count', 1);
    }

    public function test_detail_groups_items_by_supplier_from_daftar_pembelanjaan_items(): void
    {
        $record = DaftarPembelanjaan::query()->create([
            'tanggal_pesan' => '2026-04-09',
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

        $record->items()->create([
            'produk_id' => $produkBeras->id,
            'kategori_id' => $kategoriKg->id,
            'supplier_id' => $supplierA->id,
            'nama_barang' => 'Beras',
            'qty' => 5,
            'satuan' => 'KG',
            'stok' => 2,
            'kebutuhan' => 5,
            'nama_supplier' => 'PT Sumber Pangan',
        ]);

        $record->items()->create([
            'produk_id' => $produkMinyak->id,
            'kategori_id' => $kategoriLiter->id,
            'supplier_id' => $supplierB->id,
            'nama_barang' => 'Minyak Goreng',
            'qty' => 7,
            'satuan' => 'Liter',
            'stok' => 3,
            'kebutuhan' => 7,
            'nama_supplier' => 'CV Makmur Jaya',
        ]);

        $this->getJson('/api/daftar-pembelanjaan-supplier/'.$record->id)
            ->assertOk()
            ->assertJsonPath('message', 'Detail daftar pembelanjaan supplier berhasil diambil.')
            ->assertJsonPath('data.id', $record->id)
            ->assertJsonPath('data.tanggal_pesan', '2026-04-09')
            ->assertJsonCount(2, 'data.suppliers')
            ->assertJsonPath('data.suppliers.0.supplier.nama', 'PT Sumber Pangan')
            ->assertJsonPath('data.suppliers.0.items.0.nama_barang', 'Beras')
            ->assertJsonPath('data.suppliers.1.supplier.nama', 'CV Makmur Jaya')
            ->assertJsonPath('data.suppliers.1.items.0.nama_barang', 'Minyak Goreng');
    }
}
