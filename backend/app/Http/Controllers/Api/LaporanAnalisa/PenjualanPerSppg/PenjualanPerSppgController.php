<?php

namespace App\Http\Controllers\Api\LaporanAnalisa\PenjualanPerSppg;

use App\Http\Controllers\Controller;
use App\Models\TransaksiPenjualan\SuratJalanItem;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class PenjualanPerSppgController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'periode' => ['nullable', Rule::in(['harian', 'bulanan', 'tahunan'])],
            'tanggal' => ['nullable', 'date'],
            'search' => ['nullable', 'string'],
            'sort_field' => ['nullable', Rule::in(['nama_sppg', 'total_penjualan', 'persentase'])],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $periode = $validated['periode'] ?? 'bulanan';
        $tanggal = isset($validated['tanggal'])
            ? Carbon::parse($validated['tanggal'], 'Asia/Jakarta')
            : Carbon::today('Asia/Jakarta');
        $search = strtolower($validated['search'] ?? '');
        $sortField = $validated['sort_field'] ?? 'total_penjualan';
        $sortOrder = $validated['sort_order'] ?? 'desc';
        $perPage = $validated['per_page'] ?? 10;
        $page = $validated['page'] ?? 1;

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
            ->get();

        $totalGlobal = (float) $rows->sum('total_penjualan');

        $records = $rows->map(function ($row) use ($totalGlobal): array {
            $totalPenjualan = (float) $row->total_penjualan;

            return [
                'sppg_id' => $row->sppg_id,
                'nama_sppg' => $row->nama_sppg,
                'total_penjualan' => $totalPenjualan,
                'persentase' => $totalGlobal > 0
                    ? round(($totalPenjualan / $totalGlobal) * 100, 2)
                    : 0,
            ];
        });

        if ($search !== '') {
            $records = $records->filter(fn (array $row): bool => str_contains(strtolower($row['nama_sppg']), $search))->values();
        }

        $records = $this->sortRows($records, $sortField, $sortOrder);

        $paginator = new LengthAwarePaginator(
            $records->forPage($page, $perPage)->values(),
            $records->count(),
            $perPage,
            $page
        );

        return response()->json([
            'message' => 'Laporan penjualan per SPPG berhasil diambil.',
            'data' => $paginator->items(),
            'meta' => [
                'periode' => $periode,
                'tanggal_acuan' => $tanggal->toDateString(),
                'total_penjualan_global' => $totalGlobal,
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }

    private function sortRows(Collection $rows, string $sortField, string $sortOrder): Collection
    {
        $sorted = $rows->sort(function (array $left, array $right) use ($sortField, $sortOrder): int {
            $leftValue = $left[$sortField] ?? null;
            $rightValue = $right[$sortField] ?? null;

            if (is_string($leftValue) || is_string($rightValue)) {
                $comparison = strcmp((string) $leftValue, (string) $rightValue);
            } else {
                $comparison = ($leftValue <=> $rightValue);
            }

            return $sortOrder === 'desc' ? -$comparison : $comparison;
        });

        return $sorted->values();
    }
}
