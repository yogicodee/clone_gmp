<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Dashboard\DashboardCashflowTrend\DashboardCashflowTrendController;
use App\Http\Controllers\Api\Dashboard\DashboardExpenseAnalysis\DashboardExpenseAnalysisController;
use App\Http\Controllers\Api\Dashboard\DashboardInventorySummary\DashboardInventorySummaryController;
use App\Http\Controllers\Api\Dashboard\DashboardSalesBySppg\DashboardSalesBySppgController;
use App\Http\Controllers\Api\Dashboard\DashboardSummary\DashboardSummaryController;
use App\Http\Controllers\Api\KeuanganAkuntansi\Pemasukan\PemasukanController;
use App\Http\Controllers\Api\KeuanganAkuntansi\Pengeluaran\PengeluaranController;
use App\Http\Controllers\Api\LaporanAnalisa\LabaRugiTransaksional\LabaRugiTransaksionalController;
use App\Http\Controllers\Api\LaporanAnalisa\LaporanStokBarang\LaporanStokBarangController;
use App\Http\Controllers\Api\LaporanAnalisa\PenjualanPerSppg\PenjualanPerSppgController;
use App\Http\Controllers\Api\MasterData\Armada\ArmadaController;
use App\Http\Controllers\Api\MasterData\BankRekening\BankRekeningController;
use App\Http\Controllers\Api\MasterData\Gudang\GudangController;
use App\Http\Controllers\Api\MasterData\Karyawan\KaryawanController;
use App\Http\Controllers\Api\MasterData\Kategori\KategoriController;
use App\Http\Controllers\Api\MasterData\Mitra\MitraController;
use App\Http\Controllers\Api\MasterData\Perusahaan\PerusahaanController;
use App\Http\Controllers\Api\MasterData\Produk\ProdukController;
use App\Http\Controllers\Api\MasterData\Sppg\SppgController;
use App\Http\Controllers\Api\MasterData\Supplier\SupplierController;
use App\Http\Controllers\Api\MasterData\Wilayah\WilayahController;
use App\Http\Controllers\Api\TransaksiPembelian\DaftarPembelanjaan\DaftarPembelanjaanController;
use App\Http\Controllers\Api\TransaksiPembelian\DaftarPembelanjaan\DaftarPembelanjaanItem\DaftarPembelanjaanItemController;
use App\Http\Controllers\Api\TransaksiPembelian\DaftarPembelanjaanSupplier\DaftarPembelanjaanSupplierController;
use App\Http\Controllers\Api\TransaksiPembelian\OrderPenawaran\OrderPenawaranController;
use App\Http\Controllers\Api\TransaksiPembelian\OrderPenawaran\OrderPenawaranItem\OrderPenawaranItemController;
use App\Http\Controllers\Api\TransaksiPenjualan\InvoicePenjualan\InvoicePenjualanController;
use App\Http\Controllers\Api\TransaksiPenjualan\Penjualan\PenjualanController;
use App\Http\Controllers\Api\TransaksiPenjualan\Penjualan\PenjualanItem\PenjualanItemController;
use App\Http\Controllers\Api\TransaksiPenjualan\SuratJalan\SuratJalanController;
use App\Http\Controllers\Api\TransaksiPenjualan\SuratJalan\SuratJalanItem\SuratJalanItemController;
use App\Http\Controllers\Api\TransaksiPenjualan\TandaTerima\TandaTerimaController;
use App\Http\Controllers\Api\TransaksiPenjualan\TandaTerima\TandaTerimaItem\TandaTerimaItemController;
use App\Http\Controllers\Api\WarehouseSystem\WarehouseInbound\WarehouseInboundController;
use App\Http\Controllers\Api\WarehouseSystem\WarehouseRetur\WarehouseReturController;
use App\Http\Controllers\Api\WarehouseSystem\WarehouseStokBasah\WarehouseStokBasahController;
use App\Http\Controllers\Api\WarehouseSystem\WarehouseStokKering\WarehouseStokKeringController;
use Illuminate\Support\Facades\Route;

