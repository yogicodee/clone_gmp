<?php

namespace App\Http\Controllers\Api\Dashboard\DashboardInventorySummary;

use App\Http\Controllers\Controller;
use App\Models\WarehouseSystem\WarehouseStokBasah;
use App\Models\WarehouseSystem\WarehouseStokKering;
use Illuminate\Http\JsonResponse;

class DashboardInventorySummaryController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $stokKering = WarehouseStokKering::query()
            ->selectRaw('COALESCE(SUM(qty), 0) as total_qty, COALESCE(SUM(qty * harga_beli), 0) as total_nilai, COUNT(*) as total_baris, COUNT(DISTINCT gudang_id) as total_gudang')
            ->first();

        $stokBasah = WarehouseStokBasah::query()
            ->selectRaw('COALESCE(SUM(qty), 0) as total_qty, COALESCE(SUM(qty * harga_beli), 0) as total_nilai, COUNT(*) as total_baris, COUNT(DISTINCT gudang_id) as total_gudang')
            ->first();

        $totalQty = (float) ($stokKering?->total_qty ?? 0) + (float) ($stokBasah?->total_qty ?? 0);
        $totalNilai = (float) ($stokKering?->total_nilai ?? 0) + (float) ($stokBasah?->total_nilai ?? 0);
        $totalBaris = (int) ($stokKering?->total_baris ?? 0) + (int) ($stokBasah?->total_baris ?? 0);
        $gudangAktif = collect([
            (int) ($stokKering?->total_gudang ?? 0),
            (int) ($stokBasah?->total_gudang ?? 0),
        ])->max() ?? 0;

        return response()->json([
            'message' => 'Ringkasan persediaan dashboard berhasil diambil.',
            'data' => [
                'total_qty' => $totalQty,
                'total_nilai_stok' => $totalNilai,
                'total_baris_stok' => $totalBaris,
                'gudang_aktif' => $gudangAktif,
            ],
        ]);
    }
}
