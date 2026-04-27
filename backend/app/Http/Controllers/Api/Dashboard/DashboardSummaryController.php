<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\TransaksiPenjualan\Penjualan;
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

        return response()->json([
            'message' => 'Ringkasan dashboard berhasil diambil.',
            'data' => [
                'tanggal' => $today,
                'omset_hari_ini' => $omsetHariIni,
            ],
        ]);
    }
}
