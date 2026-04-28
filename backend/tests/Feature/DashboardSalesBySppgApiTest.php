<?php

namespace Tests\Feature;

use App\Models\MasterData\Gudang;
use App\Models\MasterData\Sppg;
use App\Models\TransaksiPenjualan\Penjualan;
use App\Models\TransaksiPenjualan\SuratJalan;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardSalesBySppgApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_sales_by_sppg_returns_grouped_totals_for_selected_period(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 4, 28, 10, 0, 0, 'Asia/Jakarta'));

        $gudang = Gudang::query()->create([
            'nama_gudang' => 'Gudang Penjualan',
            'alamat' => 'Jl. Melati',
            'nama_pic' => 'Budi',
            'no_pic' => '08123456789',
        ]);

        $sppgA = $this->createSppg('SPPG A', '081111111111');
        $sppgB = $this->createSppg('SPPG B', '082222222222');
        $sppgC = $this->createSppg('SPPG C', '083333333333');

        $this->createCompletedSalesChain($gudang->id, $sppgA->id, 'A', '2026-04-28', 9000000);
        $this->createCompletedSalesChain($gudang->id, $sppgB->id, 'B', '2026-04-10', 6000000);
        $this->createCompletedSalesChain($gudang->id, $sppgC->id, 'C', '2026-04-12', 5000000);
        $this->createCompletedSalesChain($gudang->id, $sppgA->id, 'OLD', '2026-03-20', 7000000);

        $response = $this->getJson('/api/dashboard/penjualan-per-sppg?periode=bulanan&tanggal=2026-04-28');

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Data penjualan per SPPG berhasil diambil.')
            ->assertJsonPath('data.periode', 'bulanan')
            ->assertJsonPath('data.tanggal_acuan', '2026-04-28')
            ->assertJsonPath('data.total_penjualan_global', 20000000)
            ->assertJsonCount(3, 'data.breakdown')
            ->assertJsonPath('data.breakdown.0.nama_sppg', 'SPPG A')
            ->assertJsonPath('data.breakdown.0.total_penjualan', 9000000)
            ->assertJsonPath('data.breakdown.0.persentase', 45)
            ->assertJsonPath('data.breakdown.1.nama_sppg', 'SPPG B')
            ->assertJsonPath('data.breakdown.1.total_penjualan', 6000000)
            ->assertJsonPath('data.breakdown.2.nama_sppg', 'SPPG C')
            ->assertJsonPath('data.breakdown.2.total_penjualan', 5000000);

        Carbon::setTestNow();
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
            'kode_penjualan' => 'TRX-GRAFIK-'.$suffix,
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
            'nomor_surat_jalan' => 'SJ-GRAFIK-'.$suffix,
            'no_po' => 'PO-GRAFIK-'.$suffix,
            'tanggal' => $tanggal,
            'sppg_id' => $sppgId,
            'status' => 'selesai',
        ]);

        $suratJalan->items()->create([
            'penjualan_item_id' => $item->id,
            'nama_barang' => 'Barang '.$suffix,
            'qty' => 1,
            'satuan' => 'Pcs',
            'keterangan' => 'Dashboard test',
        ]);
    }
}
