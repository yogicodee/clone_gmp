<?php

namespace App\Http\Controllers\Api\Dashboard\DashboardExpenseAnalysis;

use App\Http\Controllers\Controller;
use App\Models\KeuanganAkuntansi\Pengeluaran;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardExpenseAnalysisController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'tanggal_awal' => ['nullable', 'date'],
            'tanggal_akhir' => ['nullable', 'date', 'after_or_equal:tanggal_awal'],
        ]);

        $today = Carbon::today('Asia/Jakarta');
        $tanggalAwal = isset($validated['tanggal_awal'])
            ? Carbon::parse($validated['tanggal_awal'], 'Asia/Jakarta')->toDateString()
            : $today->copy()->startOfMonth()->toDateString();
        $tanggalAkhir = isset($validated['tanggal_akhir'])
            ? Carbon::parse($validated['tanggal_akhir'], 'Asia/Jakarta')->toDateString()
            : $today->toDateString();

        $query = Pengeluaran::query()
            ->selectRaw('nama_operasional, SUM(qty * harga_satuan) as total_pengeluaran')
            ->whereBetween('tanggal_keluar', [$tanggalAwal, $tanggalAkhir])
            ->groupBy('nama_operasional')
            ->orderByDesc('total_pengeluaran');

        $items = $query
            ->limit(6)
            ->get()
            ->map(fn ($item): array => [
                'nama_operasional' => $item->nama_operasional,
                'total_pengeluaran' => (float) $item->total_pengeluaran,
            ])
            ->values();

        return response()->json([
            'message' => 'Data beban operasional dashboard berhasil diambil.',
            'data' => [
                'tanggal_awal' => $tanggalAwal,
                'tanggal_akhir' => $tanggalAkhir,
                'items' => $items,
            ],
        ]);
    }
}