//Autenthication
Route::prefix('auth')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth.api')->group(function (): void {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth.api')->group(function (): void {
    //============================== Dashboard =============================
    Route::get('dashboard/summary', DashboardSummaryController::class);
    Route::get('dashboard/penjualan-per-sppg', DashboardSalesBySppgController::class);
    Route::get('dashboard/cashflow-trend', DashboardCashflowTrendController::class);
    Route::get('dashboard/beban-operasional', DashboardExpenseAnalysisController::class);
    Route::get('dashboard/persediaan', DashboardInventorySummaryController::class);

    //============================== Laporan dan Analisa =====================
    Route::get('laporan/stok-barang', LaporanStokBarangController::class);
    Route::get('laporan/laba-rugi-transaksional', LabaRugiTransaksionalController::class);
    Route::get('laporan/penjualan-per-sppg', PenjualanPerSppgController::class);

    //============================== Keuangan dan Akuntansi =====================
    Route::apiResource('pemasukan', PemasukanController::class);
    Route::apiResource('pengeluaran', PengeluaranController::class);

    // ============================== Master Data ===========================
    Route::apiResource('wilayah', WilayahController::class);
    Route::apiResource('supplier', SupplierController::class);
    Route::apiResource('mitra', MitraController::class);
    Route::apiResource('sppg', SppgController::class);
    Route::apiResource('produk', ProdukController::class);
    Route::apiResource('perusahaan', PerusahaanController::class);
    Route::apiResource('gudang', GudangController::class);
    Route::apiResource('armada', ArmadaController::class);
    Route::apiResource('karyawan', KaryawanController::class);
    Route::apiResource('bank-rekening', BankRekeningController::class);
    Route::apiResource('kategori', KategoriController::class);

    // ============================ Transaksi Pembelian ===========================
    // Order Penawaran
    Route::apiResource('order-penawaran', OrderPenawaranController::class);
    Route::get('order-penawaran/filter/by-tanggal', [OrderPenawaranController::class, 'byTanggal']);
    Route::get('order-penawaran/{orderPenawaran}/items', [OrderPenawaranItemController::class, 'index']);
    Route::post('order-penawaran/{orderPenawaran}/items', [OrderPenawaranItemController::class, 'store']);
    Route::get('order-penawaran/{orderPenawaran}/items/{item}', [OrderPenawaranItemController::class, 'show']);
    Route::put('order-penawaran/{orderPenawaran}/items/{item}', [OrderPenawaranItemController::class, 'update']);
    Route::delete('order-penawaran/{orderPenawaran}/items/{item}', [OrderPenawaranItemController::class, 'destroy']);
    // daftar pembelanjaan
    Route::apiResource('daftar-pembelanjaan', DaftarPembelanjaanController::class);
    Route::get('daftar-pembelanjaan/{daftarPembelanjaan}/items', [DaftarPembelanjaanItemController::class, 'index']);
    Route::post('daftar-pembelanjaan/{daftarPembelanjaan}/items', [DaftarPembelanjaanItemController::class, 'store']);
    Route::get('daftar-pembelanjaan/{daftarPembelanjaan}/items/{item}', [DaftarPembelanjaanItemController::class, 'show']);
    Route::put('daftar-pembelanjaan/{daftarPembelanjaan}/items/{item}', [DaftarPembelanjaanItemController::class, 'update']);
    Route::delete('daftar-pembelanjaan/{daftarPembelanjaan}/items/{item}', [DaftarPembelanjaanItemController::class, 'destroy']);
    // daftar pembelanjaan supplier
    Route::get('daftar-pembelanjaan-supplier', [DaftarPembelanjaanSupplierController::class, 'index']);
    Route::get('daftar-pembelanjaan-supplier/{daftarPembelanjaan}', [DaftarPembelanjaanSupplierController::class, 'show']);

    // ============================ Transaksi Penjualan ===========================
    Route::apiResource('penjualan', PenjualanController::class);
    Route::get('penjualan/{penjualan}/opsi-barang', [PenjualanItemController::class, 'opsiBarang']);
    Route::get('penjualan/{penjualan}/items', [PenjualanItemController::class, 'index']);
    Route::post('penjualan/{penjualan}/items', [PenjualanItemController::class, 'store']);
    Route::get('penjualan/{penjualan}/items/{item}', [PenjualanItemController::class, 'show']);
    Route::put('penjualan/{penjualan}/items/{item}', [PenjualanItemController::class, 'update']);
    Route::delete('penjualan/{penjualan}/items/{item}', [PenjualanItemController::class, 'destroy']);

    Route::apiResource('surat-jalan', SuratJalanController::class);
    Route::get('surat-jalan/{suratJalan}/opsi-barang', [SuratJalanItemController::class, 'opsiBarang']);
    Route::get('surat-jalan/{suratJalan}/items', [SuratJalanItemController::class, 'index']);
    Route::post('surat-jalan/{suratJalan}/items', [SuratJalanItemController::class, 'store']);
    Route::get('surat-jalan/{suratJalan}/items/{item}', [SuratJalanItemController::class, 'show']);
    Route::put('surat-jalan/{suratJalan}/items/{item}', [SuratJalanItemController::class, 'update']);
    Route::delete('surat-jalan/{suratJalan}/items/{item}', [SuratJalanItemController::class, 'destroy']);

    Route::apiResource('tanda-terima', TandaTerimaController::class);
    Route::get('tanda-terima/{tandaTerima}/opsi-barang', [TandaTerimaItemController::class, 'opsiBarang']);
    Route::get('tanda-terima/{tandaTerima}/items', [TandaTerimaItemController::class, 'index']);
    Route::post('tanda-terima/{tandaTerima}/items', [TandaTerimaItemController::class, 'store']);
    Route::get('tanda-terima/{tandaTerima}/items/{item}', [TandaTerimaItemController::class, 'show']);
    Route::put('tanda-terima/{tandaTerima}/items/{item}', [TandaTerimaItemController::class, 'update']);
    Route::delete('tanda-terima/{tandaTerima}/items/{item}', [TandaTerimaItemController::class, 'destroy']);
    Route::get('invoice-penjualan/opsi-sppg', [InvoicePenjualanController::class, 'opsiSppgByTanggalKirim']);
    Route::apiResource('invoice-penjualan', InvoicePenjualanController::class);

    // ============================= Warehouse System ===========================
    Route::apiResource('inbound', WarehouseInboundController::class);
    Route::apiResource('stok-kering', WarehouseStokKeringController::class);
    Route::apiResource('stok-basah', WarehouseStokBasahController::class);
    Route::apiResource('retur-rusak', WarehouseReturController::class);
});
