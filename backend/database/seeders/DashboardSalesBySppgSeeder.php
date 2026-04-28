<?php

namespace Database\Seeders;

use App\Models\MasterData\Gudang;
use App\Models\MasterData\Sppg;
use App\Models\TransaksiPenjualan\Penjualan;
use App\Models\TransaksiPenjualan\SuratJalan;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DashboardSalesBySppgSeeder extends Seeder
{
    public function run(): void
    {
        $today = Carbon::today('Asia/Jakarta')->toDateString();

        $gudang = Gudang::query()->firstOrCreate(
            ['nama_gudang' => 'Gudang Dashboard'],
            [
                'alamat' => 'Jl. Dashboard',
                'nama_pic' => 'PIC Dashboard',
                'no_pic' => '081234567890',
            ]
        );

        $sppgA = Sppg::query()->firstOrCreate(
            ['nama_sppg' => 'SPPG A'],
            [
                'alamat' => 'Alamat A',
                'nama_yayasan' => 'Yayasan A',
                'nama_penanggungjawab' => 'PJ A',
                'no_penanggungjawab' => '081111111111',
            ]
        );

        $sppgB = Sppg::query()->firstOrCreate(
            ['nama_sppg' => 'SPPG B'],
            [
                'alamat' => 'Alamat B',
                'nama_yayasan' => 'Yayasan B',
                'nama_penanggungjawab' => 'PJ B',
                'no_penanggungjawab' => '082222222222',
            ]
        );

        $sppgC = Sppg::query()->firstOrCreate(
            ['nama_sppg' => 'SPPG C'],
            [
                'alamat' => 'Alamat C',
                'nama_yayasan' => 'Yayasan C',
                'nama_penanggungjawab' => 'PJ C',
                'no_penanggungjawab' => '083333333333',
            ]
        );

        $this->seedSalesForSppg($today, $gudang->id, $sppgA->id, 'A', 9000000);
        $this->seedSalesForSppg($today, $gudang->id, $sppgB->id, 'B', 6000000);
        $this->seedSalesForSppg($today, $gudang->id, $sppgC->id, 'C', 5000000);
    }

    private function seedSalesForSppg(string $today, int $gudangId, int $sppgId, string $suffix, int $totalHarga): void
    {
        $penjualan = Penjualan::query()->updateOrCreate(
            ['kode_penjualan' => 'TRX-SPPG-'.$suffix],
            [
                'tanggal' => $today,
                'status' => 'selesai',
                'total_harga' => $totalHarga,
            ]
        );

        $item = $penjualan->items()->updateOrCreate(
            ['nama_barang' => 'Barang '.$suffix],
            [
                'gudang_id' => $gudangId,
                'qty' => 1,
                'satuan' => 'Pcs',
                'harga_satuan' => $totalHarga,
                'total_harga' => $totalHarga,
            ]
        );

        $suratJalan = SuratJalan::query()->updateOrCreate(
            ['nomor_surat_jalan' => 'SJ-SPPG-'.$suffix],
            [
                'no_po' => 'PO-SPPG-'.$suffix,
                'tanggal' => $today,
                'sppg_id' => $sppgId,
                'status' => 'selesai',
            ]
        );

        $suratJalan->items()->updateOrCreate(
            ['penjualan_item_id' => $item->id],
            [
                'nama_barang' => 'Barang '.$suffix,
                'qty' => 1,
                'satuan' => 'Pcs',
                'keterangan' => 'Dashboard sample',
            ]
        );
    }
}
