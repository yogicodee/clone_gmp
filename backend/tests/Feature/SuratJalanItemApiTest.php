<?php

namespace Tests\Feature;

use App\Models\MasterData\Gudang;
use App\Models\TransaksiPenjualan\Penjualan;
use App\Models\TransaksiPenjualan\SuratJalan;
use App\Models\TransaksiPenjualan\SuratJalanItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuratJalanItemApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_opsi_barang_returns_penjualan_items(): void
    {
        $gudang = Gudang::query()->create([
            'nama_gudang' => 'Gudang Penjualan',
            'alamat' => 'Jl. Melati',
            'nama_pic' => 'Budi',
            'no_pic' => '08123456789',
        ]);

        $penjualan = Penjualan::query()->create([
            'kode_penjualan' => 'TRX-001',
            'tanggal' => '2026-04-25',
            'status' => 'draft',
            'total_harga' => 0,
        ]);

        $penjualan->items()->create([
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

        $response = $this->getJson('/api/surat-jalan/'.$suratJalan->id.'/opsi-barang');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Opsi barang surat jalan berhasil diambil.')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nama_barang', 'Pasir');
    }

    public function test_surat_jalan_item_can_be_created_updated_and_deleted(): void
    {
        $gudang = Gudang::query()->create([
            'nama_gudang' => 'Gudang Penjualan',
            'alamat' => 'Jl. Melati',
            'nama_pic' => 'Budi',
            'no_pic' => '08123456789',
        ]);

        $penjualan = Penjualan::query()->create([
            'kode_penjualan' => 'TRX-002',
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

        $createResponse = $this->postJson('/api/surat-jalan/'.$suratJalan->id.'/items', [
            'penjualan_item_id' => $sourceItem->id,
            'keterangan' => 'Dikirim lengkap',
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('message', 'Item surat jalan berhasil ditambahkan.')
            ->assertJsonPath('data.nama_barang', 'Semen')
            ->assertJsonPath('data.qty', '8.00')
            ->assertJsonPath('data.keterangan', 'Dikirim lengkap');

        $itemId = $createResponse->json('data.id');

        $this->getJson('/api/surat-jalan/'.$suratJalan->id.'/items/'.$itemId)
            ->assertOk()
            ->assertJsonPath('data.id', $itemId)
            ->assertJsonPath('data.nama_barang', 'Semen');

        $this->putJson('/api/surat-jalan/'.$suratJalan->id.'/items/'.$itemId, [
            'penjualan_item_id' => $sourceItem->id,
            'keterangan' => 'Dikirim sebagian',
        ])
            ->assertOk()
            ->assertJsonPath('data.keterangan', 'Dikirim sebagian');

        $this->deleteJson('/api/surat-jalan/'.$suratJalan->id.'/items/'.$itemId)
            ->assertOk()
            ->assertJsonPath('message', 'Item surat jalan berhasil dihapus.');

        $this->assertDatabaseMissing('surat_jalan_items', [
            'id' => $itemId,
        ]);
    }

    public function test_item_from_other_surat_jalan_returns_not_found(): void
    {
        $recordA = SuratJalan::query()->create([
            'nomor_surat_jalan' => 'SJ-003',
            'tanggal' => '2026-04-25',
            'status' => 'draft',
        ]);

        $recordB = SuratJalan::query()->create([
            'nomor_surat_jalan' => 'SJ-004',
            'tanggal' => '2026-04-25',
            'status' => 'draft',
        ]);

        $item = SuratJalanItem::query()->create([
            'surat_jalan_id' => $recordB->id,
            'nama_barang' => 'Batu',
            'qty' => 2,
            'satuan' => 'Kubik',
            'keterangan' => 'Beda record',
        ]);

        $this->getJson('/api/surat-jalan/'.$recordA->id.'/items/'.$item->id)
            ->assertNotFound();
    }

    public function test_surat_jalan_rejects_items_from_different_penjualan_transactions(): void
    {
        $gudang = Gudang::query()->create([
            'nama_gudang' => 'Gudang Penjualan',
            'alamat' => 'Jl. Melati',
            'nama_pic' => 'Budi',
            'no_pic' => '08123456789',
        ]);

        $penjualanA = Penjualan::query()->create([
            'kode_penjualan' => 'TRX-100',
            'tanggal' => '2026-04-25',
            'status' => 'draft',
            'total_harga' => 0,
        ]);

        $penjualanB = Penjualan::query()->create([
            'kode_penjualan' => 'TRX-200',
            'tanggal' => '2026-04-25',
            'status' => 'draft',
            'total_harga' => 0,
        ]);

        $itemA = $penjualanA->items()->create([
            'gudang_id' => $gudang->id,
            'nama_barang' => 'Pasir',
            'qty' => 3,
            'satuan' => 'Kg',
            'harga_satuan' => 12000,
            'total_harga' => 36000,
        ]);

        $itemB = $penjualanB->items()->create([
            'gudang_id' => $gudang->id,
            'nama_barang' => 'Semen',
            'qty' => 5,
            'satuan' => 'Zak',
            'harga_satuan' => 65000,
            'total_harga' => 325000,
        ]);

        $suratJalan = SuratJalan::query()->create([
            'nomor_surat_jalan' => 'SJ-100',
            'tanggal' => '2026-04-25',
            'status' => 'draft',
        ]);

        $this->postJson('/api/surat-jalan/'.$suratJalan->id.'/items', [
            'penjualan_item_id' => $itemA->id,
            'keterangan' => 'Item pertama',
        ])->assertCreated();

        $this->postJson('/api/surat-jalan/'.$suratJalan->id.'/items', [
            'penjualan_item_id' => $itemB->id,
            'keterangan' => 'Item beda transaksi',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['penjualan_item_id']);
    }
}
