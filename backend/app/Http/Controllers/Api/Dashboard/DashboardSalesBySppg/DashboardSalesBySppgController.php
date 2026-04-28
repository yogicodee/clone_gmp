<?php

namespace App\Http\Controllers\Api\Dashboard\DashboardSalesBySppg;

use App\Http\Controllers\Controller;
use App\Models\TransaksiPenjualan\SuratJalanItem;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DashboardSalesBySppgController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'periode' => ['nullable', Rule::in(['harian', 'bulanan', 'tahunan'])],
            'tanggal' => ['nullable', 'date'],
        ]);

        $periode = $validated['periode'] ?? 'bulanan';
        $tanggal = isset($validated['tanggal'])
            ? Carbon::parse($validated['tanggal'], 'Asia/Jakarta')
            : Carbon::today('Asia/Jakarta');

        $query = SuratJalanItem::query()
            ->selectRaw('surat_jalan.sppg_id, sppg.nama_sppg, SUM(penjualan_items.total_harga) as total_penjualan')
            ->join('surat_jalan', 'surat_jalan.id', '=', 'surat_jalan_items.surat_jalan_id')
            ->join('penjualan_items', 'penjualan_items.id', '=', 'surat_jalan_items.penjualan_item_id')
            ->join('penjualan', 'penjualan.id', '=', 'penjualan_items.penjualan_id')
            ->join('sppg', 'sppg.id', '=', 'surat_jalan.sppg_id')
            ->whereNotNull('surat_jalan.sppg_id')
            ->where('surat_jalan.status', 'selesai')
            ->where('penjualan.status', 'selesai');

        match ($periode) {
            'harian' => $query->whereDate('surat_jalan.tanggal', $tanggal->toDateString()),
            'tahunan' => $query->whereYear('surat_jalan.tanggal', $tanggal->year),
            default => $query
                ->whereYear('surat_jalan.tanggal', $tanggal->year)
                ->whereMonth('surat_jalan.tanggal', $tanggal->month),
        };

        $rows = $query
            ->groupBy('surat_jalan.sppg_id', 'sppg.nama_sppg')
            ->orderByDesc('total_penjualan')
            ->get();

        $totalGlobal = (float) $rows->sum('total_penjualan');

        $breakdown = $rows->map(function ($row) use ($totalGlobal): array {
            $totalPenjualan = (float) $row->total_penjualan;

            return [
                'sppg_id' => $row->sppg_id,
                'nama_sppg' => $row->nama_sppg,
                'total_penjualan' => $totalPenjualan,
                'persentase' => $totalGlobal > 0
                    ? round(($totalPenjualan / $totalGlobal) * 100, 2)
                    : 0,
            ];
        })->values();

        return response()->json([
            'message' => 'Data penjualan per SPPG berhasil diambil.',
            'data' => [
                'periode' => $periode,
                'tanggal_acuan' => $tanggal->toDateString(),
                'total_penjualan_global' => $totalGlobal,
                'breakdown' => $breakdown,
            ],
        ]);
    }
}
