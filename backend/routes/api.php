<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MasterData\ArmadaController;
use App\Http\Controllers\Api\MasterData\BankRekeningController;
use App\Http\Controllers\Api\MasterData\GudangController;
use App\Http\Controllers\Api\MasterData\KaryawanController;
use App\Http\Controllers\Api\MasterData\KategoriController;
use App\Http\Controllers\Api\MasterData\MitraController;
use App\Http\Controllers\Api\MasterData\PerusahaanController;
use App\Http\Controllers\Api\MasterData\ProdukController;
use App\Http\Controllers\Api\MasterData\SppgController;
use App\Http\Controllers\Api\MasterData\SupplierController;
use App\Http\Controllers\Api\MasterData\WilayahController;
use App\Http\Controllers\Api\TransaksiPenjualan\InvoicePenjualanController;
use App\Http\Controllers\Api\TransaksiPembelian\DaftarPembelanjaanController;
use App\Http\Controllers\Api\TransaksiPembelian\DaftarPembelanjaanItemController;
use App\Http\Controllers\Api\TransaksiPembelian\DaftarPembelanjaanSupplierController;
use App\Http\Controllers\Api\TransaksiPembelian\OrderPenawaranController;
use App\Http\Controllers\Api\TransaksiPembelian\OrderPenawaranItemController;
use App\Http\Controllers\Api\TransaksiPenjualan\PenjualanController;
use App\Http\Controllers\Api\TransaksiPenjualan\PenjualanItemController;
use App\Http\Controllers\Api\TransaksiPenjualan\SuratJalanController;
use App\Http\Controllers\Api\TransaksiPenjualan\SuratJalanItemController;
use App\Http\Controllers\Api\TransaksiPenjualan\TandaTerimaController;
use App\Http\Controllers\Api\TransaksiPenjualan\TandaTerimaItemController;
use App\Http\Controllers\Api\WarehouseSystem\WarehouseInboundController;
use App\Http\Controllers\Api\WarehouseSystem\WarehouseReturController;
use App\Http\Controllers\Api\WarehouseSystem\WarehouseStokBasahController;
use App\Http\Controllers\Api\WarehouseSystem\WarehouseStokKeringController;
use Illuminate\Support\Facades\Route;

//Autenthication
Route::prefix('auth')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth.api')->group(function (): void {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

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
Route::apiResource('invoice-penjualan', InvoicePenjualanController::class);

// ============================= Warehouse System ===========================
Route::apiResource('inbound', WarehouseInboundController::class);
Route::apiResource('stok-kering', WarehouseStokKeringController::class);
Route::apiResource('stok-basah', WarehouseStokBasahController::class);
Route::apiResource('retur-rusak', WarehouseReturController::class);
