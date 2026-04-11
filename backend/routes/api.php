<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ArmadaController;
use App\Http\Controllers\Api\BankRekeningController;
use App\Http\Controllers\Api\GudangController;
use App\Http\Controllers\Api\KaryawanController;
use App\Http\Controllers\Api\KategoriController;
use App\Http\Controllers\Api\MitraController;
use App\Http\Controllers\Api\OrderPenawaranController;
use App\Http\Controllers\Api\OrderPenawaranItemController;
use App\Http\Controllers\Api\ProdukController;
use App\Http\Controllers\Api\SppgController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\WilayahController;
use Illuminate\Support\Facades\Route;

//Autenthication
Route::prefix('auth')->group(function (): void {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth.api')->group(function (): void {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::apiResource('wilayah', WilayahController::class);
Route::apiResource('supplier', SupplierController::class);
Route::apiResource('mitra', MitraController::class);
Route::apiResource('sppg', SppgController::class);
Route::apiResource('produk', ProdukController::class);
Route::apiResource('gudang', GudangController::class);
Route::apiResource('armada', ArmadaController::class);
Route::apiResource('karyawan', KaryawanController::class);
Route::apiResource('bank-rekening', BankRekeningController::class);
Route::apiResource('kategori', KategoriController::class);
Route::apiResource('order-penawaran', OrderPenawaranController::class);
Route::get('order-penawaran/{orderPenawaran}/items', [OrderPenawaranItemController::class, 'index']);
Route::post('order-penawaran/{orderPenawaran}/items', [OrderPenawaranItemController::class, 'store']);
Route::get('order-penawaran/{orderPenawaran}/items/{item}', [OrderPenawaranItemController::class, 'show']);
Route::put('order-penawaran/{orderPenawaran}/items/{item}', [OrderPenawaranItemController::class, 'update']);
Route::delete('order-penawaran/{orderPenawaran}/items/{item}', [OrderPenawaranItemController::class, 'destroy']);
