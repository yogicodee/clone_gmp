<?php

namespace App\Http\Controllers\Api\Dashboard\DashboardSummary;

use App\Http\Controllers\Controller;
use App\Models\TransaksiPenjualan\Penjualan;
use App\Models\WarehouseSystem\WarehouseInbound;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class DashboardSummaryController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $today = Carbon::today('Asia/Jakarta')->toDateString();

        $omsetHariIni = (float) Penjualan::query()
            ->whereDate('tanggal', $today)
            ->where('status', 'selesai')
            ->sum('total_harga');

        $pengeluaranHariIni = (float) WarehouseInbound::query()
            ->whereDate('tanggal_masuk', $today)
            ->sum('total_harga');

        $keuntunganHariIni = $omsetHariIni - $pengeluaranHariIni;

        return response()->json([
            'message' => 'Ringkasan dashboard berhasil diambil.',
            'data' => [
                'tanggal' => $today,
                'omset_hari_ini' => $omsetHariIni,
                'pengeluaran_hari_ini' => $pengeluaranHariIni,
                'keuntungan_hari_ini' => $keuntunganHariIni,
            ],
        ]);
    }
}
