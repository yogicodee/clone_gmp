<?php

namespace Tests\Feature;

use App\Models\MasterData\Gudang;
use App\Models\TransaksiPenjualan\Penjualan;
use App\Models\TransaksiPenjualan\SuratJalan;
use App\Models\TransaksiPenjualan\TandaTerima;
use App\Models\TransaksiPenjualan\TandaTerimaItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TandaTerimaItemApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_opsi_barang_returns_items_from_matching_surat_jalan(): void
    {
        $gudang = Gudang::query()->create([
            'nama_gudang' => 'Gudang Penjualan',
            'alamat' => 'Jl. Melati',
            'nama_pic' => 'Budi',
            'no_pic' => '08123456789',
        ]);

        $penjualan = Penjualan::query()->create([
            'kode_penjualan' => 'SJ-001',
            'tanggal' => '2026-04-25',
            'status' => 'draft',
            'total_harga' => 0,
        ]);

        $sourceItem = $penjualan->items()->create([
            'gudang_id' => $gudang->id,
            'nama_barang' => 'Pasir',
            'qty' => 3,
            'satuan' => 'Kg',
            'harga_satuan' => 12000,
            'total_harga' => 36000,
        ]);

        $suratJalan = SuratJalan::query()->create([
            'nomor_surat_jalan' => 'SJ-001',
            'tanggal' => '2026-04-25',
            'status' => 'draft',
        ]);

        $suratJalan->items()->create([
            'penjualan_item_id' => $sourceItem->id,
            'nama_barang' => $sourceItem->nama_barang,
            'qty' => $sourceItem->qty,
            'satuan' => $sourceItem->satuan,
            'keterangan' => 'Dikirim',
        ]);

        $tandaTerima = TandaTerima::query()->create([
            'nomor_tanda_terima' => 'TT-001',
            'nomor_surat_jalan' => 'SJ-001',
            'no_po' => 'PO-001',
            'tanggal' => '2026-04-25',
            'status' => 'draft',
        ]);

        $response = $this->getJson('/api/tanda-terima/'.$tandaTerima->id.'/opsi-barang');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Opsi barang tanda terima berhasil diambil.')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nama_barang', 'Pasir');
    }

    public function test_tanda_terima_item_can_be_created_updated_and_deleted(): void
    {
        $gudang = Gudang::query()->create([
            'nama_gudang' => 'Gudang Penjualan',
            'alamat' => 'Jl. Melati',
            'nama_pic' => 'Budi',
            'no_pic' => '08123456789',
        ]);

        $penjualan = Penjualan::query()->create([
            'kode_penjualan' => 'SJ-002',
            'tanggal' => '2026-04-25',
            'status' => 'draft',
            'total_harga' => 0,
        ]);

        $sourceItem = $penjualan->items()->create([
            'gudang_id' => $gudang->id,
            'nama_barang' => 'Semen',
            'qty' => 8,
            'satuan' => 'Zak',
            'harga_satuan' => 65000,
            'total_harga' => 520000,
        ]);

        $suratJalan = SuratJalan::query()->create([
            'nomor_surat_jalan' => 'SJ-002',
            'tanggal' => '2026-04-25',
            'status' => 'draft',
        ]);

        $suratJalan->items()->create([
            'penjualan_item_id' => $sourceItem->id,
            'nama_barang' => $sourceItem->nama_barang,
            'qty' => $sourceItem->qty,
            'satuan' => $sourceItem->satuan,
            'keterangan' => 'Dikirim',
        ]);

        $tandaTerima = TandaTerima::query()->create([
            'nomor_tanda_terima' => 'TT-002',
            'nomor_surat_jalan' => 'SJ-002',
            'tanggal' => '2026-04-25',
            'status' => 'draft',
        ]);

        $createResponse = $this->postJson('/api/tanda-terima/'.$tandaTerima->id.'/items', [
            'penjualan_item_id' => $sourceItem->id,
            'keterangan' => 'Diterima lengkap',
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('message', 'Item tanda terima berhasil ditambahkan.')
            ->assertJsonPath('data.nama_barang', 'Semen')
            ->assertJsonPath('data.qty', '8.00')
            ->assertJsonPath('data.keterangan', 'Diterima lengkap');

        $itemId = $createResponse->json('data.id');

        $this->getJson('/api/tanda-terima/'.$tandaTerima->id.'/items/'.$itemId)
            ->assertOk()
            ->assertJsonPath('data.id', $itemId)
            ->assertJsonPath('data.nama_barang', 'Semen');

        $this->putJson('/api/tanda-terima/'.$tandaTerima->id.'/items/'.$itemId, [
            'penjualan_item_id' => $sourceItem->id,
            'keterangan' => 'Diterima sebagian',
        ])
            ->assertOk()
            ->assertJsonPath('data.keterangan', 'Diterima sebagian');

        $this->deleteJson('/api/tanda-terima/'.$tandaTerima->id.'/items/'.$itemId)
            ->assertOk()
            ->assertJsonPath('message', 'Item tanda terima berhasil dihapus.');

        $this->assertDatabaseMissing('tanda_terima_items', [
            'id' => $itemId,
        ]);
    }

    public function test_item_from_other_tanda_terima_returns_not_found(): void
    {
        $recordA = TandaTerima::query()->create([
            'nomor_tanda_terima' => 'TT-003',
            'nomor_surat_jalan' => 'SJ-003',
            'tanggal' => '2026-04-25',
            'status' => 'draft',
        ]);

        $recordB = TandaTerima::query()->create([
            'nomor_tanda_terima' => 'TT-004',
            'nomor_surat_jalan' => 'SJ-004',
            'tanggal' => '2026-04-25',
            'status' => 'draft',
        ]);

        $item = TandaTerimaItem::query()->create([
            'tanda_terima_id' => $recordB->id,
            'nama_barang' => 'Batu',
            'qty' => 2,
            'satuan' => 'Kubik',
            'keterangan' => 'Beda record',
        ]);

        $this->getJson('/api/tanda-terima/'.$recordA->id.'/items/'.$item->id)
            ->assertNotFound();
    }

    public function test_tanda_terima_rejects_items_not_present_in_linked_surat_jalan(): void
    {
        $gudang = Gudang::query()->create([
            'nama_gudang' => 'Gudang Penjualan',
            'alamat' => 'Jl. Melati',
            'nama_pic' => 'Budi',
            'no_pic' => '08123456789',
        ]);

        $penjualan = Penjualan::query()->create([
            'kode_penjualan' => 'SJ-099',
            'tanggal' => '2026-04-25',
            'status' => 'draft',
            'total_harga' => 0,
        ]);

        $sourceItem = $penjualan->items()->create([
            'gudang_id' => $gudang->id,
            'nama_barang' => 'Kerikil',
            'qty' => 4,
            'satuan' => 'Karung',
            'harga_satuan' => 10000,
            'total_harga' => 40000,
        ]);

        SuratJalan::query()->create([
            'nomor_surat_jalan' => 'SJ-099',
            'tanggal' => '2026-04-25',
            'status' => 'draft',
        ]);

        $tandaTerima = TandaTerima::query()->create([
            'nomor_tanda_terima' => 'TT-099',
            'nomor_surat_jalan' => 'SJ-099',
            'tanggal' => '2026-04-25',
            'status' => 'draft',
        ]);

        $this->postJson('/api/tanda-terima/'.$tandaTerima->id.'/items', [
            'penjualan_item_id' => $sourceItem->id,
            'keterangan' => 'Tidak ada di surat jalan',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['penjualan_item_id']);
    }
}
