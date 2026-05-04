<?php

namespace Tests\Feature;

use App\Models\MasterData\Sppg;
use App\Models\TransaksiPenjualan\InvoicePenjualan;
use App\Models\TransaksiPenjualan\Penjualan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePenjualanApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_penjualan_index_returns_paginated_records(): void
    {
        $sppg = Sppg::query()->create([
            'nama_sppg' => 'SPPG A',
            'alamat' => 'Surabaya',
            'nama_yayasan' => 'Yayasan A',
            'nama_penanggungjawab' => 'Budi',
            'no_penanggungjawab' => '081234567890',
        ]);

        $penjualanA = Penjualan::query()->create([
            'kode_penjualan' => 'TRX-001',
            'tanggal' => '2026-04-25',
            'status' => 'selesai',
            'total_harga' => 150000,
        ]);

        $penjualanB = Penjualan::query()->create([
            'kode_penjualan' => 'TRX-002',
            'tanggal' => '2026-04-26',
            'status' => 'draft',
            'total_harga' => 250000,
        ]);

        InvoicePenjualan::query()->create([
            'nomor_invoice' => 'INV-001',
            'penjualan_id' => $penjualanA->id,
            'sppg_id' => $sppg->id,
            'tanggal_invoice' => '2026-04-27',
            'total_tagihan' => 150000,
            'status_pembayaran' => 'belum lunas',
        ]);

        InvoicePenjualan::query()->create([
            'nomor_invoice' => 'INV-002',
            'penjualan_id' => $penjualanB->id,
            'sppg_id' => $sppg->id,
            'tanggal_invoice' => '2026-04-28',
            'total_tagihan' => 250000,
            'status_pembayaran' => 'lunas',
        ]);

        $response = $this->getJson('/api/invoice-penjualan?search=TRX-002');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Data invoice penjualan berhasil diambil.')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nomor_invoice', 'INV-002')
            ->assertJsonPath('data.0.kode_penjualan', 'TRX-002')
            ->assertJsonPath('data.0.nama_sppg', 'SPPG A');
    }

    public function test_invoice_penjualan_can_be_created_updated_and_deleted(): void
    {
        $sppg = Sppg::query()->create([
            'nama_sppg' => 'SPPG A',
            'alamat' => 'Surabaya',
            'nama_yayasan' => 'Yayasan A',
            'nama_penanggungjawab' => 'Budi',
            'no_penanggungjawab' => '081234567890',
        ]);

        $penjualan = Penjualan::query()->create([
            'kode_penjualan' => 'TRX-010',
            'tanggal' => '2026-04-25',
            'status' => 'selesai',
            'total_harga' => 300000,
        ]);

        $createResponse = $this->postJson('/api/invoice-penjualan', [
            'nomor_invoice' => 'INV-010',
            'penjualan_id' => $penjualan->id,
            'sppg_id' => $sppg->id,
            'tanggal_invoice' => '2026-04-29',
            'status_pembayaran' => 'belum lunas',
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('message', 'Invoice penjualan berhasil ditambahkan.')
            ->assertJsonPath('data.nomor_invoice', 'INV-010')
            ->assertJsonPath('data.total_tagihan', '300000.00')
            ->assertJsonPath('data.kode_penjualan', 'TRX-010')
            ->assertJsonPath('data.nama_sppg', 'SPPG A');

        $id = $createResponse->json('data.id');

        $this->getJson('/api/invoice-penjualan/'.$id)
            ->assertOk()
            ->assertJsonPath('message', 'Detail invoice penjualan berhasil diambil.')
            ->assertJsonPath('data.id', $id);

        $this->putJson('/api/invoice-penjualan/'.$id, [
            'nomor_invoice' => 'INV-010-REV',
            'penjualan_id' => $penjualan->id,
            'sppg_id' => $sppg->id,
            'tanggal_invoice' => '2026-04-30',
            'total_tagihan' => 275000,
            'status_pembayaran' => 'lunas',
        ])
            ->assertOk()
            ->assertJsonPath('data.nomor_invoice', 'INV-010-REV')
            ->assertJsonPath('data.total_tagihan', '275000.00')
            ->assertJsonPath('data.status_pembayaran', 'lunas');

        $this->deleteJson('/api/invoice-penjualan/'.$id)
            ->assertOk()
            ->assertJsonPath('message', 'Invoice penjualan berhasil dihapus.');

        $this->assertDatabaseMissing('invoice_penjualan', [
            'id' => $id,
        ]);
    }

    public function test_invoice_penjualan_rejects_duplicate_invoice_per_penjualan(): void
    {
        $sppg = Sppg::query()->create([
            'nama_sppg' => 'SPPG A',
            'alamat' => 'Surabaya',
            'nama_yayasan' => 'Yayasan A',
            'nama_penanggungjawab' => 'Budi',
            'no_penanggungjawab' => '081234567890',
        ]);

        $penjualan = Penjualan::query()->create([
            'kode_penjualan' => 'TRX-020',
            'tanggal' => '2026-04-25',
            'status' => 'selesai',
            'total_harga' => 175000,
        ]);

        InvoicePenjualan::query()->create([
            'nomor_invoice' => 'INV-020',
            'penjualan_id' => $penjualan->id,
            'sppg_id' => $sppg->id,
            'tanggal_invoice' => '2026-04-29',
            'total_tagihan' => 175000,
            'status_pembayaran' => 'belum lunas',
        ]);

        $this->postJson('/api/invoice-penjualan', [
            'nomor_invoice' => 'INV-020-B',
            'penjualan_id' => $penjualan->id,
            'sppg_id' => $sppg->id,
            'tanggal_invoice' => '2026-04-30',
            'status_pembayaran' => 'belum lunas',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['penjualan_id']);
    }

    public function test_invoice_penjualan_rejects_penjualan_that_is_not_selesai(): void
    {
        $sppg = Sppg::query()->create([
            'nama_sppg' => 'SPPG A',
            'alamat' => 'Surabaya',
            'nama_yayasan' => 'Yayasan A',
            'nama_penanggungjawab' => 'Budi',
            'no_penanggungjawab' => '081234567890',
        ]);

        $penjualan = Penjualan::query()->create([
            'kode_penjualan' => 'TRX-030',
            'tanggal' => '2026-04-25',
            'status' => 'draft',
            'total_harga' => 50000,
        ]);

        $this->postJson('/api/invoice-penjualan', [
            'nomor_invoice' => 'INV-030',
            'penjualan_id' => $penjualan->id,
            'sppg_id' => $sppg->id,
            'tanggal_invoice' => '2026-04-30',
            'status_pembayaran' => 'belum lunas',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['penjualan_id']);
    }
}
