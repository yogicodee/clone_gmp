<?php

namespace Tests\Feature;

use App\Models\MasterData\Gudang;
use App\Models\MasterData\Sppg;
use App\Models\TransaksiPenjualan\Penjualan;
use App\Models\TransaksiPenjualan\SuratJalan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenjualanPerSppgApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_laporan_penjualan_per_sppg_returns_paginated_rows(): void
    {
        $gudang = Gudang::factory()->create([
            'nama_gudang' => 'Gudang Penjualan',
        ]);

        $sppgA = $this->createSppg('SPPG A', '081111111111');
        $sppgB = $this->createSppg('SPPG B', '082222222222');
        $sppgC = $this->createSppg('SPPG C', '083333333333');

        $this->createCompletedSalesChain($gudang->id, $sppgA->id, 'A', '2026-04-28', 9000000);
        $this->createCompletedSalesChain($gudang->id, $sppgB->id, 'B', '2026-04-10', 6000000);
        $this->createCompletedSalesChain($gudang->id, $sppgC->id, 'C', '2026-04-12', 5000000);

        $this->getJson('/api/laporan/penjualan-per-sppg?periode=bulanan&tanggal=2026-04-28')
            ->assertOk()
            ->assertJsonPath('message', 'Laporan penjualan per SPPG berhasil diambil.')
            ->assertJsonPath('meta.periode', 'bulanan')
            ->assertJsonPath('meta.total_penjualan_global', 20000000)
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.nama_sppg', 'SPPG A')
            ->assertJsonPath('data.0.total_penjualan', 9000000)
            ->assertJsonPath('data.0.persentase', 45)
            ->assertJsonPath('data.1.nama_sppg', 'SPPG B')
            ->assertJsonPath('data.2.nama_sppg', 'SPPG C');
    }

    public function test_laporan_penjualan_per_sppg_can_filter_by_search(): void
    {
        $gudang = Gudang::factory()->create([
            'nama_gudang' => 'Gudang Penjualan',
        ]);

        $sppgA = $this->createSppg('SPPG Alpha', '081111111111');
        $sppgB = $this->createSppg('SPPG Beta', '082222222222');

        $this->createCompletedSalesChain($gudang->id, $sppgA->id, 'ALPHA', '2026-04-28', 9000000);
        $this->createCompletedSalesChain($gudang->id, $sppgB->id, 'BETA', '2026-04-10', 6000000);

        $this->getJson('/api/laporan/penjualan-per-sppg?periode=bulanan&tanggal=2026-04-28&search=alpha')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.nama_sppg', 'SPPG Alpha');
    }

    private function createSppg(string $nama, string $phone): Sppg
    {
        return Sppg::query()->create([
            'nama_sppg' => $nama,
            'alamat' => 'Alamat '.$nama,
            'nama_yayasan' => 'Yayasan '.$nama,
            'nama_penanggungjawab' => 'PJ '.$nama,
            'no_penanggungjawab' => $phone,
        ]);
    }

    private function createCompletedSalesChain(int $gudangId, int $sppgId, string $suffix, string $tanggal, int $totalHarga): void
    {
        $penjualan = Penjualan::query()->create([
            'kode_penjualan' => 'TRX-LAP-'.$suffix,
            'tanggal' => $tanggal,
            'status' => 'selesai',
            'total_harga' => $totalHarga,
        ]);

        $item = $penjualan->items()->create([
            'gudang_id' => $gudangId,
            'nama_barang' => 'Barang '.$suffix,
            'qty' => 1,
            'satuan' => 'Pcs',
            'harga_satuan' => $totalHarga,
            'total_harga' => $totalHarga,
        ]);

        $suratJalan = SuratJalan::query()->create([
            'nomor_surat_jalan' => 'SJ-LAP-'.$suffix,
            'no_po' => 'PO-LAP-'.$suffix,
            'tanggal' => $tanggal,
            'sppg_id' => $sppgId,
            'status' => 'selesai',
        ]);

        $suratJalan->items()->create([
            'penjualan_item_id' => $item->id,
            'nama_barang' => 'Barang '.$suffix,
            'qty' => 1,
            'satuan' => 'Pcs',
            'keterangan' => 'Laporan test',
        ]);
    }
}
